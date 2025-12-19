<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Verificar que la tabla existe
        if (!Schema::hasTable('clientes')) {
            return;
        }

        Schema::table('clientes', function (Blueprint $table) {
            $table->string('nombre_comercial', 255)->nullable()->after('razon_social');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('clientes')) {
            return;
        }

        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('nombre_comercial');
        });
    }
};
