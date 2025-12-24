@extends('layouts.app')

@section('title', 'Clientes')

@section('actions')
    <div class="flex gap-2">
        <button @click="$dispatch('open-modal-carga')"
                class="inline-flex items-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
            </svg>
            Carga Masiva
        </button>
        <a href="{{ route('clientes.create') }}"
           class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nuevo Cliente
        </a>
    </div>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
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
                            {{ $cliente->email ?? '-' }}
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
                        <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
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

<!-- Modal Carga Masiva -->
<div x-data="cargaMasivaApp()" @open-modal-carga.window="abrirModal()" x-show="modalAbierto" x-cloak
     class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" @click.self="cerrarModal()">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full flex flex-col" style="max-height: 90vh;">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between flex-shrink-0">
            <h3 class="text-lg font-semibold text-gray-900">Carga Masiva de Clientes</h3>
            <button @click="cerrarModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Body -->
        <div class="p-6 overflow-y-auto flex-1">
            <!-- Paso 1: Subir Archivo -->
            <div x-show="paso === 1">
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-4">
                        Carga un archivo CSV o Excel con los datos de los clientes.
                    </p>
                    <a href="{{ route('clientes.plantilla') }}"
                       class="inline-flex items-center px-3 py-2 bg-blue-100 text-blue-700 text-sm rounded-lg hover:bg-blue-200 mb-4">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Descargar Plantilla CSV
                    </a>
                </div>

                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center">
                    <input type="file" @change="cargarArchivo($event)" accept=".csv,.xlsx,.xls" id="archivoInput" class="hidden">
                    <label for="archivoInput" class="cursor-pointer">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="mt-2 text-sm text-gray-600">
                            <span class="text-blue-600 hover:text-blue-700 font-medium">Click para seleccionar archivo</span>
                            o arrastra aquí
                        </p>
                        <p class="mt-1 text-xs text-gray-500">CSV, XLSX o XLS (máximo 2MB)</p>
                    </label>
                    <p x-show="nombreArchivo" class="mt-3 text-sm text-green-600" x-text="'Archivo: ' + nombreArchivo"></p>
                </div>

                <div x-show="cargando" class="mt-4 text-center">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2 text-sm text-gray-600">Procesando archivo...</p>
                </div>
            </div>

            <!-- Paso 2: Validación y Vista Previa -->
            <div x-show="paso === 2">
                <!-- Errores -->
                <div x-show="errores.length > 0" class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                    <h4 class="text-sm font-semibold text-red-800 mb-2">Errores encontrados:</h4>
                    <ul class="text-sm text-red-700 list-disc list-inside">
                        <template x-for="error in errores" :key="error">
                            <li x-text="error"></li>
                        </template>
                    </ul>
                </div>

                <!-- Warnings -->
                <div x-show="warnings.length > 0" class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <h4 class="text-sm font-semibold text-yellow-800 mb-2">Advertencias:</h4>
                    <ul class="text-sm text-yellow-700 list-disc list-inside">
                        <template x-for="warning in warnings" :key="warning">
                            <li x-text="warning"></li>
                        </template>
                    </ul>
                </div>

                <!-- Resumen -->
                <div x-show="todasLasFilas.length > 0" class="mb-4 space-y-2">
                    <div x-show="datosValidados.length > 0" class="p-3 bg-green-50 border border-green-200 rounded-lg">
                        <p class="text-sm text-green-800">
                            ✓ <span x-text="datosValidados.length"></span> clientes válidos listos para importar
                        </p>
                    </div>
                    <div x-show="todasLasFilas.length !== datosValidados.length" class="p-3 bg-red-50 border border-red-200 rounded-lg">
                        <p class="text-sm text-red-800">
                            ✗ <span x-text="todasLasFilas.length - datosValidados.length"></span> filas con errores (no se importarán)
                        </p>
                    </div>
                </div>

                <!-- Tabla de todas las filas -->
                <div x-show="todasLasFilas.length > 0" class="border border-gray-200 rounded-lg overflow-hidden">
                    <div class="overflow-y-auto overflow-x-auto" style="max-height: 400px; display: block;">
                        <table class="min-w-full divide-y divide-gray-200 table-auto">
                            <thead class="bg-gray-50 sticky top-0 z-10">
                                <tr>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fila</th>
                                    <th class="px-3 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">RUC</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Razón Social</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">WhatsApp</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Activo</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <template x-for="(fila, index) in todasLasFilas" :key="index">
                                    <tr :class="fila.valido ? 'hover:bg-gray-50' : 'bg-red-50 hover:bg-red-100'">
                                        <td class="px-3 py-3 text-sm text-gray-600 whitespace-nowrap" x-text="fila.fila"></td>
                                        <td class="px-3 py-3 text-sm whitespace-nowrap">
                                            <span x-show="fila.valido" class="inline-flex items-center text-green-600" title="Válido">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
                                                </svg>
                                            </span>
                                            <span x-show="!fila.valido" class="inline-flex items-center text-red-600" :title="fila.errores.join(', ')">
                                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"/>
                                                </svg>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm whitespace-nowrap"
                                            :class="fila.valido ? 'text-gray-900' : 'text-red-900'"
                                            x-text="fila.ruc || '-'"></td>
                                        <td class="px-4 py-3 text-sm"
                                            :class="fila.valido ? 'text-gray-900' : 'text-red-900'"
                                            x-text="fila.razon_social || '-'"></td>
                                        <td class="px-4 py-3 text-sm text-gray-600 whitespace-nowrap" x-text="fila.whatsapp || '-'"></td>
                                        <td class="px-4 py-3 text-sm whitespace-nowrap">
                                            <span :class="fila.activo ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'"
                                                  class="px-2 py-1 rounded-full text-xs font-medium"
                                                  x-text="fila.activo ? 'Activo' : 'Inactivo'"></span>
                                        </td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Leyenda de errores por fila -->
                <div x-show="todasLasFilas.some(f => !f.valido)" class="mt-3 p-3 bg-gray-50 border border-gray-200 rounded-lg">
                    <p class="text-xs text-gray-600 mb-2 font-semibold">Errores por fila (pasa el cursor sobre el ícono ✗ para ver detalles):</p>
                    <template x-for="(fila, index) in todasLasFilas.filter(f => !f.valido)" :key="index">
                        <div class="text-xs text-red-700 mb-1">
                            <span class="font-medium">Fila <span x-text="fila.fila"></span>:</span>
                            <span x-text="fila.errores.join(', ')"></span>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Paso 3: Procesando -->
            <div x-show="paso === 3" class="text-center py-8">
                <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-green-600"></div>
                <p class="mt-4 text-sm text-gray-600">Guardando clientes...</p>
            </div>

            <!-- Paso 4: Completado -->
            <div x-show="paso === 4" class="text-center py-8">
                <svg class="mx-auto h-16 w-16 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <h3 class="mt-4 text-lg font-semibold text-gray-900">¡Importación completada!</h3>
                <p class="mt-2 text-sm text-gray-600" x-text="mensaje"></p>
            </div>
        </div>

        <!-- Footer -->
        <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between bg-gray-50 flex-shrink-0">
            <button @click="cerrarModal()"
                    class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                <span x-show="paso !== 4">Cancelar</span>
                <span x-show="paso === 4">Cerrar</span>
            </button>

            <div class="flex gap-2">
                <button x-show="paso === 2" @click="volverPaso1()"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                    Volver
                </button>
                <button x-show="paso === 2 && datosValidados.length > 0" @click="procesarImportacion()"
                        :disabled="procesando"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:bg-gray-400">
                    Importar <span x-text="datosValidados.length"></span> Clientes
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function cargaMasivaApp() {
    return {
        modalAbierto: false,
        paso: 1, // 1: subir, 2: validar, 3: procesando, 4: completado
        nombreArchivo: '',
        datosValidados: [],
        todasLasFilas: [],
        errores: [],
        warnings: [],
        cargando: false,
        procesando: false,
        mensaje: '',

        abrirModal() {
            this.modalAbierto = true;
            this.resetear();
        },

        cerrarModal() {
            this.modalAbierto = false;
            if (this.paso === 4) {
                window.location.reload();
            }
        },

        resetear() {
            this.paso = 1;
            this.nombreArchivo = '';
            this.datosValidados = [];
            this.todasLasFilas = [];
            this.errores = [];
            this.warnings = [];
            this.cargando = false;
            this.procesando = false;
            this.mensaje = '';
        },

        async cargarArchivo(event) {
            const archivo = event.target.files[0];
            if (!archivo) return;

            this.nombreArchivo = archivo.name;
            this.cargando = true;

            const formData = new FormData();
            formData.append('archivo', archivo);

            try {
                const response = await fetch('{{ route("clientes.validar") }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: formData
                });

                if (!response.ok) {
                    const data = await response.json();
                    alert('Error: ' + (data.message || 'Error al procesar archivo'));
                    this.cargando = false;
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    this.datosValidados = data.data;
                    this.todasLasFilas = data.allRows || [];
                    this.errores = data.errors || [];
                    this.warnings = data.warnings || [];
                    this.paso = 2;
                } else {
                    alert('Error: ' + data.message);
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al procesar archivo. Verifica que el formato sea correcto.');
            } finally {
                this.cargando = false;
            }
        },

        volverPaso1() {
            this.paso = 1;
            this.nombreArchivo = '';
            this.datosValidados = [];
            this.todasLasFilas = [];
            this.errores = [];
            this.warnings = [];
            document.getElementById('archivoInput').value = '';
        },

        async procesarImportacion() {
            this.procesando = true;
            this.paso = 3;

            try {
                const response = await fetch('{{ route("clientes.procesar") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });

                if (!response.ok) {
                    const data = await response.json();
                    alert('Error: ' + (data.message || 'Error al importar clientes'));
                    this.paso = 2;
                    this.procesando = false;
                    return;
                }

                const data = await response.json();

                if (data.success) {
                    this.mensaje = data.message;
                    this.paso = 4;
                } else {
                    alert('Error: ' + data.message);
                    this.paso = 2;
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al importar clientes. Por favor intenta nuevamente.');
                this.paso = 2;
            } finally {
                this.procesando = false;
            }
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
