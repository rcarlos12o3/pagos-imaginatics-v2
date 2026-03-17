<div>
    <!-- Estadísticas del Mes Actual -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6 min-h-[140px]">
        <!-- Total Recaudado -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg" wire:key="stat-total-recaudado">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 truncate">Total Recaudado</p>
                        <p class="text-2xl font-semibold text-gray-900 transition-all duration-300">S/ {{ number_format($this->estadisticasMes->total_recaudado, 2) }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ now()->locale('es')->isoFormat('MMMM YYYY') }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cantidad de Pagos -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg" wire:key="stat-cantidad-pagos">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 truncate">Cantidad de Pagos</p>
                        <p class="text-2xl font-semibold text-gray-900 transition-all duration-300">{{ $this->estadisticasMes->cantidad_pagos }}</p>
                        <p class="text-xs text-gray-400 mt-1">transacciones</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pago Promedio -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg" wire:key="stat-pago-promedio">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 truncate">Pago Promedio</p>
                        <p class="text-2xl font-semibold text-gray-900 transition-all duration-300">S/ {{ number_format($this->estadisticasMes->pago_promedio, 2) }}</p>
                        <p class="text-xs text-gray-400 mt-1">por transacción</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mayor Pago -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg" wire:key="stat-mayor-pago">
            <div class="p-6">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <div class="ml-4 flex-1">
                        <p class="text-sm font-medium text-gray-500 truncate">Mayor Pago</p>
                        <p class="text-2xl font-semibold text-gray-900 transition-all duration-300">S/ {{ number_format($this->estadisticasMes->mayor_pago, 2) }}</p>
                        <p class="text-xs text-gray-400 mt-1">máximo registrado</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Indicador de carga sutil en la esquina -->
    <div wire:loading.delay.longer wire:target="search,mes,anio,metodo,banco"
         class="fixed top-20 right-6 z-50">
        <div class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow-lg flex items-center space-x-2 animate-pulse">
            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span class="text-sm font-medium">Actualizando...</span>
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
            <!-- Buscador Instantáneo -->
            <div class="mb-4">
                <input type="text"
                       wire:model.live.debounce.500ms="search"
                       placeholder="Buscar por RUC, razón social o nombre comercial..."
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>

            <!-- Filtros -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Mes</label>
                    <select wire:model.live.debounce.300ms="mes"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Todos</option>
                        @for($m = 1; $m <= 12; $m++)
                            <option value="{{ $m }}">
                                {{ \Carbon\Carbon::create()->month($m)->locale('es')->isoFormat('MMMM') }}
                            </option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Año</label>
                    <select wire:model.live.debounce.300ms="anio"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        @for($y = now()->year; $y >= now()->year - 5; $y--)
                            <option value="{{ $y }}">{{ $y }}</option>
                        @endfor
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Método</label>
                    <select wire:model.live.debounce.300ms="metodo"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Todos</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="deposito">Depósito</option>
                        <option value="yape">Yape</option>
                        <option value="plin">Plin</option>
                        <option value="efectivo">Efectivo</option>
                        <option value="otro">Otro</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Banco</label>
                    <select wire:model.live.debounce.300ms="banco"
                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                        <option value="">Todos</option>
                        @foreach($this->bancos as $bancoItem)
                            <option value="{{ $bancoItem }}">{{ $bancoItem }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
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
                        <tr class="hover:bg-gray-50 transition-colors duration-150"
                            wire:key="pago-{{ $pago->id }}">
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
                                <button @click="$dispatch('ver-detalle-pago', { id: {{ $pago->id }} })"
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
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-12 h-12 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    <p class="text-lg font-medium">No se encontraron pagos</p>
                                    @if($search || $metodo || $banco)
                                        <p class="text-sm mt-1">Intenta cambiar los filtros de búsqueda</p>
                                    @endif
                                </div>
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
</div>
