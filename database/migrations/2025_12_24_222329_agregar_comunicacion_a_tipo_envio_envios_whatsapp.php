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
        // Modificar el ENUM de tipo_envio en envios_whatsapp para agregar 'comunicacion'
        DB::statement("ALTER TABLE `envios_whatsapp` MODIFY COLUMN `tipo_envio` ENUM('orden_pago', 'recordatorio_vencido', 'recordatorio_proximo', 'auth', 'recuperacion_password', 'factura_electronica', 'confirmacion_pago', 'servicio_suspendido', 'servicio_renovado', 'servicio_activado', 'comunicacion') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir: eliminar 'comunicacion' del ENUM
        DB::statement("ALTER TABLE `envios_whatsapp` MODIFY COLUMN `tipo_envio` ENUM('orden_pago', 'recordatorio_vencido', 'recordatorio_proximo', 'auth', 'recuperacion_password', 'factura_electronica', 'confirmacion_pago', 'servicio_suspendido', 'servicio_renovado', 'servicio_activado') NOT NULL");
    }
};
