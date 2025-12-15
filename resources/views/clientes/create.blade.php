@extends('layouts.app')

@section('title', 'Nuevo Cliente')

@section('actions')
    <a href="{{ route('clientes.index') }}"
       class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Volver
    </a>
@endsection

@section('content')
<div class="max-w-2xl" x-data="consultaRuc()">
    <form action="{{ route('clientes.store') }}" method="POST" class="bg-white shadow-sm rounded-lg p-6 space-y-4">
        @csrf

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">RUC *</label>
                <div class="flex gap-2">
                    <input type="text" name="ruc" x-model="ruc" value="{{ old('ruc') }}" required
                           maxlength="11"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           @keyup.enter="consultarRuc()">
                    <button type="button" @click="consultarRuc()"
                            :disabled="consultando || ruc.length !== 11"
                            class="mt-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors">
                        <span x-show="!consultando">🔍 Buscar</span>
                        <span x-show="consultando">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
                <p x-show="mensaje" :class="mensajeExito ? 'text-green-600' : 'text-red-600'" class="text-sm mt-1" x-text="mensaje"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Tipo Documento *</label>
                <select name="tipo_documento" required
                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="RUC">RUC</option>
                    <option value="DNI">DNI</option>
                    <option value="CE">CE</option>
                    <option value="PASAPORTE">PASAPORTE</option>
                </select>
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Razón Social *</label>
            <input type="text" name="razon_social" x-model="razonSocial" value="{{ old('razon_social') }}" required
                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">WhatsApp *</label>
                <input type="text" name="whatsapp" value="{{ old('whatsapp') }}" required
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Monto</label>
                <input type="number" step="0.01" name="monto" value="{{ old('monto') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Fecha Vencimiento</label>
                <input type="date" name="fecha_vencimiento" value="{{ old('fecha_vencimiento') }}"
                       class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Tipo de Servicio</label>
            <select name="tipo_servicio"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="mensual">Mensual</option>
                <option value="trimestral">Trimestral</option>
                <option value="semestral">Semestral</option>
                <option value="anual" selected>Anual</option>
            </select>
        </div>

        <div class="flex items-center">
            <input type="checkbox" name="activo" value="1" checked
                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <label class="ml-2 block text-sm text-gray-900">Cliente Activo</label>
        </div>

        <div class="flex justify-end gap-4 pt-4">
            <a href="{{ route('clientes.index') }}"
               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                Crear Cliente
            </button>
        </div>
    </form>
</div>

<script>
function consultaRuc() {
    return {
        ruc: '{{ old('ruc') }}',
        razonSocial: '{{ old('razon_social') }}',
        consultando: false,
        mensaje: '',
        mensajeExito: false,

        async consultarRuc() {
            if (this.ruc.length !== 11) {
                this.mostrarError('El RUC debe tener 11 dígitos');
                return;
            }

            this.consultando = true;
            this.mensaje = '';

            try {
                const response = await fetch(`/api/ruc/consultar?ruc=${this.ruc}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    this.razonSocial = data.data.nombre_o_razon_social;
                    this.mostrarExito(`Datos obtenidos desde ${data.source === 'cache' ? 'caché' : 'SUNAT'}`);
                } else {
                    this.mostrarError(data.error || 'Error al consultar RUC');
                }
            } catch (error) {
                this.mostrarError('Error de conexión');
            } finally {
                this.consultando = false;
            }
        },

        mostrarExito(msg) {
            this.mensaje = msg;
            this.mensajeExito = true;
            setTimeout(() => { this.mensaje = ''; }, 5000);
        },

        mostrarError(msg) {
            this.mensaje = msg;
            this.mensajeExito = false;
            setTimeout(() => { this.mensaje = ''; }, 5000);
        }
    }
}
</script>
@endsection
