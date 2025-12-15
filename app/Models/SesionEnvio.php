<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SesionEnvio extends Model
{
    protected $table = 'sesiones_envio';

    public $timestamps = false;

    protected $fillable = [
        'tipo_envio',
        'total_clientes',
        'procesados',
        'exitosos',
        'fallidos',
        'estado',
        'configuracion',
        'fecha_creacion',
        'fecha_finalizacion',
    ];

    protected $casts = [
        'configuracion' => 'array',
        'fecha_creacion' => 'datetime',
        'fecha_finalizacion' => 'datetime',
    ];

    /**
     * Trabajos de envío de esta sesión
     */
    public function trabajos(): HasMany
    {
        return $this->hasMany(ColaEnvio::class, 'sesion_id');
    }

    /**
     * Scope por estado
     */
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope por tipo de envío
     */
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo_envio', $tipo);
    }
}
