<div x-data="{
    abierto: @entangle('mostrarDropdown'),
    cerrarConDelay() {
        setTimeout(() => { this.abierto = false; $wire.mostrarDropdown = false; }, 200);
    }
}"
class="relative w-full">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
            Cliente @if($required)<span class="text-red-600">*</span>@endif
        </label>

        <div class="relative">
            <!-- Input de búsqueda -->
            <input
                type="text"
                wire:model.live.debounce.300ms="busqueda"
                @focus="abierto = $wire.mostrarDropdown"
                @blur="cerrarConDelay()"
                placeholder="Buscar por RUC o Razón Social..."
                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 pr-10"
                {{ $required ? 'required' : '' }}
                autocomplete="off"
            >

            <!-- Input hidden con el ID del cliente -->
            <input type="hidden" name="cliente_id" value="{{ $clienteId ?? '' }}">

            <!-- Icono de búsqueda / limpiar -->
            <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                @if($clienteSeleccionado)
                    <button
                        type="button"
                        wire:click="limpiarSeleccion"
                        class="text-gray-400 hover:text-gray-600"
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                @else
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                @endif
            </div>

            <!-- Dropdown de resultados -->
            <div
                x-show="abierto && {{ count($clientes) > 0 ? 'true' : 'false' }}"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform opacity-0 scale-95"
                x-transition:enter-end="transform opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform opacity-100 scale-100"
                x-transition:leave-end="transform opacity-0 scale-95"
                class="absolute z-50 mt-1 w-full bg-white shadow-lg max-h-60 rounded-md py-1 text-base ring-1 ring-black ring-opacity-5 overflow-auto focus:outline-none sm:text-sm"
                style="display: none;"
            >
                @forelse($clientes as $cliente)
                    <button
                        type="button"
                        wire:click="seleccionarCliente({{ $cliente->id }})"
                        class="w-full text-left px-4 py-2 hover:bg-blue-50 cursor-pointer transition-colors {{ $clienteSeleccionado?->id === $cliente->id ? 'bg-blue-100' : '' }}"
                    >
                        <div class="font-medium text-gray-900">{{ $cliente->razon_social }}</div>
                        <div class="text-sm text-gray-600">RUC: {{ $cliente->ruc }}</div>
                    </button>
                @empty
                    <div class="px-4 py-2 text-gray-500 text-sm">
                        No se encontraron clientes
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Cliente seleccionado (vista previa) -->
        @if($clienteSeleccionado)
            <div class="mt-2 p-3 bg-blue-50 rounded-lg border border-blue-200">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="font-medium text-gray-900">{{ $clienteSeleccionado->razon_social }}</div>
                        <div class="text-sm text-gray-600">RUC: {{ $clienteSeleccionado->ruc }}</div>
                    </div>
                    <button
                        type="button"
                        wire:click="limpiarSeleccion"
                        class="ml-2 text-gray-400 hover:text-gray-600"
                        title="Cambiar cliente"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
            </div>
        @endif

        <!-- Mensaje de ayuda -->
        @if(!$clienteSeleccionado && strlen($busqueda) > 0 && strlen($busqueda) < 2)
            <p class="mt-1 text-sm text-gray-500">Escribe al menos 2 caracteres para buscar</p>
        @endif

        <!-- Error de validación -->
        @error('cliente_id')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>
