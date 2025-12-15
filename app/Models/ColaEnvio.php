<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ColaEnvio extends Model
{
    protected $table = 'cola_envios';

    public $timestamps = false;

    protected $fillable = [
        'sesion_id',
        'cliente_id',
        'servicio_contratado_id',
        'tipo_envio',
        'prioridad',
        'estado',
        'intentos',
        'max_intentos',
        'ruc',
        'razon_social',
        'whatsapp',
        'monto',
        'fecha_vencimiento',
        'tipo_servicio',
        'mensaje_texto',
        'imagen_base64',
        'dias_restantes',
        'fecha_creacion',
        'fecha_programada',
        'fecha_procesamiento',
        'fecha_envio',
        'envio_whatsapp_id',
        'respuesta_api',
        'mensaje_error',
    ];

    protected $casts = [
        'respuesta_api' => 'array',
        'fecha_creacion' => 'datetime',
        'fecha_programada' => 'datetime',
        'fecha_procesamiento' => 'datetime',
        'fecha_envio' => 'datetime',
        'fecha_vencimiento' => 'date',
    ];

    /**
     * Sesión a la que pertenece
     */
    public function sesion(): BelongsTo
    {
        return $this->belongsTo(SesionEnvio::class, 'sesion_id');
    }

    /**
     * Cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Servicio contratado
     */
    public function servicioContratado(): BelongsTo
    {
        return $this->belongsTo(ServicioContratado::class, 'servicio_contratado_id');
    }

    /**
     * Scope por estado
     */
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope pendientes
     */
    public function scopePendientes($query)
    {
        return $query->where('estado', 'pendiente');
    }

    /**
     * Scope por sesión
     */
    public function scopeSesion($query, $sesionId)
    {
        return $query->where('sesion_id', $sesionId);
    }
}
