@extends('layouts.app')

@section('title', 'Servicios Contratados')

@section('actions')
    <a href="{{ route('servicios.create') }}"
       class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo Servicio
    </a>
@endsection

@section('content')
<!-- Estadísticas -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-3">
        <div class="text-gray-500 text-xs font-medium">Total Contratos</div>
        <div class="text-xl font-bold text-gray-900 mt-1">{{ $estadisticas['total_contratos'] ?? 0 }}</div>
    </div>
    <div class="bg-green-50 rounded-lg shadow-sm p-3">
        <div class="text-green-600 text-xs font-medium">Activos</div>
        <div class="text-xl font-bold text-green-700 mt-1">{{ $estadisticas['activos'] ?? 0 }}</div>
    </div>
    <div class="bg-yellow-50 rounded-lg shadow-sm p-3">
        <div class="text-yellow-600 text-xs font-medium">Suspendidos</div>
        <div class="text-xl font-bold text-yellow-700 mt-1">{{ $estadisticas['suspendidos'] ?? 0 }}</div>
    </div>
    <div class="bg-red-50 rounded-lg shadow-sm p-3">
        <div class="text-red-600 text-xs font-medium">Vencidos</div>
        <div class="text-xl font-bold text-red-700 mt-1">{{ $estadisticas['vencidos'] ?? 0 }}</div>
    </div>
    <div class="bg-gray-50 rounded-lg shadow-sm p-3">
        <div class="text-gray-600 text-xs font-medium">Cancelados</div>
        <div class="text-xl font-bold text-gray-700 mt-1">{{ $estadisticas['cancelados'] ?? 0 }}</div>
    </div>
</div>

<!-- Filtros -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <form method="GET" action="{{ route('servicios.index') }}" class="space-y-4">
        <!-- Búsqueda -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar por RUC o Razón Social</label>
            <input type="text" name="buscar" value="{{ request('buscar') }}"
                   placeholder="Ingrese RUC o Razón Social..."
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <p class="mt-1 text-xs text-gray-500">Busca en toda la base de datos, no solo en la página actual</p>
        </div>

        <!-- Otros filtros -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                <select name="estado" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                    <option value="suspendido" {{ request('estado') == 'suspendido' ? 'selected' : '' }}>Suspendido</option>
                    <option value="vencido" {{ request('estado') == 'vencido' ? 'selected' : '' }}>Vencido</option>
                    <option value="cancelado" {{ request('estado') == 'cancelado' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Periodo</label>
                <select name="periodo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="mensual" {{ request('periodo') == 'mensual' ? 'selected' : '' }}>Mensual</option>
                    <option value="trimestral" {{ request('periodo') == 'trimestral' ? 'selected' : '' }}>Trimestral</option>
                    <option value="semestral" {{ request('periodo') == 'semestral' ? 'selected' : '' }}>Semestral</option>
                    <option value="anual" {{ request('periodo') == 'anual' ? 'selected' : '' }}>Anual</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                    Buscar
                </button>
                @if(request()->hasAny(['buscar', 'estado', 'periodo', 'cliente_id']))
                    <a href="{{ route('servicios.index') }}" class="px-4 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50">
                        Limpiar
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>

<!-- Tabla de Servicios -->
<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Servicio</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Precio</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periodo</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimiento</th>
                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse($servicios as $servicio)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $servicio->cliente->razon_social }}</div>
                        <div class="text-sm text-gray-500">RUC: {{ $servicio->cliente->ruc }}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">{{ $servicio->catalogoServicio->nombre }}</div>
                        <div class="text-sm text-gray-500">{{ $servicio->catalogoServicio->categoria }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">{{ $servicio->moneda }} {{ number_format($servicio->precio, 2) }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                            {{ ucfirst($servicio->periodo_facturacion) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                        {{ $servicio->fecha_vencimiento->format('d/m/Y') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $estadoClasses = [
                                'activo' => 'bg-green-100 text-green-800',
                                'suspendido' => 'bg-yellow-100 text-yellow-800',
                                'vencido' => 'bg-red-100 text-red-800',
                                'cancelado' => 'bg-gray-100 text-gray-800',
                            ];
                            $class = $estadoClasses[$servicio->estado] ?? 'bg-gray-100 text-gray-800';
                        @endphp
                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $class }}">
                            {{ ucfirst($servicio->estado) }}
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <a href="{{ route('servicios.show', $servicio) }}" class="text-blue-600 hover:text-blue-900 mr-3">Ver</a>
                        <a href="{{ route('servicios.edit', $servicio) }}" class="text-indigo-600 hover:text-indigo-900">Editar</a>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                        No hay servicios contratados
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
    </div>

    <!-- Paginación -->
    @if($servicios->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200">
            {{ $servicios->links() }}
        </div>
    @endif
</div>
@endsection
