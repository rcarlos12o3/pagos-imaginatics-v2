@extends('layouts.app')

@section('title', 'Comunicaciones')

@section('content')
<div class="bg-white shadow-sm rounded-lg overflow-hidden" x-data="comunicacionesApp()">
    <!-- Header -->
    <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold text-gray-800">Enviar Comunicación a Clientes</h2>
            <button @click="cargarClientes()"
                    :disabled="cargando"
                    class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition-colors">
                <span x-show="!cargando">Recargar Clientes</span>
                <span x-show="cargando">Cargando...</span>
            </button>
        </div>
    </div>

    <!-- Filtros y Selección -->
    <div class="border-b border-gray-200 px-6 py-4 bg-gray-50">
        <div class="flex items-center gap-4 mb-4">
            <input type="text"
                   x-model="busqueda"
                   @input="filtrarClientes()"
                   placeholder="Buscar por razón social, RUC o WhatsApp..."
                   class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">

            <button @click="toggleSeleccionTodos()"
                    class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 whitespace-nowrap">
                <span x-text="todoSeleccionado ? 'Deseleccionar Todos' : 'Seleccionar Todos'"></span>
            </button>
        </div>

        <div class="text-sm text-gray-600">
            <span x-text="clientesSeleccionados.length"></span> cliente(s) seleccionado(s)
        </div>
    </div>

    <!-- Lista de Clientes -->
    <div class="px-6 py-4 max-h-96 overflow-y-auto border-b border-gray-200">
        <template x-if="cargando">
            <div class="text-center py-8 text-gray-500">
                Cargando clientes...
            </div>
        </template>

        <template x-if="!cargando && clientesFiltrados.length === 0">
            <div class="text-center py-8 text-gray-500">
                No hay clientes activos con WhatsApp registrado.
            </div>
        </template>

        <template x-if="!cargando && clientesFiltrados.length > 0">
            <div class="space-y-2">
                <template x-for="cliente in clientesFiltrados" :key="cliente.id">
                    <label class="flex items-center p-3 rounded-lg hover:bg-gray-50 cursor-pointer transition-colors border border-gray-200">
                        <input type="checkbox"
                               :value="cliente.id"
                               x-model="clientesSeleccionados"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 mr-3">
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="font-medium text-gray-900" x-text="cliente.razon_social"></span>
                                <span class="text-xs text-gray-500" x-text="'(' + cliente.ruc + ')'"></span>
                            </div>
                            <div class="text-sm text-gray-600 mt-1">
                                <span class="inline-flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/>
                                    </svg>
                                    <span x-text="cliente.whatsapp"></span>
                                </span>
                            </div>
                        </div>
                    </label>
                </template>
            </div>
        </template>
    </div>

    <!-- Formulario de Mensaje -->
    <div class="px-6 py-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Mensaje
                <span class="text-red-500">*</span>
            </label>
            <div class="text-xs text-gray-500 mb-2">
                El mensaje comenzará automáticamente con "Hola [Razón Social]"
            </div>
            <textarea x-model="mensaje"
                      rows="6"
                      maxlength="4000"
                      placeholder="Escribe aquí el contenido del mensaje..."
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            <div class="text-xs text-gray-500 mt-1">
                <span x-text="mensaje.length"></span> / 4000 caracteres
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">
                Imagen (Opcional)
            </label>
            <div class="flex items-center gap-4">
                <input type="file"
                       @change="cargarImagen($event)"
                       accept="image/*"
                       id="imagenInput"
                       class="block w-full text-sm text-gray-500
                              file:mr-4 file:py-2 file:px-4
                              file:rounded-md file:border-0
                              file:text-sm file:font-semibold
                              file:bg-blue-50 file:text-blue-700
                              hover:file:bg-blue-100">

                <button type="button"
                        @click="limpiarImagen()"
                        x-show="imagenBase64"
                        class="px-3 py-2 bg-red-100 text-red-700 text-sm rounded-md hover:bg-red-200">
                    Quitar
                </button>
            </div>

            <!-- Preview de imagen -->
            <div x-show="imagenBase64" class="mt-4">
                <p class="text-sm text-gray-600 mb-2">Vista previa:</p>
                <img :src="imagenBase64" alt="Preview" class="max-h-48 rounded-lg border border-gray-300">
            </div>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-600">
                <template x-if="!enviando">
                    <span>Los mensajes se programarán en cola y se enviarán automáticamente con delays de 30-90 segundos entre clientes.</span>
                </template>
                <template x-if="enviando">
                    <span class="text-blue-600 font-medium">
                        Programando envíos...
                    </span>
                </template>
            </div>

            <button @click="enviar()"
                    :disabled="enviando || clientesSeleccionados.length === 0 || !mensaje.trim()"
                    class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 disabled:bg-gray-400 disabled:cursor-not-allowed transition-colors">
                <span x-show="!enviando">Programar Envíos</span>
                <span x-show="enviando">Programando...</span>
            </button>
        </div>
    </div>

    <!-- Modal de Confirmación sin Imagen -->
    <div x-show="mostrarConfirmacionSinImagen"
         x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
         @click.self="mostrarConfirmacionSinImagen = false">
        <div class="bg-white rounded-lg shadow-xl max-w-md w-full mx-4 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">¿Enviar sin imagen?</h3>
            <p class="text-gray-600 mb-6">No has seleccionado ninguna imagen. ¿Estás seguro de continuar con solo texto?</p>
            <div class="flex gap-3 justify-end">
                <button @click="mostrarConfirmacionSinImagen = false"
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">
                    Cancelar
                </button>
                <button @click="confirmarEnvioSinImagen()"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    Sí, enviar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function comunicacionesApp() {
    return {
        clientes: [],
        clientesFiltrados: [],
        clientesSeleccionados: [],
        busqueda: '',
        mensaje: '',
        imagenBase64: null,
        cargando: false,
        enviando: false,
        mostrarConfirmacionSinImagen: false,

        get todoSeleccionado() {
            return this.clientesFiltrados.length > 0 &&
                   this.clientesSeleccionados.length === this.clientesFiltrados.length;
        },

        init() {
            this.cargarClientes();
        },

        async cargarClientes() {
            this.cargando = true;
            try {
                const response = await fetch('/api/comunicaciones/clientes');
                const data = await response.json();

                if (data.success) {
                    this.clientes = data.clientes;
                    this.filtrarClientes();
                } else {
                    this.mostrarError('Error al cargar clientes');
                }
            } catch (error) {
                console.error('Error:', error);
                this.mostrarError('Error de conexión');
            } finally {
                this.cargando = false;
            }
        },

        filtrarClientes() {
            if (!this.busqueda.trim()) {
                this.clientesFiltrados = [...this.clientes];
                return;
            }

            const termino = this.busqueda.toLowerCase();
            this.clientesFiltrados = this.clientes.filter(cliente => {
                return cliente.razon_social.toLowerCase().includes(termino) ||
                       cliente.ruc.includes(termino) ||
                       (cliente.whatsapp && cliente.whatsapp.includes(termino));
            });
        },

        toggleSeleccionTodos() {
            if (this.todoSeleccionado) {
                this.clientesSeleccionados = [];
            } else {
                this.clientesSeleccionados = this.clientesFiltrados.map(c => c.id);
            }
        },

        cargarImagen(event) {
            const file = event.target.files[0];
            if (!file) return;

            // Validar tipo
            if (!file.type.startsWith('image/')) {
                this.mostrarError('Por favor selecciona una imagen válida');
                return;
            }

            // Validar tamaño (10MB máximo original)
            if (file.size > 10 * 1024 * 1024) {
                this.mostrarError('La imagen es muy grande. Máximo 10MB');
                return;
            }

            // Comprimir y redimensionar imagen
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    // Redimensionar si es muy grande
                    const maxWidth = 1200;
                    const maxHeight = 1200;
                    let width = img.width;
                    let height = img.height;

                    if (width > maxWidth || height > maxHeight) {
                        if (width > height) {
                            height = (height / width) * maxWidth;
                            width = maxWidth;
                        } else {
                            width = (width / height) * maxHeight;
                            height = maxHeight;
                        }
                    }

                    // Crear canvas y comprimir
                    const canvas = document.createElement('canvas');
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);

                    // Convertir a base64 con compresión (calidad 0.8)
                    this.imagenBase64 = canvas.toDataURL('image/jpeg', 0.8);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        },

        limpiarImagen() {
            this.imagenBase64 = null;
            document.getElementById('imagenInput').value = '';
        },

        async enviar() {
            // Validaciones
            if (this.clientesSeleccionados.length === 0) {
                this.mostrarError('Selecciona al menos un cliente');
                return;
            }

            if (!this.mensaje.trim()) {
                this.mostrarError('Escribe un mensaje');
                return;
            }

            // Si no hay imagen, pedir confirmación
            if (!this.imagenBase64) {
                this.mostrarConfirmacionSinImagen = true;
                return;
            }

            // Enviar
            await this.ejecutarEnvio(false);
        },

        async confirmarEnvioSinImagen() {
            this.mostrarConfirmacionSinImagen = false;
            await this.ejecutarEnvio(true);
        },

        async ejecutarEnvio(confirmarSinImagen) {
            this.enviando = true;

            try {
                const response = await fetch('/api/comunicaciones/enviar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        clientes: this.clientesSeleccionados,
                        mensaje: this.mensaje,
                        imagen: this.imagenBase64,
                        confirmar_sin_imagen: confirmarSinImagen
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(`✅ ${data.message}\n\nSesión ID: ${data.sesion_id}\nTotal programados: ${data.total}\n\nLos mensajes se enviarán automáticamente. Puedes cerrar esta ventana.`);

                    // Limpiar formulario
                    this.clientesSeleccionados = [];
                    this.mensaje = '';
                    this.limpiarImagen();
                } else if (data.requiere_confirmacion) {
                    this.mostrarConfirmacionSinImagen = true;
                } else {
                    this.mostrarError(data.message || 'Error al programar envíos');
                }
            } catch (error) {
                console.error('Error:', error);
                this.mostrarError('Error de conexión');
            } finally {
                this.enviando = false;
            }
        },

        mostrarError(mensaje) {
            alert('❌ ' + mensaje);
        }
    }
}
</script>

<style>
[x-cloak] { display: none !important; }
</style>
@endsection
