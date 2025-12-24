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
        // Modificar el ENUM de tipo_envio en cola_envios para agregar 'comunicacion'
        DB::statement("ALTER TABLE `cola_envios` MODIFY COLUMN `tipo_envio` ENUM('orden_pago', 'recordatorio_proximo', 'recordatorio_vencido', 'comunicacion') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir: eliminar 'comunicacion' del ENUM
        DB::statement("ALTER TABLE `cola_envios` MODIFY COLUMN `tipo_envio` ENUM('orden_pago', 'recordatorio_proximo', 'recordatorio_vencido') NOT NULL");
    }
};
