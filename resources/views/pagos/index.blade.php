@extends('layouts.app')

@section('title', 'Gestión de Pagos')

@section('content')
<div class="space-y-6" x-data="pagoManager()">
    <!-- Estadísticas del Mes Actual -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Total Recaudado -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 truncate">Total Recaudado</p>
                        <p class="text-2xl font-semibold text-gray-900">S/ {{ number_format($estadisticasMes->total_recaudado, 2) }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ now()->locale('es')->isoFormat('MMMM YYYY') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cantidad de Pagos -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 truncate">Cantidad de Pagos</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $estadisticasMes->cantidad_pagos }}</p>
                        <p class="text-xs text-gray-400 mt-1">transacciones</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pago Promedio -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 truncate">Pago Promedio</p>
                        <p class="text-2xl font-semibold text-gray-900">S/ {{ number_format($estadisticasMes->pago_promedio, 2) }}</p>
                        <p class="text-xs text-gray-400 mt-1">por transacción</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mayor Pago -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 truncate">Mayor Pago</p>
                        <p class="text-2xl font-semibold text-gray-900">S/ {{ number_format($estadisticasMes->mayor_pago, 2) }}</p>
                        <p class="text-xs text-gray-400 mt-1">máximo registrado</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial de Pagos -->
    <div class="bg-white shadow-sm rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                Historial de Pagos
            </h2>
        </div>

        <!-- Buscador y Filtros -->
        <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
            <form method="GET" action="{{ route('pagos.index') }}" id="pagosFilterForm">
                <!-- Buscador Instantáneo -->
                <div class="mb-4">
                    <input type="text"
                           name="search"
                           value="{{ request('search') }}"
                           placeholder="Buscar por RUC, razón social o nombre comercial..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                           @input="
                               clearTimeout(debounceTimer);
                               debounceTimer = setTimeout(() => {
                                   document.getElementById('pagosFilterForm').submit();
                               }, 500);
                           ">
                </div>

                <!-- Filtros -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Mes</label>
                        <select name="mes"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @for($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" {{ request('mes', now()->month) == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::create()->month($m)->locale('es')->isoFormat('MMMM') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Año</label>
                        <select name="anio"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                onchange="this.form.submit()">
                            @for($y = now()->year; $y >= now()->year - 5; $y--)
                                <option value="{{ $y }}" {{ request('anio', now()->year) == $y ? 'selected' : '' }}>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Método</label>
                        <select name="metodo"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                onchange="this.form.submit()">
                            <option value="">Todos</option>
                            <option value="transferencia" {{ request('metodo') == 'transferencia' ? 'selected' : '' }}>Transferencia</option>
                            <option value="deposito" {{ request('metodo') == 'deposito' ? 'selected' : '' }}>Depósito</option>
                            <option value="yape" {{ request('metodo') == 'yape' ? 'selected' : '' }}>Yape</option>
                            <option value="plin" {{ request('metodo') == 'plin' ? 'selected' : '' }}>Plin</option>
                            <option value="efectivo" {{ request('metodo') == 'efectivo' ? 'selected' : '' }}>Efectivo</option>
                            <option value="otro" {{ request('metodo') == 'otro' ? 'selected' : '' }}>Otro</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1">Banco</label>
                        <select name="banco"
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                onchange="this.form.submit()">
                            <option value="">Todos</option>
                            @foreach($bancos as $banco)
                                <option value="{{ $banco }}" {{ request('banco') == $banco ? 'selected' : '' }}>
                                    {{ $banco }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabla de Pagos -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RUC</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Método</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N° Operación</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Banco</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($pagos as $pago)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">
                                #{{ $pago->id }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $pago->fecha_pago->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                {{ $pago->cliente->razon_social }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $pago->cliente->ruc }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold text-gray-900">
                                S/ {{ number_format($pago->monto_pagado, 2) }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($pago->metodo_pago == 'transferencia') bg-blue-100 text-blue-800
                                    @elseif($pago->metodo_pago == 'yape') bg-purple-100 text-purple-800
                                    @elseif($pago->metodo_pago == 'plin') bg-pink-100 text-pink-800
                                    @elseif($pago->metodo_pago == 'efectivo') bg-green-100 text-green-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($pago->metodo_pago) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $pago->numero_operacion ?? '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-600">
                                {{ $pago->banco ?? '-' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                <button @click="verDetalle({{ $pago->id }})"
                                        class="text-blue-600 hover:text-blue-900">Ver</button>
                                <a href="{{ route('pagos.edit', $pago) }}"
                                   class="text-indigo-600 hover:text-indigo-900">Editar</a>
                                <form action="{{ route('pagos.destroy', $pago) }}"
                                      method="POST"
                                      class="inline"
                                      onsubmit="return confirm('¿Estás seguro de eliminar este pago?')">
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
                            <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">
                                No se encontraron pagos
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($pagos->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $pagos->links() }}
            </div>
        @endif
    </div>

    <!-- Modal de Detalles del Pago -->
    <div x-show="modalAbierto"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         aria-labelledby="modal-title"
         role="dialog"
         aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div x-show="modalAbierto"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 @click="cerrarModal()"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <!-- Modal Panel -->
            <div x-show="modalAbierto"
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">

                <!-- Header -->
                <div class="bg-white px-6 pt-5 pb-4">
                    <div class="flex items-start justify-between">
                        <h3 class="text-lg leading-6 font-semibold text-gray-900" x-text="'Detalles del Pago #' + (pagoSeleccionado?.id || '')"></h3>
                        <button @click="cerrarModal()" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 pb-6 space-y-4" x-show="pagoSeleccionado">
                    <!-- Cliente Info -->
                    <div class="border-b pb-4">
                        <p class="text-sm text-gray-500 mb-1">👤 Cliente</p>
                        <p class="font-semibold text-gray-900" x-text="pagoSeleccionado?.cliente?.razon_social"></p>
                        <p class="text-sm text-gray-600">RUC: <span x-text="pagoSeleccionado?.cliente?.ruc"></span></p>
                        <p class="text-sm text-gray-600">WhatsApp: <span x-text="pagoSeleccionado?.cliente?.whatsapp || '-'"></span></p>
                    </div>

                    <!-- Monto -->
                    <div>
                        <p class="text-sm text-gray-500 mb-1">💰 Monto Pagado</p>
                        <p class="text-2xl font-bold text-gray-900">
                            S/ <span x-text="Number(pagoSeleccionado?.monto_pagado || 0).toFixed(2)"></span>
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Fecha de Pago -->
                        <div>
                            <p class="text-sm text-gray-500 mb-1">📅 Fecha de Pago</p>
                            <p class="font-medium text-gray-900" x-text="formatearFecha(pagoSeleccionado?.fecha_pago)"></p>
                        </div>

                        <!-- Método de Pago -->
                        <div>
                            <p class="text-sm text-gray-500 mb-1">💳 Método de Pago</p>
                            <p class="font-medium text-gray-900 capitalize" x-text="pagoSeleccionado?.metodo_pago"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- N° Operación -->
                        <div>
                            <p class="text-sm text-gray-500 mb-1">🧾 N° Operación</p>
                            <p class="font-medium text-gray-900" x-text="pagoSeleccionado?.numero_operacion || '-'"></p>
                        </div>

                        <!-- Banco -->
                        <div>
                            <p class="text-sm text-gray-500 mb-1">🏦 Banco</p>
                            <p class="font-medium text-gray-900" x-text="pagoSeleccionado?.banco || '-'"></p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Registrado por -->
                        <div>
                            <p class="text-sm text-gray-500 mb-1">👤 Registrado por</p>
                            <p class="font-medium text-gray-900" x-text="pagoSeleccionado?.registrado_por || 'Sistema'"></p>
                        </div>

                        <!-- Fecha de Registro -->
                        <div>
                            <p class="text-sm text-gray-500 mb-1">🕐 Fecha de Registro</p>
                            <p class="font-medium text-gray-900 text-sm" x-text="formatearFechaHora(pagoSeleccionado?.fecha_registro)"></p>
                        </div>
                    </div>

                    <!-- Observaciones -->
                    <div x-show="pagoSeleccionado?.observaciones">
                        <p class="text-sm text-gray-500 mb-1">📝 Observaciones</p>
                        <p class="text-sm text-gray-900" x-text="pagoSeleccionado?.observaciones"></p>
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 flex justify-end">
                    <button @click="cerrarModal()"
                            class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300 transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function pagoManager() {
    return {
        debounceTimer: null,
        modalAbierto: false,
        pagoSeleccionado: null,

        async verDetalle(pagoId) {
            try {
                const response = await fetch(`/pagos/${pagoId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.pagoSeleccionado = data.pago;
                    this.modalAbierto = true;
                }
            } catch (error) {
                console.error('Error al cargar detalles del pago:', error);
                alert('Error al cargar los detalles del pago');
            }
        },

        cerrarModal() {
            this.modalAbierto = false;
            setTimeout(() => {
                this.pagoSeleccionado = null;
            }, 300);
        },

        formatearFecha(fecha) {
            if (!fecha) return '-';
            const date = new Date(fecha);
            return date.toLocaleDateString('es-PE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        },

        formatearFechaHora(fecha) {
            if (!fecha) return '-';
            const date = new Date(fecha);
            return date.toLocaleString('es-PE', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: true
            });
        }
    }
}
</script>

<style>
[x-cloak] {
    display: none !important;
}
</style>
@endsection
