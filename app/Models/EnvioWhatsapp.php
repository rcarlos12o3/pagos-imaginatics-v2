<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnvioWhatsapp extends Model
{
    protected $table = 'envios_whatsapp';

    public $timestamps = false;

    protected $fillable = [
        'cliente_id',
        'factura_id',
        'servicio_contratado_id',
        'numero_destino',
        'tipo_envio',
        'estado',
        'mensaje_texto',
        'imagen_generada',
        'respuesta_api',
        'mensaje_error',
        'fecha_envio',
        'fecha_programado',
    ];

    protected $casts = [
        'imagen_generada' => 'boolean',
        'respuesta_api' => 'array',
        'fecha_envio' => 'datetime',
        'fecha_programado' => 'date',
    ];

    /**
     * Relación con Cliente
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Relación con ServicioContratado
     */
    public function servicioContratado(): BelongsTo
    {
        return $this->belongsTo(ServicioContratado::class, 'servicio_contratado_id');
    }

    /**
     * Scope para filtrar por tipo de envío
     */
    public function scopeTipoEnvio($query, $tipo)
    {
        if ($tipo) {
            return $query->where('tipo_envio', $tipo);
        }
        return $query;
    }

    /**
     * Scope para filtrar por estado
     */
    public function scopeEstado($query, $estado)
    {
        if ($estado) {
            return $query->where('estado', $estado);
        }
        return $query;
    }

    /**
     * Scope para buscar por cliente
     */
    public function scopeBuscarCliente($query, $search)
    {
        if ($search) {
            return $query->whereHas('cliente', function ($q) use ($search) {
                $q->where('razon_social', 'like', "%{$search}%")
                  ->orWhere('ruc', 'like', "%{$search}%");
            });
        }
        return $query;
    }

    /**
     * Scope para envíos de hoy
     */
    public function scopeHoy($query)
    {
        return $query->whereDate('fecha_envio', today());
    }

    /**
     * Scope para envíos del mes actual
     */
    public function scopeMesActual($query)
    {
        return $query->whereYear('fecha_envio', now()->year)
                    ->whereMonth('fecha_envio', now()->month);
    }

    /**
     * Scope para envíos exitosos
     */
    public function scopeExitosos($query)
    {
        return $query->where('estado', 'enviado');
    }

    /**
     * Scope para envíos con error
     */
    public function scopeConError($query)
    {
        return $query->where('estado', 'error');
    }

    /**
     * Obtener el nombre formateado del tipo de envío
     */
    public function getTipoEnvioNombreAttribute(): string
    {
        return match($this->tipo_envio) {
            'orden_pago' => 'Orden de Pago',
            'recordatorio_proximo' => 'Recordatorio Próximo',
            'recordatorio_vencido' => 'Recordatorio Vencido',
            'auth' => 'Autenticación',
            'recuperacion_password' => 'Recuperación de Contraseña',
            'factura_electronica' => 'Factura Electrónica',
            'confirmacion_pago' => 'Confirmación de Pago',
            'servicio_suspendido' => 'Servicio Suspendido',
            'servicio_renovado' => 'Servicio Renovado',
            'servicio_activado' => 'Servicio Activado',
            'comunicacion' => 'Comunicación',
            default => $this->tipo_envio,
        };
    }

    /**
     * Obtener el nombre formateado del estado
     */
    public function getEstadoNombreAttribute(): string
    {
        return match($this->estado) {
            'enviado' => 'Enviado',
            'pendiente' => 'Pendiente',
            'error' => 'Error',
            default => $this->estado,
        };
    }

    /**
     * Obtener la clase CSS para el badge del tipo de envío
     */
    public function getTipoBadgeClassAttribute(): string
    {
        return match($this->tipo_envio) {
            'orden_pago' => 'bg-blue-100 text-blue-800',
            'recordatorio_proximo' => 'bg-yellow-100 text-yellow-800',
            'recordatorio_vencido' => 'bg-red-100 text-red-800',
            'comunicacion' => 'bg-purple-100 text-purple-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Obtener la clase CSS para el badge del estado
     */
    public function getEstadoBadgeClassAttribute(): string
    {
        return match($this->estado) {
            'enviado' => 'bg-green-100 text-green-800',
            'pendiente' => 'bg-yellow-100 text-yellow-800',
            'error' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }
}
