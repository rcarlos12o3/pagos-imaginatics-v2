@extends('layouts.app')

@section('title', 'Gestión de Pagos')

@section('content')
<div class="space-y-6" x-data="pagoManager()" @ver-detalle-pago.window="verDetalle($event.detail.id)">
    <!-- Componente Livewire con todas las optimizaciones -->
    @livewire('pagos-table')

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
