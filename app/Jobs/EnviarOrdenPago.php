<?php

namespace App\Jobs;

use App\Models\ColaEnvio;
use App\Models\EnvioWhatsapp;
use App\Models\SesionEnvio;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class EnviarOrdenPago implements ShouldQueue
{
    use Queueable;

    public $tries = 1; // Solo 1 intento - NO reintentar automáticamente
    public $timeout = 120;

    protected $colaEnvioId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $colaEnvioId)
    {
        $this->colaEnvioId = $colaEnvioId;

        // Asignar a cola específica de órdenes de pago
        $this->onQueue('ordenes-pago');

        // ⚠️ DELAY ALEATORIO: Simula comportamiento humano (15-45 segundos entre envíos)
        $delaySegundos = rand(15, 45);
        $this->delay(now()->addSeconds($delaySegundos));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $trabajo = ColaEnvio::find($this->colaEnvioId);

        if (!$trabajo) {
            Log::error("Trabajo de cola no encontrado: {$this->colaEnvioId}");
            return;
        }

        // Marcar como procesando
        $trabajo->update([
            'estado' => 'procesando',
            'fecha_inicio_procesamiento' => now(),
        ]);

        try {
            // Obtener configuración de WhatsApp desde la base de datos
            $token = \DB::table('configuracion')->where('clave', 'token_whatsapp')->value('valor');
            $instancia = \DB::table('configuracion')->where('clave', 'instancia_whatsapp')->value('valor');
            $apiUrl = \DB::table('configuracion')->where('clave', 'api_url_whatsapp')->value('valor');

            if (!$token || !$instancia || !$apiUrl) {
                throw new \Exception('Configuración de WhatsApp incompleta en base de datos');
            }

            // URL completa para envío de medios
            $url = rtrim($apiUrl, '/') . '/message/sendmedia/' . $instancia;

            // Limpiar base64: remover prefijo data:image/png;base64, si existe
            $imagenBase64 = $trabajo->imagen_base64;
            if (strpos($imagenBase64, 'data:image') === 0) {
                $imagenBase64 = preg_replace('/^data:image\/[a-z]+;base64,/', '', $imagenBase64);
            }

            // Enviar a WhatsApp API
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ])
                ->post($url, [
                    'number' => $trabajo->whatsapp,
                    'caption' => $trabajo->mensaje_texto,
                    'media' => $imagenBase64,
                    'mediatype' => 'image', // Tipo de archivo requerido por la API
                ]);

            if ($response->successful()) {
                // Éxito
                $trabajo->update([
                    'estado' => 'enviado',
                    'fecha_envio' => now(),
                    'respuesta_api' => $response->json(),
                ]);

                // Registrar en historial
                EnvioWhatsapp::create([
                    'cliente_id' => $trabajo->cliente_id,
                    'servicio_contratado_id' => $trabajo->servicio_contratado_id,
                    'tipo_envio' => $trabajo->tipo_envio,
                    'numero_destino' => $trabajo->whatsapp,
                    'mensaje_texto' => $trabajo->mensaje_texto,
                    'imagen_generada' => true,
                    'estado' => 'enviado',
                    'fecha_envio' => now(),
                    'respuesta_api' => $response->json(),
                ]);

                // Actualizar estadísticas de sesión
                $this->actualizarSesion($trabajo->sesion_id, true);

            } else {
                throw new \Exception('Error en API WhatsApp: ' . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("Error enviando WhatsApp (cola {$this->colaEnvioId}): " . $e->getMessage());

            $trabajo->increment('intentos');

            // Marcar como error - NO reintentar automáticamente
            $trabajo->update([
                'estado' => 'error',
                'mensaje_error' => $e->getMessage(),
            ]);

            $this->actualizarSesion($trabajo->sesion_id, false);
        }
    }

    /**
     * Actualizar estadísticas de la sesión
     */
    protected function actualizarSesion(int $sesionId, bool $exitoso): void
    {
        $sesion = SesionEnvio::find($sesionId);
        if (!$sesion) return;

        $sesion->increment('procesados');

        if ($exitoso) {
            $sesion->increment('exitosos');
        } else {
            $sesion->increment('fallidos');
        }

        // Si ya se procesaron todos, marcar sesión como completado
        if ($sesion->procesados >= $sesion->total_clientes) {
            $sesion->update([
                'estado' => 'completado',
                'fecha_finalizacion' => now(),
            ]);
        }
    }
}
