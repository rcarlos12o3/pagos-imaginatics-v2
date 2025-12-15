<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ServicioContratado extends Model
{
    protected $table = 'servicios_contratados';

    public $timestamps = false;

    protected $fillable = [
        'cliente_id',
        'servicio_id',
        'precio',
        'moneda',
        'periodo_facturacion',
        'fecha_inicio',
        'fecha_vencimiento',
        'fecha_ultima_factura',
        'fecha_proximo_pago',
        'estado',
        'motivo_suspension',
        'auto_renovacion',
        'configuracion',
        'notas',
        'usuario_creacion',
    ];

    protected $casts = [
        'precio' => 'decimal:2',
        'fecha_inicio' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_ultima_factura' => 'date',
        'fecha_proximo_pago' => 'date',
        'auto_renovacion' => 'boolean',
        'configuracion' => 'array',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
    ];

    /**
     * Cliente que contrató el servicio
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Servicio del catálogo
     */
    public function catalogoServicio(): BelongsTo
    {
        return $this->belongsTo(CatalogoServicio::class, 'servicio_id');
    }

    /**
     * Envíos de WhatsApp relacionados a este servicio
     */
    public function envios(): HasMany
    {
        return $this->hasMany(EnvioWhatsapp::class, 'servicio_contratado_id');
    }

    /**
     * Scope para servicios activos
     */
    public function scopeActivo($query)
    {
        return $query->where('estado', 'activo');
    }

    /**
     * Scope por periodo de facturación
     */
    public function scopePeriodo($query, $periodo)
    {
        return $query->where('periodo_facturacion', $periodo);
    }

    /**
     * Scope por estado
     */
    public function scopeEstado($query, $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope por cliente
     */
    public function scopeCliente($query, $clienteId)
    {
        return $query->where('cliente_id', $clienteId);
    }
}
