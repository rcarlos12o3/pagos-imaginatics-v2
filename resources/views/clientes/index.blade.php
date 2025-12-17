@extends('layouts.app')

@section('title', 'Clientes')

@section('actions')
    <a href="{{ route('clientes.create') }}"
       class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nuevo Cliente
    </a>
@endsection

@section('content')
<div class="bg-white shadow-sm rounded-lg overflow-hidden" x-data="{ debounceTimer: null }">
    <!-- Tabs/Filters -->
    <div class="border-b border-gray-200">
        <div class="px-6 py-4">
            <form method="GET" action="{{ route('clientes.index') }}" id="clientesFilterForm" class="flex gap-4">
            <input type="text"
                   name="search"
                   value="{{ request('search') }}"
                   placeholder="Buscar por razón social, RUC o WhatsApp..."
                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                   @input="
                       clearTimeout(debounceTimer);
                       debounceTimer = setTimeout(() => {
                           document.getElementById('clientesFilterForm').submit();
                       }, 500);
                   ">

            <select name="activo"
                    class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                    onchange="this.form.submit()">
                <option value="">Todos</option>
                <option value="1" {{ request('activo') == '1' ? 'selected' : '' }}>Activos</option>
                <option value="0" {{ request('activo') == '0' ? 'selected' : '' }}>Inactivos</option>
            </select>

            <button type="submit"
                    class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700">
                Buscar
            </button>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RUC</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Razón Social</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WhatsApp</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Servicio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimiento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($clientes as $cliente)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            {{ $cliente->ruc }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $cliente->razon_social }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $cliente->whatsapp }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                {{ ucfirst($cliente->tipo_servicio) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                            {{ $cliente->fecha_vencimiento?->format('d/m/Y') ?? '-' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            @if($cliente->activo)
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Activo
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Inactivo
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('clientes.show', $cliente) }}"
                               class="text-blue-600 hover:text-blue-900 mr-3">Ver</a>
                            <a href="{{ route('clientes.edit', $cliente) }}"
                               class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</a>
                            <form action="{{ route('clientes.destroy', $cliente) }}"
                                  method="POST"
                                  class="inline"
                                  onsubmit="return confirm('¿Estás seguro de eliminar este cliente?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900">
                                    Eliminar
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">
                            No se encontraron clientes
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($clientes->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $clientes->links() }}
        </div>
    @endif
</div>
@endsection
