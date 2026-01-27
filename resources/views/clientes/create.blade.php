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

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Tipo Documento *</label>
                <select name="tipo_documento" required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="RUC" selected>RUC</option>
                    <option value="DNI">DNI</option>
                    <option value="CE">CE</option>
                    <option value="PASAPORTE">PASAPORTE</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Número *</label>
                <div class="flex gap-2">
                    <input type="text" name="ruc" x-model="ruc" value="{{ old('ruc') }}" required
                           maxlength="11" placeholder="20123456789"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                           @keyup.enter="consultarRuc()">
                    <button type="button" @click="consultarRuc()"
                            :disabled="consultando || ruc.length !== 11"
                            class="px-3 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors whitespace-nowrap">
                        <span x-show="!consultando">🔍</span>
                        <span x-show="consultando">⏳</span>
                    </button>
                </div>
                <p x-show="mensaje" :class="mensajeExito ? 'text-green-600' : 'text-red-600'" class="text-xs mt-1" x-text="mensaje"></p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">WhatsApp *</label>
                <div class="flex">
                    <select name="codigo_pais" id="codigo_pais"
                            class="rounded-l-md border-r-0 border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm bg-gray-50 w-24">
                        <option value="51" selected>🇵🇪 +51</option>
                        <option value="1">🇺🇸 +1</option>
                        <option value="54">🇦🇷 +54</option>
                        <option value="55">🇧🇷 +55</option>
                        <option value="56">🇨🇱 +56</option>
                        <option value="57">🇨🇴 +57</option>
                        <option value="52">🇲🇽 +52</option>
                        <option value="593">🇪🇨 +593</option>
                        <option value="591">🇧🇴 +591</option>
                        <option value="34">🇪🇸 +34</option>
                    </select>
                    <input type="text" id="whatsapp_numero" value="{{ old('whatsapp_numero') }}" required
                           placeholder="999999999" maxlength="15"
                           class="block w-full rounded-r-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <input type="hidden" name="whatsapp" id="whatsapp_completo" value="{{ old('whatsapp') }}">
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Razón Social *</label>
                <input type="text" name="razon_social" x-model="razonSocial" value="{{ old('razon_social') }}" required
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre Comercial</label>
                <input type="text" name="nombre_comercial" value="{{ old('nombre_comercial') }}"
                       placeholder="Opcional"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}"
                       placeholder="contacto@empresa.com"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                <select name="activo" required
                        class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="1" selected>Activo</option>
                    <option value="0">Inactivo</option>
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nombre de Contacto</label>
                <input type="text" name="contacto_nombre" value="{{ old('contacto_nombre') }}"
                       placeholder="Opcional"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cargo</label>
                <input type="text" name="contacto_cargo" value="{{ old('contacto_cargo') }}"
                       placeholder="Opcional"
                       class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Dirección</label>
            <textarea name="direccion" rows="2" placeholder="Opcional"
                      class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">{{ old('direccion') }}</textarea>
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
// Concatenar código + número
function actualizarWhatsapp() {
    const codigo = document.getElementById('codigo_pais').value;
    const numero = document.getElementById('whatsapp_numero').value.replace(/\D/g, '');
    document.getElementById('whatsapp_completo').value = codigo + numero;
}

document.getElementById('codigo_pais').addEventListener('change', actualizarWhatsapp);
document.getElementById('whatsapp_numero').addEventListener('input', actualizarWhatsapp);
document.querySelector('form').addEventListener('submit', actualizarWhatsapp);

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
