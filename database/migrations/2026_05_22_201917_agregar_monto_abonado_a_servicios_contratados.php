<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('servicios_contratados', function (Blueprint $table) {
            $table->decimal('monto_abonado', 10, 2)->default(0)->after('precio');
        });
    }

    public function down(): void
    {
        Schema::table('servicios_contratados', function (Blueprint $table) {
            $table->dropColumn('monto_abonado');
        });
    }
};
