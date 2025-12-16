<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cliente extends Model
{
    protected $table = 'clientes';

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';

    protected $fillable = [
        'ruc',
        'tipo_documento',
        'razon_social',
        'monto',
        'fecha_vencimiento',
        'whatsapp',
        'email',
        'contacto_nombre',
        'contacto_cargo',
        'tipo_servicio',
        'direccion',
        'estado_sunat',
        'activo',
    ];

    protected $casts = [
        'fecha_vencimiento' => 'date',
        'monto' => 'decimal:2',
        'activo' => 'boolean',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
    ];

    public function serviciosContratados(): HasMany
    {
        return $this->hasMany(ServicioContratado::class, 'cliente_id');
    }

    public function enviosWhatsapp(): HasMany
    {
        return $this->hasMany(EnvioWhatsapp::class, 'cliente_id');
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    public function scopePorVencer($query, $dias = 30)
    {
        return $query->where('activo', true)
            ->whereNotNull('fecha_vencimiento')
            ->whereBetween('fecha_vencimiento', [now(), now()->addDays($dias)]);
    }

    /**
     * Accessor para limpiar razon_social de HTML entities y caracteres mal codificados
     */
    public function getRazonSocialAttribute($value): string
    {
        return clean_text($value);
    }

    /**
     * Accessor para limpiar contacto_nombre
     */
    public function getContactoNombreAttribute($value): ?string
    {
        return clean_text($value);
    }
}
