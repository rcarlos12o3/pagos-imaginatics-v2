@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="space-y-6">
    <!-- Tarjetas de Métricas -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Clientes Activos -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Clientes Activos</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $totalClientes }}</p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Clientes Por Vencer -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Por Vencer (30 días)</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $clientesPorVencer }}</p>
                </div>
                <div class="bg-yellow-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Envíos Hoy -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Envíos Hoy</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $enviosHoy }}</p>
                </div>
                <div class="bg-green-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
                    </svg>
                </div>
            </div>
        </div>

        <!-- Envíos del Mes -->
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-purple-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Envíos del Mes</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ $enviosMes }}</p>
                </div>
                <div class="bg-purple-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Clientes por Tipo de Servicio -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Clientes por Tipo de Servicio</h2>
            <div class="space-y-3">
                @foreach($clientesPorServicio as $servicio)
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-3 h-3 rounded-full
                                @if($servicio->tipo_servicio == 'mensual') bg-blue-500
                                @elseif($servicio->tipo_servicio == 'trimestral') bg-green-500
                                @elseif($servicio->tipo_servicio == 'semestral') bg-yellow-500
                                @else bg-purple-500
                                @endif">
                            </div>
                            <span class="text-sm font-medium text-gray-700 capitalize">
                                {{ $servicio->tipo_servicio }}
                            </span>
                        </div>
                        <div class="flex items-center space-x-3">
                            <span class="text-sm font-bold text-gray-900">{{ $servicio->total }}</span>
                            <div class="w-32 bg-gray-200 rounded-full h-2">
                                <div class="
                                    @if($servicio->tipo_servicio == 'mensual') bg-blue-500
                                    @elseif($servicio->tipo_servicio == 'trimestral') bg-green-500
                                    @elseif($servicio->tipo_servicio == 'semestral') bg-yellow-500
                                    @else bg-purple-500
                                    @endif
                                    h-2 rounded-full"
                                    style="width: {{ ($servicio->total / $totalClientes) * 100 }}%">
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Últimos Clientes Registrados -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Últimos Clientes Registrados</h2>
            <div class="space-y-3">
                @forelse($ultimosClientes as $cliente)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                        <div>
                            <p class="text-sm font-medium text-gray-900">{{ $cliente->razon_social }}</p>
                            <p class="text-xs text-gray-500">RUC: {{ $cliente->ruc }}</p>
                        </div>
                        <span class="text-xs text-gray-500">
                            {{ $cliente->fecha_creacion->diffForHumans() }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">No hay clientes registrados</p>
                @endforelse
            </div>
            @if($ultimosClientes->count() > 0)
                <a href="{{ route('clientes.index') }}" class="block mt-4 text-sm text-blue-600 hover:text-blue-800 text-center">
                    Ver todos los clientes →
                </a>
            @endif
        </div>
    </div>

    <!-- Próximos Vencimientos -->
    @if($proximosVencimientos->count() > 0)
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Próximos Vencimientos (30 días)</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">RUC</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Servicio</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Vencimiento</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($proximosVencimientos as $cliente)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    {{ $cliente->razon_social }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $cliente->ruc }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ ucfirst($cliente->tipo_servicio) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                    {{ $cliente->fecha_vencimiento->format('d/m/Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $diasRestantes = now()->diffInDays($cliente->fecha_vencimiento, false);
                                    @endphp
                                    @if($diasRestantes <= 7)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            {{ $diasRestantes }} días
                                        </span>
                                    @elseif($diasRestantes <= 15)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            {{ $diasRestantes }} días
                                        </span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            {{ $diasRestantes }} días
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
@endsection
