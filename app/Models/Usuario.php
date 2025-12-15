<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Usuario extends Authenticatable
{
    use Notifiable;

    protected $table = 'usuarios';

    const CREATED_AT = 'fecha_creacion';
    const UPDATED_AT = 'fecha_actualizacion';

    protected $fillable = [
        'celular',
        'password_hash',
        'nombre',
        'activo',
        'primera_vez',
        'ultimo_acceso',
        'intentos_fallidos',
        'bloqueado_hasta',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'primera_vez' => 'boolean',
        'ultimo_acceso' => 'datetime',
        'bloqueado_hasta' => 'datetime',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'intentos_fallidos' => 'integer',
    ];

    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    public function getAuthIdentifierName()
    {
        return 'celular';
    }

    public function estaBloqueado(): bool
    {
        return $this->bloqueado_hasta && $this->bloqueado_hasta->isFuture();
    }

    public function incrementarIntentosFallidos(): void
    {
        $this->intentos_fallidos++;

        if ($this->intentos_fallidos >= 5) {
            $this->bloqueado_hasta = now()->addMinutes(15);
        }

        $this->save();
    }

    public function limpiarIntentosFallidos(): void
    {
        $this->intentos_fallidos = 0;
        $this->bloqueado_hasta = null;
        $this->ultimo_acceso = now();
        $this->save();
    }

    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}
