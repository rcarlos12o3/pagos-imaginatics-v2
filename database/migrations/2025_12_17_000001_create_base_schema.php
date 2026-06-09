<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('clientes')) {
            Schema::create('clientes', function (Blueprint $table) {
                $table->id();
                $table->string('ruc', 20)->unique();
                $table->string('tipo_documento', 20)->default('RUC');
                $table->string('razon_social', 500);
                $table->decimal('monto', 10, 2)->default(0);
                $table->date('fecha_vencimiento')->nullable();
                $table->string('whatsapp', 20)->nullable();
                $table->string('email', 255)->nullable();
                $table->string('contacto_nombre', 255)->nullable();
                $table->string('contacto_cargo', 100)->nullable();
                $table->string('tipo_servicio', 100)->nullable();
                $table->string('direccion', 500)->nullable();
                $table->string('estado_sunat', 100)->nullable();
                $table->boolean('activo')->default(true);
                $table->timestamp('fecha_creacion')->nullable();
                $table->timestamp('fecha_actualizacion')->nullable();
            });
        }

        if (!Schema::hasTable('catalogo_servicios')) {
            Schema::create('catalogo_servicios', function (Blueprint $table) {
                $table->id();
                $table->string('nombre', 255);
                $table->text('descripcion')->nullable();
                $table->string('categoria', 100)->nullable();
                $table->decimal('precio_base', 10, 2)->default(0);
                $table->string('moneda', 10)->default('PEN');
                $table->json('periodos_disponibles')->nullable();
                $table->boolean('requiere_facturacion')->default(false);
                $table->boolean('igv_incluido')->default(false);
                $table->json('configuracion_default')->nullable();
                $table->boolean('activo')->default(true);
                $table->integer('orden_visualizacion')->default(0);
                $table->timestamp('fecha_creacion')->nullable();
                $table->timestamp('fecha_actualizacion')->nullable();
            });
        }

        if (!Schema::hasTable('servicios_contratados')) {
            Schema::create('servicios_contratados', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cliente_id');
                $table->unsignedBigInteger('servicio_id');
                $table->decimal('precio', 10, 2);
                $table->string('moneda', 10)->default('PEN');
                $table->string('periodo_facturacion', 50);
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_vencimiento')->nullable();
                $table->date('fecha_ultima_factura')->nullable();
                $table->date('fecha_proximo_pago')->nullable();
                $table->enum('estado', ['activo', 'suspendido', 'cancelado', 'vencido'])->default('activo');
                $table->text('motivo_suspension')->nullable();
                $table->boolean('auto_renovacion')->default(false);
                $table->json('configuracion')->nullable();
                $table->text('notas')->nullable();
                $table->string('usuario_creacion', 255)->nullable();

                $table->foreign('cliente_id')->references('id')->on('clientes');
                $table->foreign('servicio_id')->references('id')->on('catalogo_servicios');
            });
        }

        if (!Schema::hasTable('historial_pagos')) {
            Schema::create('historial_pagos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cliente_id');
                $table->unsignedBigInteger('factura_id')->nullable();
                $table->decimal('monto_pagado', 10, 2);
                $table->date('fecha_pago');
                $table->string('metodo_pago', 100);
                $table->string('numero_operacion', 255)->nullable();
                $table->string('banco', 100)->nullable();
                $table->string('comprobante_ruta', 500)->nullable();
                $table->text('observaciones')->nullable();
                $table->string('registrado_por', 255)->nullable();
                $table->json('servicios_pagados')->nullable();
                $table->date('periodo_inicio')->nullable();
                $table->date('periodo_fin')->nullable();
                $table->timestamp('fecha_registro')->nullable();

                $table->foreign('cliente_id')->references('id')->on('clientes');
            });
        }

        if (!Schema::hasTable('sesiones_envio')) {
            Schema::create('sesiones_envio', function (Blueprint $table) {
                $table->id();
                $table->enum('tipo_envio', ['orden_pago', 'recordatorio_proximo', 'recordatorio_vencido']);
                $table->integer('total_clientes')->default(0);
                $table->integer('procesados')->default(0);
                $table->integer('exitosos')->default(0);
                $table->integer('fallidos')->default(0);
                $table->string('estado', 50)->default('pendiente');
                $table->json('configuracion')->nullable();
                $table->timestamp('fecha_creacion')->nullable();
                $table->timestamp('fecha_finalizacion')->nullable();
            });
        }

        if (!Schema::hasTable('cola_envios')) {
            Schema::create('cola_envios', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('sesion_id');
                $table->unsignedBigInteger('cliente_id')->nullable();
                $table->unsignedBigInteger('servicio_contratado_id')->nullable();
                $table->enum('tipo_envio', ['orden_pago', 'recordatorio_proximo', 'recordatorio_vencido']);
                $table->integer('prioridad')->default(0);
                $table->string('estado', 50)->default('pendiente');
                $table->integer('intentos')->default(0);
                $table->integer('max_intentos')->default(3);
                $table->string('ruc', 20)->nullable();
                $table->string('razon_social', 500)->nullable();
                $table->string('whatsapp', 20)->nullable();
                $table->decimal('monto', 10, 2)->nullable();
                $table->date('fecha_vencimiento')->nullable();
                $table->string('tipo_servicio', 100)->nullable();
                $table->text('mensaje_texto')->nullable();
                $table->longText('imagen_base64')->nullable();
                $table->integer('dias_restantes')->nullable();
                $table->timestamp('fecha_creacion')->nullable();
                $table->timestamp('fecha_programada')->nullable();
                $table->timestamp('fecha_procesamiento')->nullable();
                $table->timestamp('fecha_envio')->nullable();
                $table->unsignedBigInteger('envio_whatsapp_id')->nullable();
                $table->json('respuesta_api')->nullable();
                $table->text('mensaje_error')->nullable();

                $table->foreign('sesion_id')->references('id')->on('sesiones_envio');
                $table->foreign('cliente_id')->references('id')->on('clientes');
                $table->foreign('servicio_contratado_id')->references('id')->on('servicios_contratados');
            });
        }

        if (!Schema::hasTable('envios_whatsapp')) {
            Schema::create('envios_whatsapp', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('cliente_id');
                $table->unsignedBigInteger('factura_id')->nullable();
                $table->unsignedBigInteger('servicio_contratado_id')->nullable();
                $table->string('numero_destino', 20);
                $table->enum('tipo_envio', [
                    'orden_pago', 'recordatorio_vencido', 'recordatorio_proximo',
                    'auth', 'recuperacion_password', 'factura_electronica',
                    'confirmacion_pago', 'servicio_suspendido', 'servicio_renovado', 'servicio_activado',
                ]);
                $table->string('estado', 50)->default('pendiente');
                $table->text('mensaje_texto')->nullable();
                $table->boolean('imagen_generada')->default(false);
                $table->json('respuesta_api')->nullable();
                $table->text('mensaje_error')->nullable();
                $table->timestamp('fecha_envio')->nullable();
                $table->date('fecha_programado')->nullable();

                $table->foreign('cliente_id')->references('id')->on('clientes');
                $table->foreign('servicio_contratado_id')->references('id')->on('servicios_contratados');
            });
        }

        if (!Schema::hasTable('consultas_ruc')) {
            Schema::create('consultas_ruc', function (Blueprint $table) {
                $table->id();
                $table->string('ruc', 20);
                $table->json('respuesta_api')->nullable();
                $table->string('estado_consulta', 50);
                $table->string('ip_origen', 45)->nullable();
                $table->timestamp('fecha_consulta')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('cola_envios');
        Schema::dropIfExists('envios_whatsapp');
        Schema::dropIfExists('historial_pagos');
        Schema::dropIfExists('servicios_contratados');
        Schema::dropIfExists('sesiones_envio');
        Schema::dropIfExists('catalogo_servicios');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('consultas_ruc');
    }
};
