<?php

namespace App\Console\Commands;

use App\Models\ColaEnvio;
use App\Models\EnvioWhatsapp;
use App\Models\SesionEnvio;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProcesarColaEnvios extends Command
{
    /**
     * Comando para procesar cola de envíos de WhatsApp
     * Con comportamiento humano: delays aleatorios, horario laboral, límites por hora
     */
    protected $signature = 'cola:procesar {--force : Forzar procesamiento fuera de horario}';

    protected $description = 'Procesa cola de envíos de WhatsApp con comportamiento humano';

    // Configuración de comportamiento humano (optimizado)
    const PAUSA_ENTRE_MENSAJES = [10, 15]; // segundos entre imagen y texto
    const PAUSA_ENTRE_CLIENTES = [15, 30]; // segundos entre clientes
    const MAX_MENSAJES_POR_HORA = 60; // límite seguro para cuentas business
    const MAX_TRABAJOS_POR_EJECUCION = 50;
    const TIMEOUT_PROCESAMIENTO = 7200; // 2 horas
    const HORARIO_INICIO = 8; // 8 AM
    const HORARIO_FIN = 18; // 6 PM

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $inicio = Carbon::now('America/Lima');

        $this->info("🚀 Iniciando procesador de cola [" . $inicio->format('Y-m-d H:i:s') . "]");

        // Verificar horario laboral (a menos que se fuerce)
        if (!$this->option('force') && !$this->esHorarioLaboral($inicio)) {
            $this->warn("⏰ Fuera de horario laboral (8 AM - 6 PM). Use --force para procesar de todas formas.");
            return Command::SUCCESS;
        }

        // Verificar límite de mensajes por hora
        if (!$this->option('force') && !$this->validarLimitePorHora()) {
            $this->warn("⚠️ Límite de " . self::MAX_MENSAJES_POR_HORA . " mensajes por hora alcanzado. Esperando...");
            return Command::SUCCESS;
        }

        // Protección: Solo una instancia a la vez usando cache lock
        $lock = Cache::lock('procesar_cola_whatsapp', 3600); // 1 hora de lock

        if (!$lock->get()) {
            $this->warn("⏸️ Otra instancia ya está procesando la cola. Saliendo.");
            return Command::SUCCESS;
        }

        try {
            $this->procesarSesiones();

            $this->info("✅ Procesamiento completado en " . $inicio->diffInSeconds(Carbon::now('America/Lima')) . " segundos");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->error("❌ Error fatal: " . $e->getMessage());
            Log::error('Error en cola de envíos', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return Command::FAILURE;

        } finally {
            $lock->release();
        }
    }

    /**
     * Procesar sesiones pendientes
     */
    private function procesarSesiones()
    {
        $sesiones = SesionEnvio::whereIn('estado', ['pendiente', 'procesando'])
            ->where(function($q) {
                $q->whereNull('fecha_creacion')
                    ->orWhere('fecha_creacion', '>=', Carbon::now('America/Lima')->subSeconds(self::TIMEOUT_PROCESAMIENTO));
            })
            ->orderBy('fecha_creacion', 'asc')
            ->limit(5)
            ->get();

        if ($sesiones->isEmpty()) {
            $this->info("✅ No hay sesiones pendientes para procesar");
            return;
        }

        foreach ($sesiones as $sesion) {
            $this->info("📦 Procesando sesión #{$sesion->id} - Tipo: {$sesion->tipo_envio}");

            // Marcar como procesando
            if ($sesion->estado === 'pendiente') {
                $sesion->update([
                    'estado' => 'procesando',
                    'fecha_creacion' => Carbon::now('America/Lima')
                ]);
            }

            $this->procesarTrabajosDeSesion($sesion);
        }
    }

    /**
     * Procesar trabajos de una sesión
     */
    private function procesarTrabajosDeSesion(SesionEnvio $sesion)
    {
        // Obtener trabajos pendientes con lock
        $trabajos = ColaEnvio::where('sesion_id', $sesion->id)
            ->where('estado', 'pendiente')
            ->where('intentos', '<', DB::raw('max_intentos'))
            ->where(function($q) {
                $q->whereNull('fecha_programada')
                    ->orWhere('fecha_programada', '<=', Carbon::now('America/Lima'));
            })
            ->orderBy('fecha_creacion', 'asc')
            ->limit(self::MAX_TRABAJOS_POR_EJECUCION)
            ->lockForUpdate()
            ->get();

        if ($trabajos->isEmpty()) {
            $this->info("  ✅ Sesión #{$sesion->id} completada");

            $sesion->update([
                'estado' => 'completado',
                'fecha_finalizacion' => Carbon::now('America/Lima')
            ]);

            return;
        }

        $total = $trabajos->count();
        $this->info("  📋 {$total} trabajos pendientes");

        $procesados = 0;

        foreach ($trabajos as $index => $trabajo) {
            $num = $index + 1;

            // Obtener nombre del cliente
            $cliente = $trabajo->cliente;
            $nombreCliente = $cliente ? $cliente->razon_social : "Cliente #{$trabajo->cliente_id}";

            $this->line("  [{$num}/{$total}] Procesando: {$nombreCliente}");

            try {
                // Marcar como procesando e incrementar intentos
                $trabajo->increment('intentos');
                $trabajo->update([
                    'estado' => 'procesando',
                    'fecha_procesamiento' => Carbon::now('America/Lima'),
                ]);

                // Procesar envío
                $resultado = $this->procesarEnvio($trabajo);

                if ($resultado['success']) {
                    $trabajo->update([
                        'estado' => 'enviado',
                        'respuesta_api' => $resultado['respuesta'],
                        'mensaje_error' => null,
                        'fecha_envio' => Carbon::now('America/Lima'),
                        'envio_whatsapp_id' => $resultado['envio_id'] ?? null
                    ]);

                    $this->info("    ✅ Enviado exitosamente");
                    $procesados++;

                    // Actualizar contador de sesión
                    $sesion->increment('exitosos');

                } else {
                    $nuevoEstado = $trabajo->intentos >= $trabajo->max_intentos ? 'error' : 'pendiente';

                    $trabajo->update([
                        'estado' => $nuevoEstado,
                        'mensaje_error' => $resultado['error'],
                        'respuesta_api' => $resultado['respuesta'] ?? null,
                        'fecha_programada' => $nuevoEstado === 'pendiente'
                            ? Carbon::now('America/Lima')->addMinutes(30)
                            : null
                    ]);

                    $this->error("    ❌ Error: {$resultado['error']}");

                    if ($nuevoEstado === 'error') {
                        $sesion->increment('fallidos');
                    }
                }

                $sesion->increment('procesados');

                // Pausa humana entre clientes
                if ($index < $total - 1) {
                    $pausa = rand(self::PAUSA_ENTRE_CLIENTES[0], self::PAUSA_ENTRE_CLIENTES[1]);
                    $this->comment("    ⏱️ Pausa de {$pausa}s (modo humano)...");
                    sleep($pausa);
                }

            } catch (\Exception $e) {
                $this->error("    ⚠️ Excepción: {$e->getMessage()}");

                $trabajo->update([
                    'estado' => 'error',
                    'mensaje_error' => $e->getMessage()
                ]);

                $sesion->increment('fallidos');
                $sesion->increment('procesados');
            }
        }

        $this->info("  📊 Sesión #{$sesion->id}: {$procesados}/{$total} enviados exitosamente");
    }

    /**
     * Procesar un envío individual
     */
    private function procesarEnvio(ColaEnvio $trabajo): array
    {
        try {
            // Obtener configuración de WhatsApp
            $config = $this->obtenerConfigWhatsApp();

            if (!$config) {
                return [
                    'success' => false,
                    'error' => 'Configuración de WhatsApp no encontrada'
                ];
            }

            $numero = $this->formatearNumero($trabajo->whatsapp);

            // Enviar imagen con texto como caption
            if (!empty($trabajo->imagen_base64)) {
                $this->line("      📤 Enviando orden de pago (imagen + texto)...");

                $resultado = $this->enviarImagen($numero, $trabajo->imagen_base64, $trabajo->cliente_id, $trabajo->mensaje_texto, $config);

                if (!$resultado['success']) {
                    return $resultado;
                }

                $this->info("      ✅ Orden enviada exitosamente");
            } else {
                // Si no hay imagen, enviar solo texto
                $this->line("      💬 Enviando texto...");

                $resultado = $this->enviarTexto($numero, $trabajo->mensaje_texto, $config);

                if (!$resultado['success']) {
                    return $resultado;
                }

                $this->info("      ✅ Texto enviado");
            }

            // 3. Registrar en historial
            $envio = EnvioWhatsapp::create([
                'cliente_id' => $trabajo->cliente_id,
                'servicio_contratado_id' => $trabajo->servicio_contratado_id,
                'numero_destino' => $numero,
                'tipo_envio' => $trabajo->tipo_envio,
                'estado' => 'enviado',
                'mensaje' => $trabajo->mensaje_texto,
                'respuesta_api' => $resultado['respuesta'],
                'fecha_envio' => Carbon::now('America/Lima')
            ]);

            // Incrementar contador de mensajes por hora
            $this->incrementarContadorHora();

            return [
                'success' => true,
                'envio_id' => $envio->id,
                'respuesta' => $resultado['respuesta']
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Excepción: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar imagen por WhatsApp (Evolution API v1.8.x)
     */
    private function enviarImagen(string $numero, string $imagenBase64, int $clienteId, string $mensajeTexto, array $config): array
    {
        try {
            // Remover el prefijo "data:image/png;base64," si existe
            $base64Puro = $imagenBase64;
            if (str_starts_with($imagenBase64, 'data:image')) {
                $base64Puro = preg_replace('/^data:image\/(png|jpeg|jpg);base64,/', '', $imagenBase64);
            }

            $url = rtrim($config['api_url'], '/') . '/message/sendMedia/' . $config['instancia'];

            $response = Http::timeout(30)
                ->withHeaders([
                    'apikey' => $config['token'],
                    'Content-Type' => 'application/json'
                ])
                ->post($url, [
                    'number' => $numero,
                    'mediaMessage' => [
                        'mediatype' => 'image',
                        'fileName' => "orden_pago_{$clienteId}.png",
                        'media' => $base64Puro,
                        'caption' => $mensajeTexto,
                    ],
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}: " . ($response->json()['message'] ?? 'Error desconocido'),
                    'respuesta' => $response->json()
                ];
            }

            return [
                'success' => true,
                'respuesta' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error enviando imagen: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Enviar texto por WhatsApp (Evolution API v1.8.x)
     */
    private function enviarTexto(string $numero, string $mensaje, array $config): array
    {
        try {
            $url = rtrim($config['api_url'], '/') . '/message/sendText/' . $config['instancia'];

            $response = Http::timeout(30)
                ->withHeaders([
                    'apikey' => $config['token'],
                    'Content-Type' => 'application/json'
                ])
                ->post($url, [
                    'number' => $numero,
                    'textMessage' => [
                        'text' => $mensaje,
                    ],
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => "HTTP {$response->status()}: " . ($response->json()['message'] ?? 'Error desconocido'),
                    'respuesta' => $response->json()
                ];
            }

            return [
                'success' => true,
                'respuesta' => $response->json()
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => 'Error enviando texto: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtener configuración de WhatsApp desde BD
     */
    private function obtenerConfigWhatsApp(): ?array
    {
        try {
            $token = DB::table('configuracion')->where('clave', 'token_whatsapp')->value('valor');
            $instancia = DB::table('configuracion')->where('clave', 'instancia_whatsapp')->value('valor');
            $url = DB::table('configuracion')->where('clave', 'api_url_whatsapp')->value('valor');

            if (!$token || !$instancia || !$url) {
                return null;
            }

            return [
                'token' => $token,
                'instancia' => $instancia,
                'api_url' => $url
            ];

        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Formatear número de teléfono a formato internacional
     */
    private function formatearNumero(string $numero): string
    {
        $numeroLimpio = preg_replace('/[^0-9]/', '', $numero);

        // Si es número peruano de 9 dígitos, agregar código de país
        if (strlen($numeroLimpio) === 9) {
            return '51' . $numeroLimpio;
        }

        return $numeroLimpio;
    }

    /**
     * Verificar si estamos en horario laboral
     */
    private function esHorarioLaboral(Carbon $fecha): bool
    {
        $hora = $fecha->hour;
        $diaSemana = $fecha->dayOfWeek;

        // No procesar sábados (6) ni domingos (0)
        if ($diaSemana === 0 || $diaSemana === 6) {
            return false;
        }

        // Verificar horario 8 AM - 6 PM
        return $hora >= self::HORARIO_INICIO && $hora < self::HORARIO_FIN;
    }

    /**
     * Validar límite de mensajes por hora
     */
    private function validarLimitePorHora(): bool
    {
        $clave = 'whatsapp_mensajes_hora_' . Carbon::now('America/Lima')->format('Y-m-d-H');
        $contador = Cache::get($clave, 0);

        return $contador < self::MAX_MENSAJES_POR_HORA;
    }

    /**
     * Incrementar contador de mensajes por hora
     */
    private function incrementarContadorHora(): void
    {
        $clave = 'whatsapp_mensajes_hora_' . Carbon::now('America/Lima')->format('Y-m-d-H');
        $contador = Cache::get($clave, 0);

        Cache::put($clave, $contador + 1, 3600); // Expira en 1 hora
    }
}
