@extends('layouts.app')

@section('title', 'Editar Pago')

@section('actions')
    <a href="{{ route('pagos.index') }}"
       class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Volver
    </a>
@endsection

@section('content')
<div class="max-w-3xl">
    <form action="{{ route('pagos.update', $pago) }}" method="POST" class="bg-white shadow-sm rounded-lg p-6 space-y-6">
        @csrf
        @method('PUT')

        <!-- Información del Cliente (solo lectura) -->
        <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-2">Cliente</h3>
            <p class="text-lg font-medium text-gray-900">{{ $pago->cliente->razon_social }}</p>
            <p class="text-sm text-gray-600">RUC: {{ $pago->cliente->ruc }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Monto Pagado -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Monto Pagado *</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500">S/</span>
                    <input type="number"
                           name="monto_pagado"
                           value="{{ old('monto_pagado', $pago->monto_pagado) }}"
                           step="0.01"
                           min="0"
                           required
                           class="block w-full pl-10 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>
                @error('monto_pagado')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Fecha de Pago -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Pago *</label>
                <input type="date"
                       name="fecha_pago"
                       value="{{ old('fecha_pago', $pago->fecha_pago->format('Y-m-d')) }}"
                       required
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                @error('fecha_pago')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Método de Pago -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Método de Pago *</label>
                <select name="metodo_pago"
                        required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="transferencia" {{ old('metodo_pago', $pago->metodo_pago) == 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                    <option value="deposito" {{ old('metodo_pago', $pago->metodo_pago) == 'deposito' ? 'selected' : '' }}>Depósito</option>
                    <option value="yape" {{ old('metodo_pago', $pago->metodo_pago) == 'yape' ? 'selected' : '' }}>Yape</option>
                    <option value="plin" {{ old('metodo_pago', $pago->metodo_pago) == 'plin' ? 'selected' : '' }}>Plin</option>
                    <option value="efectivo" {{ old('metodo_pago', $pago->metodo_pago) == 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                    <option value="otro" {{ old('metodo_pago', $pago->metodo_pago) == 'otro' ? 'selected' : '' }}>Otro</option>
                </select>
                @error('metodo_pago')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Banco -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Banco</label>
                <input type="text"
                       name="banco"
                       value="{{ old('banco', $pago->banco) }}"
                       placeholder="Ej: BCP, Scotiabank, Interbank"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                @error('banco')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Número de Operación -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Número de Operación</label>
            <input type="text"
                   name="numero_operacion"
                   value="{{ old('numero_operacion', $pago->numero_operacion) }}"
                   placeholder="Código de transacción o referencia"
                   class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            @error('numero_operacion')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Observaciones -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
            <textarea name="observaciones"
                      rows="3"
                      placeholder="Notas adicionales sobre el pago..."
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">{{ old('observaciones', $pago->observaciones) }}</textarea>
            @error('observaciones')
                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Información de Registro (solo lectura) -->
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
            <h3 class="text-sm font-semibold text-blue-900 mb-2">Información de Registro</h3>
            <div class="grid grid-cols-2 gap-2 text-sm">
                <div>
                    <span class="text-blue-700">Registrado por:</span>
                    <span class="text-blue-900 font-medium">{{ $pago->registrado_por ?? 'Sistema' }}</span>
                </div>
                <div>
                    <span class="text-blue-700">Fecha de registro:</span>
                    <span class="text-blue-900 font-medium">{{ $pago->fecha_registro ? $pago->fecha_registro->format('d/m/Y H:i:s') : '-' }}</span>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex justify-end gap-4 pt-4 border-t border-gray-200">
            <a href="{{ route('pagos.index') }}"
               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                Actualizar Pago
            </button>
        </div>
    </form>
</div>
@endsection
