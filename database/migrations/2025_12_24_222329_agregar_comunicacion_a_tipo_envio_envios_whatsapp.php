<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar que la tabla existe antes de modificarla
        $tableExists = DB::select("SHOW TABLES LIKE 'envios_whatsapp'");

        if (empty($tableExists)) {
            // Tabla no existe - estamos en entorno de testing/CI vacío
            return;
        }

        // Modificar el ENUM de tipo_envio en envios_whatsapp para agregar 'comunicacion'
        DB::statement("ALTER TABLE `envios_whatsapp` MODIFY COLUMN `tipo_envio` ENUM('orden_pago', 'recordatorio_vencido', 'recordatorio_proximo', 'auth', 'recuperacion_password', 'factura_electronica', 'confirmacion_pago', 'servicio_suspendido', 'servicio_renovado', 'servicio_activado', 'comunicacion') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Verificar que la tabla existe antes de modificarla
        $tableExists = DB::select("SHOW TABLES LIKE 'envios_whatsapp'");

        if (empty($tableExists)) {
            return;
        }

        // Revertir: eliminar 'comunicacion' del ENUM
        DB::statement("ALTER TABLE `envios_whatsapp` MODIFY COLUMN `tipo_envio` ENUM('orden_pago', 'recordatorio_vencido', 'recordatorio_proximo', 'auth', 'recuperacion_password', 'factura_electronica', 'confirmacion_pago', 'servicio_suspendido', 'servicio_renovado', 'servicio_activado') NOT NULL");
    }
};
