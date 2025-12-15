@extends('layouts.app')

@section('title', 'Detalle del Servicio')

@section('actions')
    <div class="flex gap-2">
        @if($servicio->estado === 'activo')
            <button type="button" onclick="document.getElementById('suspender-form').classList.toggle('hidden')"
                    class="px-3 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700">
                Suspender
            </button>
        @endif

        @if(in_array($servicio->estado, ['suspendido', 'vencido']))
            <button type="button" onclick="document.getElementById('reactivar-form').classList.toggle('hidden')"
                    class="px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                Reactivar
            </button>
        @endif

        <a href="{{ route('servicios.edit', $servicio) }}"
           class="px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            Editar
        </a>

        <a href="{{ route('servicios.index') }}"
           class="px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
            Volver
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Información Principal -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex justify-between items-start mb-4">
            <h2 class="text-xl font-semibold text-gray-900">Información del Servicio</h2>
            @php
                $estadoClasses = [
                    'activo' => 'bg-green-100 text-green-800',
                    'suspendido' => 'bg-yellow-100 text-yellow-800',
                    'vencido' => 'bg-red-100 text-red-800',
                    'cancelado' => 'bg-gray-100 text-gray-800',
                ];
                $class = $estadoClasses[$servicio->estado] ?? 'bg-gray-100 text-gray-800';
            @endphp
            <span class="px-3 py-1 text-sm font-medium rounded-full {{ $class }}">
                {{ ucfirst($servicio->estado) }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-3">Cliente</h3>
                <div class="space-y-2">
                    <div>
                        <span class="text-sm text-gray-600">Razón Social:</span>
                        <p class="text-sm font-medium text-gray-900">{{ $servicio->cliente->razon_social }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">RUC:</span>
                        <p class="text-sm font-medium text-gray-900">{{ $servicio->cliente->ruc }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">WhatsApp:</span>
                        <p class="text-sm font-medium text-gray-900">{{ $servicio->cliente->whatsapp }}</p>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-3">Servicio</h3>
                <div class="space-y-2">
                    <div>
                        <span class="text-sm text-gray-600">Nombre:</span>
                        <p class="text-sm font-medium text-gray-900">{{ $servicio->catalogoServicio->nombre }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Categoría:</span>
                        <p class="text-sm font-medium text-gray-900">{{ $servicio->catalogoServicio->categoria }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Descripción:</span>
                        <p class="text-sm text-gray-900">{{ $servicio->catalogoServicio->descripcion }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalles de Facturación -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Facturación</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <span class="text-sm text-gray-600">Precio:</span>
                <p class="text-lg font-bold text-gray-900">{{ $servicio->moneda }} {{ number_format($servicio->precio, 2) }}</p>
            </div>
            <div>
                <span class="text-sm text-gray-600">Periodo:</span>
                <p class="text-sm font-medium text-gray-900">{{ ucfirst($servicio->periodo_facturacion) }}</p>
            </div>
            <div>
                <span class="text-sm text-gray-600">Auto Renovación:</span>
                <p class="text-sm font-medium text-gray-900">{{ $servicio->auto_renovacion ? 'Sí' : 'No' }}</p>
            </div>
        </div>
    </div>

    <!-- Fechas -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Fechas</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <span class="text-sm text-gray-600">Fecha de Inicio:</span>
                <p class="text-sm font-medium text-gray-900">{{ $servicio->fecha_inicio->format('d/m/Y') }}</p>
            </div>
            <div>
                <span class="text-sm text-gray-600">Fecha de Vencimiento:</span>
                <p class="text-sm font-medium text-gray-900">{{ $servicio->fecha_vencimiento->format('d/m/Y') }}</p>
            </div>
            @if($servicio->fecha_ultima_factura)
            <div>
                <span class="text-sm text-gray-600">Última Factura:</span>
                <p class="text-sm font-medium text-gray-900">{{ $servicio->fecha_ultima_factura->format('d/m/Y') }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Notas -->
    @if($servicio->notas)
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Notas</h3>
        <p class="text-sm text-gray-700">{{ $servicio->notas }}</p>
    </div>
    @endif

    <!-- Motivo de Suspensión -->
    @if($servicio->motivo_suspension)
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <h3 class="text-sm font-medium text-yellow-800 mb-1">Motivo de {{ $servicio->estado === 'cancelado' ? 'Cancelación' : 'Suspensión' }}</h3>
        <p class="text-sm text-yellow-700">{{ $servicio->motivo_suspension }}</p>
    </div>
    @endif

    <!-- Historial de Envíos -->
    @if($servicio->envios->count() > 0)
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Historial de Envíos</h3>
        <div class="space-y-3">
            @foreach($servicio->envios->take(10) as $envio)
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $envio->tipo_envio_nombre }}</p>
                        <p class="text-xs text-gray-500">{{ $envio->fecha_envio->format('d/m/Y H:i') }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded {{ $envio->estado_badge_class }}">
                        {{ $envio->estado_nombre }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<!-- Formulario Suspender (oculto) -->
<div id="suspender-form" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Suspender Servicio</h3>
        <form action="{{ route('servicios.suspender', $servicio) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Motivo de Suspensión *</label>
                <textarea name="motivo" rows="3" required
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('suspender-form').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-yellow-600 text-white rounded-md text-sm font-medium hover:bg-yellow-700">
                    Suspender
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Formulario Reactivar (oculto) -->
<div id="reactivar-form" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Reactivar Servicio</h3>
        <form action="{{ route('servicios.reactivar', $servicio) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Nueva Fecha de Vencimiento *</label>
                <input type="date" name="nueva_fecha_vencimiento" required
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('reactivar-form').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700">
                    Reactivar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
