<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Eliminar el trigger automático
        DB::unprepared('DROP TRIGGER IF EXISTS tr_actualizar_servicio_vencido');

        // 2. Cambiar todos los servicios 'vencido' a 'activo'
        DB::statement("UPDATE servicios_contratados SET estado = 'activo' WHERE estado = 'vencido'");

        // 3. Modificar el ENUM para eliminar 'vencido'
        DB::statement("ALTER TABLE servicios_contratados MODIFY COLUMN estado ENUM('activo', 'suspendido', 'cancelado') DEFAULT 'activo'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Restaurar el ENUM con 'vencido'
        DB::statement("ALTER TABLE servicios_contratados MODIFY COLUMN estado ENUM('activo', 'suspendido', 'cancelado', 'vencido') DEFAULT 'activo'");

        // 2. Recrear el trigger
        DB::unprepared("
            CREATE TRIGGER tr_actualizar_servicio_vencido
            BEFORE UPDATE ON servicios_contratados
            FOR EACH ROW
            BEGIN
                IF NEW.fecha_vencimiento < CURDATE()
                   AND NEW.estado = 'activo'
                   AND OLD.estado = 'activo' THEN
                    SET NEW.estado = 'vencido';
                END IF;
            END
        ");
    }
};
