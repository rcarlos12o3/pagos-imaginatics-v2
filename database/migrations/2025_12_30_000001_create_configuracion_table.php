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
        if (!Schema::hasTable('configuracion')) {
            Schema::create('configuracion', function (Blueprint $table) {
                $table->id();
                $table->string('clave')->unique();
                $table->text('valor')->nullable();
                $table->string('descripcion')->nullable();
                $table->timestamps();
            });
        }

        // Insertar configuración de Evolution API si no existe
        $configs = [
            [
                'clave' => 'api_url_whatsapp',
                'valor' => 'http://206.189.196.107:8085',
                'descripcion' => 'URL de la API de Evolution (WhatsApp)',
            ],
            [
                'clave' => 'token_whatsapp',
                'valor' => '8e5d98b4330d56eb08a652ea975eaf94c5e1cdaa35d1cb5c478d3a65b8fb9e7a',
                'descripcion' => 'API Key de Evolution API',
            ],
            [
                'clave' => 'instancia_whatsapp',
                'valor' => 'gerencia',
                'descripcion' => 'Nombre de la instancia de WhatsApp en Evolution API',
            ],
        ];

        foreach ($configs as $config) {
            DB::table('configuracion')->updateOrInsert(
                ['clave' => $config['clave']],
                $config
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracion');
    }
};
