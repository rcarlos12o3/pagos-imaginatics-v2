<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HistorialPago extends Model
{
    protected $table = 'historial_pagos';

    public $timestamps = false;

    protected $fillable = [
        'cliente_id',
        'factura_id',
        'monto_pagado',
        'fecha_pago',
        'metodo_pago',
        'numero_operacion',
        'banco',
        'comprobante_ruta',
        'observaciones',
        'registrado_por',
        'servicios_pagados',
        'periodo_inicio',
        'periodo_fin'
    ];

    protected $casts = [
        'monto_pagado' => 'decimal:2',
        'fecha_pago' => 'date',
        'fecha_registro' => 'datetime',
        'servicios_pagados' => 'array',
        'periodo_inicio' => 'date',
        'periodo_fin' => 'date'
    ];

    /**
     * Relación con Cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    /**
     * Obtener servicios contratados relacionados
     */
    public function servicios()
    {
        if (!$this->servicios_pagados) {
            return collect([]);
        }

        return ServicioContratado::whereIn('id', $this->servicios_pagados)->get();
    }

    /**
     * Scope: Filtrar por cliente
     */
    public function scopePorCliente($query, $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }

    /**
     * Scope: Filtrar por método de pago
     */
    public function scopePorMetodo($query, $metodo)
    {
        return $query->where('metodo_pago', $metodo);
    }

    /**
     * Scope: Filtrar por rango de fechas
     */
    public function scopeEntreFechas($query, $fechaInicio, $fechaFin)
    {
        return $query->whereBetween('fecha_pago', [$fechaInicio, $fechaFin]);
    }

    /**
     * Scope: Pagos recientes
     */
    public function scopeRecientes($query, $dias = 30)
    {
        return $query->where('fecha_pago', '>=', now()->subDays($dias));
    }
}
