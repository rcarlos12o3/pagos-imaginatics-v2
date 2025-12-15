<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsultaRuc extends Model
{
    protected $table = 'consultas_ruc';

    public $timestamps = false;

    protected $fillable = [
        'ruc',
        'respuesta_api',
        'estado_consulta',
        'ip_origen',
    ];

    protected $casts = [
        'respuesta_api' => 'array',
        'fecha_consulta' => 'datetime',
    ];

    /**
     * Scope para consultas exitosas
     */
    public function scopeExitosas($query)
    {
        return $query->where('estado_consulta', 'exitosa');
    }

    /**
     * Scope para consultas recientes (últimas 24 horas)
     */
    public function scopeRecientes($query)
    {
        return $query->where('fecha_consulta', '>=', now()->subHours(24));
    }

    /**
     * Scope para buscar por RUC
     */
    public function scopePorRuc($query, $ruc)
    {
        return $query->where('ruc', $ruc);
    }
}
