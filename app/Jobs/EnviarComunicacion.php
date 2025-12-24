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

class EnviarComunicacion implements ShouldQueue
{
    use Queueable;

    public $tries = 1;
    public $timeout = 120;

    protected $colaEnvioId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $colaEnvioId)
    {
        $this->colaEnvioId = $colaEnvioId;

        // Asignar a cola específica de comunicaciones
        $this->onQueue('comunicaciones');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $trabajo = ColaEnvio::find($this->colaEnvioId);

        if (!$trabajo) {
            Log::error("Trabajo de comunicación no encontrado: {$this->colaEnvioId}");
            return;
        }

        // Marcar como procesando
        $trabajo->update([
            'estado' => 'procesando',
            'fecha_procesamiento' => now(),
        ]);

        try {
            // Obtener configuración de WhatsApp
            $token = \DB::table('configuracion')->where('clave', 'token_whatsapp')->value('valor');
            $instancia = \DB::table('configuracion')->where('clave', 'instancia_whatsapp')->value('valor');
            $apiUrl = \DB::table('configuracion')->where('clave', 'api_url_whatsapp')->value('valor');

            if (!$token || !$instancia || !$apiUrl) {
                throw new \Exception('Configuración de WhatsApp incompleta');
            }

            $response = null;

            // Verificar si hay imagen
            if (!empty($trabajo->imagen_base64)) {
                // Enviar con imagen
                $response = $this->enviarConImagen($trabajo, $token, $instancia, $apiUrl);
            } else {
                // Enviar solo texto
                $response = $this->enviarSoloTexto($trabajo, $token, $instancia, $apiUrl);
            }

            if ($response && $response->successful()) {
                // Éxito
                $trabajo->update([
                    'estado' => 'enviado',
                    'fecha_envio' => now(),
                    'respuesta_api' => $response->json(),
                ]);

                // Registrar en historial
                EnvioWhatsapp::create([
                    'cliente_id' => $trabajo->cliente_id,
                    'numero_destino' => $trabajo->whatsapp,
                    'tipo_envio' => 'comunicacion',
                    'estado' => 'enviado',
                    'mensaje_texto' => $trabajo->mensaje_texto,
                    'imagen_generada' => !empty($trabajo->imagen_base64),
                    'fecha_envio' => Carbon::now('America/Lima'),
                    'respuesta_api' => $response->json(),
                ]);

                // Actualizar estadísticas de sesión
                $this->actualizarSesion($trabajo->sesion_id, true);

            } else {
                throw new \Exception('Error en API WhatsApp: ' . ($response ? $response->body() : 'Sin respuesta'));
            }

        } catch (\Exception $e) {
            Log::error("Error enviando comunicación (cola {$this->colaEnvioId}): " . $e->getMessage());

            $trabajo->increment('intentos');
            $trabajo->update([
                'estado' => 'error',
                'mensaje_error' => $e->getMessage(),
            ]);

            // Registrar error en historial
            EnvioWhatsapp::create([
                'cliente_id' => $trabajo->cliente_id,
                'numero_destino' => $trabajo->whatsapp,
                'tipo_envio' => 'comunicacion',
                'estado' => 'error',
                'mensaje_texto' => $trabajo->mensaje_texto,
                'imagen_generada' => !empty($trabajo->imagen_base64),
                'mensaje_error' => $e->getMessage(),
                'fecha_envio' => Carbon::now('America/Lima'),
            ]);

            $this->actualizarSesion($trabajo->sesion_id, false);
        }
    }

    /**
     * Enviar mensaje con imagen
     */
    protected function enviarConImagen($trabajo, $token, $instancia, $apiUrl)
    {
        $url = rtrim($apiUrl, '/') . '/message/sendmedia/' . $instancia;

        // Limpiar base64
        $imagenBase64 = $trabajo->imagen_base64;
        if (strpos($imagenBase64, 'data:image') === 0) {
            $imagenBase64 = preg_replace('/^data:image\/[a-z]+;base64,/', '', $imagenBase64);
        }

        return Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
                'number' => $trabajo->whatsapp,
                'mediatype' => 'image',
                'filename' => "comunicacion_{$trabajo->cliente_id}.png",
                'media' => $imagenBase64,
                'caption' => $trabajo->mensaje_texto,
            ]);
    }

    /**
     * Enviar solo texto
     */
    protected function enviarSoloTexto($trabajo, $token, $instancia, $apiUrl)
    {
        $url = rtrim($apiUrl, '/') . '/message/sendtext/' . $instancia;

        return Http::timeout(30)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $token,
                'Content-Type' => 'application/json',
            ])
            ->post($url, [
                'number' => $trabajo->whatsapp,
                'text' => $trabajo->mensaje_texto,
            ]);
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
