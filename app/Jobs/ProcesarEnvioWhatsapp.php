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

class ProcesarEnvioWhatsapp implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $timeout = 120;

    protected $colaEnvioId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $colaEnvioId)
    {
        $this->colaEnvioId = $colaEnvioId;

        // ⚠️ DELAY ALEATORIO: Simula comportamiento humano (2-5 segundos entre envíos)
        $delaySegundos = rand(2, 5);
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
            // Enviar a WhatsApp API
            $response = Http::timeout(30)
                ->withToken(config('services.whatsapp.token'))
                ->post(config('services.whatsapp.url'), [
                    'phone' => $trabajo->whatsapp,
                    'message' => $trabajo->mensaje_texto,
                    'image' => $trabajo->imagen_base64,
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
                    'whatsapp' => $trabajo->whatsapp,
                    'mensaje' => $trabajo->mensaje_texto,
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

            if ($trabajo->intentos >= $trabajo->max_intentos) {
                // Marcar como fallido después de todos los intentos
                $trabajo->update([
                    'estado' => 'fallido',
                    'error' => $e->getMessage(),
                ]);
                $this->actualizarSesion($trabajo->sesion_id, false);
            } else {
                // Reintentar
                $trabajo->update([
                    'estado' => 'pendiente',
                    'error' => $e->getMessage(),
                ]);

                // Re-encolar con delay más largo (simular pausa humana)
                dispatch(new ProcesarEnvioWhatsapp($this->colaEnvioId))
                    ->delay(now()->addMinutes(rand(1, 3)));
            }
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

        // Si ya se procesaron todos, marcar sesión como completada
        if ($sesion->procesados >= $sesion->total_clientes) {
            $sesion->update([
                'estado' => 'completada',
                'fecha_finalizacion' => now(),
            ]);
        }
    }
}
