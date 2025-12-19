@extends('layouts.app')

@section('title', 'Órdenes de Pago')

@section('content')
<div x-data="{
    servicios: [],
    serviciosFiltrados: [],
    cargando: true,
    totalServicios: 0,
    debenEnviarse: 0,
    seleccionados: [],
    enviando: false,
    progreso: 0,
    progresoTexto: '',
    busqueda: '',

    async cargarServiciosPendientes() {
        this.cargando = true;
        try {
            const response = await fetch('/api/op/analizar-pendientes');
            const data = await response.json();

            if (data.success) {
                this.servicios = data.data.servicios;
                this.serviciosFiltrados = data.data.servicios;
                this.totalServicios = data.data.total;
                this.debenEnviarse = data.data.deben_enviarse;
            }
        } catch (error) {
            console.error('Error:', error);
        } finally {
            this.cargando = false;
        }
    },

    filtrarServicios() {
        if (!this.busqueda.trim()) {
            this.serviciosFiltrados = this.servicios;
            return;
        }

        const termino = this.busqueda.toLowerCase().trim();
        this.serviciosFiltrados = this.servicios.filter(servicio => {
            return servicio.ruc.includes(termino) ||
                   servicio.empresa.toLowerCase().includes(termino);
        });
    },

    toggleSeleccion(contratoId) {
        if (this.seleccionados.includes(contratoId)) {
            this.seleccionados = this.seleccionados.filter(id => id !== contratoId);
        } else {
            this.seleccionados.push(contratoId);
        }
    },

    seleccionarTodos() {
        this.seleccionados = this.servicios
            .filter(s => s.debe_enviarse)
            .map(s => s.contrato_id);
    },

    deseleccionarTodos() {
        this.seleccionados = [];
    },

    async enviarSeleccionados() {
        if (this.seleccionados.length === 0) {
            alert('⚠️ Por favor selecciona al menos un servicio');
            return;
        }

        const serviciosAEnviar = this.servicios.filter(s => this.seleccionados.includes(s.contrato_id));
        const nombres = serviciosAEnviar.map(s => s.empresa).join('\\n• ');

        if (!confirm(`📤 ¿Enviar órdenes de pago a los siguientes servicios?\\n\\n• ${nombres}\\n\\n✅ Total: ${serviciosAEnviar.length}`)) {
            return;
        }

        this.enviando = true;
        this.progreso = 10;
        this.progresoTexto = 'Generando imágenes de órdenes de pago...';

        try {
            const serviciosConImagenes = [];

            // Generar canvas para cada servicio
            for (let i = 0; i < serviciosAEnviar.length; i++) {
                const servicio = serviciosAEnviar[i];
                this.progresoTexto = `Generando imagen ${i + 1} de ${serviciosAEnviar.length}...`;
                this.progreso = 10 + ((i / serviciosAEnviar.length) * 60);

                const canvas = await generarCanvasOrdenPago(servicio);
                const imagenBase64 = canvasToBase64(canvas);

                serviciosConImagenes.push({
                    contrato_id: servicio.contrato_id,
                    cliente_id: servicio.cliente_id,
                    whatsapp: servicio.whatsapp,
                    imagen_base64: imagenBase64
                });
            }

            // Enviar a la cola
            this.progresoTexto = 'Enviando a la cola de procesamiento...';
            this.progreso = 80;

            const response = await fetch('/api/op/enviar-ordenes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').getAttribute('content')
                },
                body: JSON.stringify({ servicios: serviciosConImagenes })
            });

            const data = await response.json();

            if (data.success) {
                this.progreso = 100;
                this.progresoTexto = '✅ Órdenes agregadas a la cola exitosamente';

                alert(`✅ ${data.data.trabajos_agregados} órdenes agregadas a la cola de envío\\n\\nSerán procesadas en segundo plano.\\n\\nPuedes ver el progreso en Historial.`);

                // Redirigir al historial para monitorear
                window.location.href = '/historial?sesion=' + data.data.sesion_id;
            } else {
                throw new Error(data.error || 'Error al enviar órdenes');
            }
        } catch (error) {
            console.error('Error:', error);
            alert('❌ Error al enviar: ' + error.message);
            this.enviando = false;
            this.progreso = 0;
            this.progresoTexto = '';
        }
    },

    getEstadoBadgeClass(estado) {
        const classes = {
            'dentro_del_plazo_ideal': 'bg-green-100 text-green-800',
            'fuera_del_plazo': 'bg-red-100 text-red-800',
            'pendiente': 'bg-gray-100 text-gray-800',
            'ya_enviado': 'bg-blue-100 text-blue-800'
        };
        return classes[estado] || 'bg-gray-100 text-gray-800';
    },

    getEstadoNombre(estado) {
        const nombres = {
            'dentro_del_plazo_ideal': 'En Plazo Ideal',
            'fuera_del_plazo': 'Atrasado',
            'pendiente': 'Pendiente',
            'ya_enviado': 'Ya Enviado'
        };
        return nombres[estado] || estado;
    }
}" x-init="cargarServiciosPendientes()">

    <!-- Alert Info -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r-lg">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-500 mr-3 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-blue-800">Sistema de Análisis Inteligente</p>
                <p class="mt-1 text-sm text-blue-700">
                    Esta pantalla muestra automáticamente los servicios que deben recibir órdenes de pago según su periodicidad y fecha de vencimiento.
                </p>
            </div>
        </div>
    </div>

    <!-- Cards de Resumen -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total de Servicios Activos</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" x-text="totalServicios"></p>
                </div>
                <div class="bg-blue-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm p-6 border-l-4 border-orange-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Deben Enviarse Ahora</p>
                    <p class="text-3xl font-bold text-orange-600 mt-2" x-text="debenEnviarse"></p>
                    <p class="text-xs text-gray-500 mt-1">Atrasados + En plazo ideal</p>
                </div>
                <div class="bg-orange-100 rounded-full p-3">
                    <svg class="w-8 h-8 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 mb-6">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-gray-900">Análisis de Servicios</h2>
                <p class="text-sm text-gray-600 mt-1">
                    <span x-show="!cargando">
                        Mostrando todos los servicios ordenados por prioridad
                    </span>
                    <span x-show="cargando">Analizando servicios...</span>
                </p>
            </div>
            <button @click="cargarServiciosPendientes()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors flex items-center gap-2"
                    :disabled="cargando">
                <svg class="w-5 h-5" :class="{'animate-spin': cargando}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                Actualizar
            </button>
        </div>

        <!-- Buscador -->
        <div class="mb-4 border-t pt-4" x-show="!cargando && totalServicios > 0">
            <div class="flex items-center gap-3">
                <div class="flex-1">
                    <input type="text"
                           x-model="busqueda"
                           @input="filtrarServicios()"
                           placeholder="Buscar por RUC o Razón Social..."
                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="mt-1 text-xs text-gray-500">Filtra en tiempo real entre los <span x-text="totalServicios"></span> servicios cargados</p>
                </div>
                <button @click="busqueda = ''; filtrarServicios();"
                        x-show="busqueda.length > 0"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-800">
                    Limpiar
                </button>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="flex items-center gap-3 border-t pt-4" x-show="!cargando && debenEnviarse > 0">
            <button @click="seleccionarTodos()"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                ✓ Seleccionar todos
            </button>
            <button @click="deseleccionarTodos()"
                    class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors text-sm">
                ✗ Deseleccionar
            </button>
            <button @click="enviarSeleccionados()"
                    class="px-6 py-2 rounded-lg text-white transition-colors text-sm font-medium flex items-center gap-2"
                    :class="seleccionados.length > 0 ? 'bg-green-600 hover:bg-green-700' : 'bg-gray-400 cursor-not-allowed'"
                    :disabled="seleccionados.length === 0 || enviando">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                <span x-text="seleccionados.length > 0 ? `Enviar ${seleccionados.length} seleccionado${seleccionados.length > 1 ? 's' : ''}` : 'Enviar órdenes'"></span>
            </button>
        </div>

        <!-- Barra de progreso -->
        <div x-show="enviando" class="mt-4 bg-gray-100 rounded-lg p-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-700" x-text="progresoTexto"></span>
                <span class="text-sm font-medium text-gray-700" x-text="progreso + '%'"></span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div class="bg-blue-600 h-2 rounded-full transition-all duration-300" :style="`width: ${progreso}%`"></div>
            </div>
        </div>
    </div>

    <!-- Tabla de Servicios -->
    <div class="bg-white rounded-lg shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-12">
                            <input type="checkbox"
                                   @change="$event.target.checked ? seleccionarTodos() : deseleccionarTodos()"
                                   :checked="seleccionados.length > 0 && seleccionados.length === servicios.filter(s => s.debe_enviarse).length"
                                   class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Servicio</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periodicidad</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimiento</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Días</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- Loading State -->
                    <tr x-show="cargando">
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="animate-spin h-8 w-8 text-blue-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <p class="text-gray-600">Analizando servicios...</p>
                        </td>
                    </tr>

                    <!-- Empty State -->
                    <tr x-show="!cargando && servicios.length === 0">
                        <td colspan="8" class="px-6 py-12 text-center">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <p class="text-gray-600 font-medium">¡Excelente!</p>
                            <p class="text-gray-500 text-sm mt-1">No hay servicios pendientes de envío en este momento</p>
                        </td>
                    </tr>

                    <!-- Data Rows -->
                    <template x-for="servicio in serviciosFiltrados" :key="servicio.contrato_id">
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox"
                                       x-show="servicio.debe_enviarse"
                                       :checked="seleccionados.includes(servicio.contrato_id)"
                                       @change="toggleSeleccion(servicio.contrato_id)"
                                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900" x-text="servicio.empresa"></div>
                                <div class="text-sm text-gray-500" x-text="servicio.ruc"></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="servicio.servicio_nombre"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-purple-100 text-purple-800" x-text="servicio.periodicidad"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span x-text="servicio.moneda"></span> <span x-text="servicio.precio"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900" x-text="servicio.fecha_vencimiento_periodo_actual"></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold" :class="servicio.dias_hasta_vencer < 0 ? 'text-red-600' : 'text-gray-900'" x-text="Math.abs(servicio.dias_hasta_vencer) + (servicio.dias_hasta_vencer < 0 ? ' (vencido)' : '')"></span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" :class="getEstadoBadgeClass(servicio.estado)" x-text="getEstadoNombre(servicio.estado)"></span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Leyenda de Estados -->
    <div class="mt-6 bg-gray-50 rounded-lg p-4">
        <h3 class="text-sm font-semibold text-gray-700 mb-3">Leyenda de Estados:</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">En Plazo Ideal</span>
                <span class="text-xs text-gray-600">Debe enviarse ahora</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Atrasado</span>
                <span class="text-xs text-gray-600">Ya venció, urgente</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">Pendiente</span>
                <span class="text-xs text-gray-600">Aún no es tiempo</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">Ya Enviado</span>
                <span class="text-xs text-gray-600">Ya se envió este periodo</span>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="/js/orden-pago.js"></script>
@endpush

@endsection
