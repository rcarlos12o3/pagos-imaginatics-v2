@extends('layouts.app')

@section('title', 'Editar Servicio')

@section('actions')
    <a href="{{ route('servicios.show', $servicio) }}"
       class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Volver
    </a>
@endsection

@section('content')
<div class="max-w-3xl">
    <form action="{{ route('servicios.update', $servicio) }}" method="POST" class="bg-white shadow-sm rounded-lg p-6 space-y-6">
        @csrf
        @method('PUT')

        <!-- Info Cliente y Servicio (No editable) -->
        <div class="bg-gray-50 rounded-lg p-4 space-y-2">
            <div>
                <span class="text-sm font-medium text-gray-700">Cliente:</span>
                <span class="text-sm text-gray-900">{{ $servicio->cliente->razon_social }}</span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-700">Servicio:</span>
                <span class="text-sm text-gray-900">{{ $servicio->catalogoServicio->nombre }}</span>
            </div>
            <div>
                <span class="text-sm font-medium text-gray-700">Categoría:</span>
                <span class="text-sm text-gray-900">{{ $servicio->catalogoServicio->categoria }}</span>
            </div>
        </div>

        <!-- Precio y Moneda -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Precio *</label>
                <input type="number" step="0.01" name="precio" value="{{ old('precio', $servicio->precio) }}" required min="0"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('precio')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Moneda *</label>
                <select name="moneda" required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="PEN" {{ $servicio->moneda == 'PEN' ? 'selected' : '' }}>PEN (Soles)</option>
                    <option value="USD" {{ $servicio->moneda == 'USD' ? 'selected' : '' }}>USD (Dólares)</option>
                </select>
                @error('moneda')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <!-- Periodo de Facturación -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Periodo de Facturación *</label>
            <select name="periodo_facturacion" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="mensual" {{ $servicio->periodo_facturacion == 'mensual' ? 'selected' : '' }}>Mensual</option>
                <option value="trimestral" {{ $servicio->periodo_facturacion == 'trimestral' ? 'selected' : '' }}>Trimestral</option>
                <option value="semestral" {{ $servicio->periodo_facturacion == 'semestral' ? 'selected' : '' }}>Semestral</option>
                <option value="anual" {{ $servicio->periodo_facturacion == 'anual' ? 'selected' : '' }}>Anual</option>
            </select>
            @error('periodo_facturacion')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Fecha de Vencimiento -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Vencimiento *</label>
            <input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento', $servicio->fecha_vencimiento->format('Y-m-d')) }}" required
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            @error('fecha_vencimiento')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Auto Renovación -->
        <div class="flex items-center">
            <input type="checkbox" name="auto_renovacion" value="1" {{ old('auto_renovacion', $servicio->auto_renovacion) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <label class="ml-2 block text-sm text-gray-900">Renovación Automática</label>
        </div>

        <!-- Notas -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
            <textarea name="notas" rows="3"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notas', $servicio->notas) }}</textarea>
            @error('notas')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Botones -->
        <div class="flex justify-end gap-4 pt-4 border-t">
            <a href="{{ route('servicios.show', $servicio) }}"
               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                Actualizar Servicio
            </button>
        </div>
    </form>
</div>
@endsection
