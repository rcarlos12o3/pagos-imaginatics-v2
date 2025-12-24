<?php

namespace App\Console\Commands;

use App\Models\ColaEnvio;
use App\Models\SesionEnvio;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class ProcesarComunicaciones extends Command
{
    /**
     * Comando para procesar cola de comunicaciones
     */
    protected $signature = 'comunicaciones:procesar {--force : Forzar procesamiento}';

    protected $description = 'Procesa la cola de comunicaciones enviando mensajes por WhatsApp';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $inicio = Carbon::now('America/Lima');

        $this->info("🚀 Iniciando procesamiento de comunicaciones [" . $inicio->format('Y-m-d H:i:s') . "]");

        // Verificar si hay comunicaciones pendientes
        $pendientes = ColaEnvio::where('tipo_envio', 'comunicacion')
            ->where('estado', 'pendiente')
            ->count();

        if ($pendientes === 0) {
            $this->info("✅ No hay comunicaciones pendientes");
            return Command::SUCCESS;
        }

        $this->info("📋 {$pendientes} comunicaciones pendientes");

        // Obtener sesiones pendientes de comunicaciones
        $sesiones = SesionEnvio::where('tipo_envio', 'comunicacion')
            ->whereIn('estado', ['pendiente', 'procesando'])
            ->orderBy('fecha_creacion', 'asc')
            ->get();

        if ($sesiones->isEmpty()) {
            $this->warn("⚠️ No hay sesiones de comunicaciones para procesar");
            return Command::SUCCESS;
        }

        foreach ($sesiones as $sesion) {
            $this->procesarSesion($sesion);
        }

        $fin = Carbon::now('America/Lima');
        $duracion = $inicio->diffInSeconds($fin);

        $this->info("✅ Procesamiento completado en {$duracion} segundos");

        return Command::SUCCESS;
    }

    /**
     * Procesar una sesión de comunicaciones
     */
    private function procesarSesion(SesionEnvio $sesion)
    {
        $this->info("📦 Procesando sesión #{$sesion->id}");

        // Marcar como procesando
        if ($sesion->estado === 'pendiente') {
            $sesion->update(['estado' => 'procesando']);
        }

        // Obtener trabajos pendientes de esta sesión
        $trabajos = ColaEnvio::where('sesion_id', $sesion->id)
            ->where('tipo_envio', 'comunicacion')
            ->where('estado', 'pendiente')
            ->orderBy('fecha_creacion', 'asc')
            ->get();

        if ($trabajos->isEmpty()) {
            $this->info("  ✅ Sesión #{$sesion->id} completada (sin trabajos pendientes)");

            $sesion->update([
                'estado' => 'completado',
                'fecha_finalizacion' => Carbon::now('America/Lima')
            ]);

            return;
        }

        $this->info("  📋 {$trabajos->count()} trabajos en cola");
        $this->info("  ⚙️ Los Jobs se procesarán automáticamente con queue:work");
        $this->info("  ⏱️ Delays configurados: 30-90 segundos entre mensajes");

        // Verificar el estado de la cola
        $this->newLine();
        $this->comment("💡 Asegúrate de que el worker de Laravel esté corriendo:");
        $this->line("   php artisan queue:work --queue=comunicaciones");
    }
}
