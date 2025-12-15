<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CatalogoServicio extends Model
{
    protected $table = 'catalogo_servicios';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'descripcion',
        'categoria',
        'precio_base',
        'moneda',
        'periodos_disponibles',
        'requiere_facturacion',
        'igv_incluido',
        'configuracion_default',
        'activo',
        'orden_visualizacion',
    ];

    protected $casts = [
        'precio_base' => 'decimal:2',
        'periodos_disponibles' => 'array',
        'configuracion_default' => 'array',
        'requiere_facturacion' => 'boolean',
        'igv_incluido' => 'boolean',
        'activo' => 'boolean',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
    ];

    /**
     * Servicios contratados que usan este servicio
     */
    public function serviciosContratados(): HasMany
    {
        return $this->hasMany(ServicioContratado::class, 'servicio_id');
    }

    /**
     * Scope para servicios activos
     */
    public function scopeActivo($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope por categoría
     */
    public function scopeCategoria($query, $categoria)
    {
        return $query->where('categoria', $categoria);
    }
}
