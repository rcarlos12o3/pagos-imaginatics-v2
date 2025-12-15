@extends('layouts.app')

@section('title', 'Registrar Pago')

@section('actions')
    <a href="{{ route('pagos-pendientes.index') }}"
       class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Volver
    </a>
@endsection

@section('content')
<div class="max-w-3xl">
    <form action="{{ route('pagos.store') }}" method="POST" class="bg-white shadow-sm rounded-lg p-6 space-y-6" x-data="pagoForm()">
        @csrf

        <!-- Información del Cliente -->
        @if($cliente)
            <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
            <div class="bg-blue-50 rounded-lg p-4 space-y-2">
                <div>
                    <span class="text-sm font-medium text-blue-700">Cliente:</span>
                    <span class="text-sm text-blue-900">{{ $cliente->razon_social }}</span>
                </div>
                <div>
                    <span class="text-sm font-medium text-blue-700">RUC:</span>
                    <span class="text-sm text-blue-900">{{ $cliente->ruc }}</span>
                </div>
            </div>
        @endif

        <!-- Servicios a Pagar -->
        @if($serviciosSeleccionados->count() > 0)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Servicios a Pagar</label>
                <div class="space-y-2">
                    @foreach($serviciosSeleccionados as $servicio)
                        <div class="flex items-center p-3 bg-gray-50 rounded-lg">
                            <input type="checkbox"
                                   name="servicios_pagados[]"
                                   value="{{ $servicio->id }}"
                                   @if($serviciosSeleccionados->count() == 1) checked @endif
                                   x-model="serviciosSeleccionados"
                                   @change="calcularTotal()"
                                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <label class="ml-3 flex-1">
                                <div class="text-sm font-medium text-gray-900">{{ $servicio->catalogoServicio->nombre }}</div>
                                <div class="text-sm text-gray-500">
                                    {{ $servicio->moneda }} {{ number_format($servicio->precio, 2) }} -
                                    {{ ucfirst($servicio->periodo_facturacion) }} -
                                    Vence: {{ $servicio->fecha_vencimiento->format('d/m/Y') }}
                                </div>
                            </label>
                            <div class="text-sm font-bold text-gray-900" x-data="{ precio: {{ $servicio->precio }}, moneda: '{{ $servicio->moneda }}' }">
                                <span x-text="moneda"></span> <span x-text="precio.toFixed(2)"></span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Monto Pagado -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Monto Pagado *</label>
            <input type="number" step="0.01" name="monto_pagado" value="{{ old('monto_pagado') }}"
                   x-model="montoPagado"
                   required min="0"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('monto_pagado')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-sm text-gray-500" x-show="montoSugerido > 0">
                Monto sugerido: <span x-text="'S/ ' + montoSugerido.toFixed(2)" class="font-medium"></span>
            </p>
        </div>

        <!-- Fecha de Pago -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Pago *</label>
            <input type="date" name="fecha_pago" value="{{ old('fecha_pago', date('Y-m-d')) }}" required
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('fecha_pago')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Método de Pago -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pago *</label>
            <select name="metodo_pago" required x-model="metodoPago"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Seleccionar método</option>
                <option value="transferencia">Transferencia</option>
                <option value="deposito">Depósito</option>
                <option value="yape">Yape</option>
                <option value="plin">Plin</option>
                <option value="efectivo">Efectivo</option>
                <option value="otro">Otro</option>
            </select>
            @error('metodo_pago')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Número de Operación (solo si es transferencia, depósito, yape o plin) -->
        <div x-show="['transferencia', 'deposito', 'yape', 'plin'].includes(metodoPago)">
            <label class="block text-sm font-medium text-gray-700 mb-1">Número de Operación</label>
            <input type="text" name="numero_operacion" value="{{ old('numero_operacion') }}" maxlength="50"
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('numero_operacion')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Banco (solo si es transferencia, depósito, yape o plin) -->
        <div x-show="['transferencia', 'deposito', 'yape', 'plin'].includes(metodoPago)">
            <label class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
            <select name="banco" x-model="banco"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Seleccionar banco...</option>
                <option value="BCP">BCP - Banco de Crédito del Perú</option>
                <option value="BBVA">BBVA</option>
                <option value="Interbank">Interbank</option>
                <option value="Scotiabank">Scotiabank</option>
                <option value="Banco de la Nación">Banco de la Nación</option>
                <option value="Banco Pichincha">Banco Pichincha</option>
                <option value="Banbif">Banbif</option>
                <option value="Otro">Otro</option>
            </select>
            @error('banco')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            <p class="mt-1 text-sm text-gray-500" x-show="metodoPago === 'yape'">
                Yape por defecto usa BCP
            </p>
            <p class="mt-1 text-sm text-gray-500" x-show="metodoPago === 'plin'">
                Plin por defecto usa Interbank
            </p>
        </div>

        <!-- Observaciones -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
            <textarea name="observaciones" rows="3"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('observaciones') }}</textarea>
            @error('observaciones')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Botones -->
        <div class="flex justify-end gap-4 pt-4 border-t">
            <a href="{{ route('pagos-pendientes.index') }}"
               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit"
                    class="px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700">
                Registrar Pago
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function pagoForm() {
    return {
        serviciosSeleccionados: @json($serviciosSeleccionados->pluck('id')->toArray()),
        metodoPago: '{{ old("metodo_pago") }}',
        banco: '{{ old("banco") }}',
        montoPagado: {{ old('monto_pagado', 0) }},
        montoSugerido: 0,

        init() {
            this.calcularTotal();
            this.$watch('metodoPago', () => this.actualizarBancoPorDefecto());
        },

        actualizarBancoPorDefecto() {
            if (this.metodoPago === 'yape') {
                this.banco = 'BCP';
            } else if (this.metodoPago === 'plin') {
                this.banco = 'Interbank';
            } else if (['efectivo', 'otro'].includes(this.metodoPago)) {
                this.banco = '';
            }
        },

        calcularTotal() {
            // Calcular monto sugerido basado en servicios seleccionados
            const servicios = @json($serviciosSeleccionados->map(function($s) {
                return ['id' => $s->id, 'precio' => $s->precio];
            }));

            this.montoSugerido = 0;
            this.serviciosSeleccionados.forEach(id => {
                const servicio = servicios.find(s => s.id == id);
                if (servicio) {
                    this.montoSugerido += parseFloat(servicio.precio);
                }
            });

            // Actualizar monto pagado si está vacío
            if (!this.montoPagado || this.montoPagado == 0) {
                this.montoPagado = this.montoSugerido;
            }
        }
    }
}
</script>
@endpush
@endsection
