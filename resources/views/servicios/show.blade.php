@extends('layouts.app')

@section('title', 'Detalle del Servicio')

@section('actions')
    <div class="flex gap-2">
        @if(in_array($servicio->estado, ['activo', 'vencido']))
            <button type="button" onclick="abrirModalMigrar()"
                    class="px-3 py-2 text-white text-sm font-medium rounded-lg"
                    style="background-color: #6366f1;"
                    onmouseover="this.style.backgroundColor='#4f46e5'"
                    onmouseout="this.style.backgroundColor='#6366f1'">
                🔄 Migrar Plan
            </button>
        @endif

        @if($servicio->estado === 'activo')
            <button type="button" onclick="document.getElementById('suspender-form').classList.toggle('hidden')"
                    class="px-3 py-2 bg-yellow-600 text-white text-sm font-medium rounded-lg hover:bg-yellow-700">
                Suspender
            </button>
        @endif

        @if(in_array($servicio->estado, ['suspendido', 'vencido']))
            <button type="button" onclick="document.getElementById('reactivar-form').classList.toggle('hidden')"
                    class="px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700">
                Reactivar
            </button>
        @endif

        <a href="{{ route('servicios.edit', $servicio) }}"
           class="px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700">
            Editar
        </a>

        <a href="{{ route('servicios.index') }}"
           class="px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50">
            Volver
        </a>
    </div>
@endsection

@section('content')
<div class="space-y-6">
    <!-- Información Principal -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <div class="flex justify-between items-start mb-4">
            <h2 class="text-xl font-semibold text-gray-900">Información del Servicio</h2>
            @php
                $estadoClasses = [
                    'activo' => 'bg-green-100 text-green-800',
                    'suspendido' => 'bg-yellow-100 text-yellow-800',
                    'vencido' => 'bg-red-100 text-red-800',
                    'cancelado' => 'bg-gray-100 text-gray-800',
                ];
                $class = $estadoClasses[$servicio->estado] ?? 'bg-gray-100 text-gray-800';
            @endphp
            <span class="px-3 py-1 text-sm font-medium rounded-full {{ $class }}">
                {{ ucfirst($servicio->estado) }}
            </span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-3">Cliente</h3>
                <div class="space-y-2">
                    <div>
                        <span class="text-sm text-gray-600">Razón Social:</span>
                        <p class="text-sm font-medium text-gray-900">{{ $servicio->cliente->razon_social }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">RUC:</span>
                        <p class="text-sm font-medium text-gray-900">{{ $servicio->cliente->ruc }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">WhatsApp:</span>
                        <p class="text-sm font-medium text-gray-900">{{ $servicio->cliente->whatsapp }}</p>
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500 mb-3">Servicio</h3>
                <div class="space-y-2">
                    <div>
                        <span class="text-sm text-gray-600">Nombre:</span>
                        <p class="text-sm font-medium text-gray-900">{{ $servicio->catalogoServicio->nombre }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Categoría:</span>
                        <p class="text-sm font-medium text-gray-900">{{ $servicio->catalogoServicio->categoria }}</p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-600">Descripción:</span>
                        <p class="text-sm text-gray-900">{{ $servicio->catalogoServicio->descripcion }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Detalles de Facturación -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Facturación</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <span class="text-sm text-gray-600">Precio:</span>
                <p class="text-lg font-bold text-gray-900">{{ $servicio->moneda }} {{ number_format($servicio->precio, 2) }}</p>
            </div>
            <div>
                <span class="text-sm text-gray-600">Periodo:</span>
                <p class="text-sm font-medium text-gray-900">{{ ucfirst($servicio->periodo_facturacion) }}</p>
            </div>
            <div>
                <span class="text-sm text-gray-600">Auto Renovación:</span>
                <p class="text-sm font-medium text-gray-900">{{ $servicio->auto_renovacion ? 'Sí' : 'No' }}</p>
            </div>
        </div>
    </div>

    <!-- Fechas -->
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Fechas</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <span class="text-sm text-gray-600">Fecha de Inicio:</span>
                <p class="text-sm font-medium text-gray-900">{{ $servicio->fecha_inicio->format('d/m/Y') }}</p>
            </div>
            <div>
                <span class="text-sm text-gray-600">Fecha de Vencimiento:</span>
                <p class="text-sm font-medium text-gray-900">{{ $servicio->fecha_vencimiento->format('d/m/Y') }}</p>
            </div>
            @if($servicio->fecha_ultima_factura)
            <div>
                <span class="text-sm text-gray-600">Última Factura:</span>
                <p class="text-sm font-medium text-gray-900">{{ $servicio->fecha_ultima_factura->format('d/m/Y') }}</p>
            </div>
            @endif
        </div>
    </div>

    <!-- Notas -->
    @if($servicio->notas)
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-2">Notas</h3>
        <p class="text-sm text-gray-700">{{ $servicio->notas }}</p>
    </div>
    @endif

    <!-- Motivo de Suspensión -->
    @if($servicio->motivo_suspension)
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
        <h3 class="text-sm font-medium text-yellow-800 mb-1">Motivo de {{ $servicio->estado === 'cancelado' ? 'Cancelación' : 'Suspensión' }}</h3>
        <p class="text-sm text-yellow-700">{{ $servicio->motivo_suspension }}</p>
    </div>
    @endif

    <!-- Historial de Envíos -->
    @if($servicio->envios->count() > 0)
    <div class="bg-white shadow-sm rounded-lg p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Historial de Envíos</h3>
        <div class="space-y-3">
            @foreach($servicio->envios->take(10) as $envio)
                <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                    <div>
                        <p class="text-sm font-medium text-gray-900">{{ $envio->tipo_envio_nombre }}</p>
                        <p class="text-xs text-gray-500">{{ $envio->fecha_envio->format('d/m/Y H:i') }}</p>
                    </div>
                    <span class="px-2 py-1 text-xs font-medium rounded {{ $envio->estado_badge_class }}">
                        {{ $envio->estado_nombre }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>

<!-- Formulario Suspender (oculto) -->
<div id="suspender-form" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Suspender Servicio</h3>
        <form action="{{ route('servicios.suspender', $servicio) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Motivo de Suspensión *</label>
                <textarea name="motivo" rows="3" required
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('suspender-form').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-yellow-600 text-white rounded-md text-sm font-medium hover:bg-yellow-700">
                    Suspender
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Formulario Reactivar (oculto) -->
<div id="reactivar-form" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Reactivar Servicio</h3>
        <form action="{{ route('servicios.reactivar', $servicio) }}" method="POST">
            @csrf
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Nueva Fecha de Vencimiento *</label>
                <input type="date" name="nueva_fecha_vencimiento" required
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            </div>
            <div class="flex justify-end gap-3">
                <button type="button" onclick="document.getElementById('reactivar-form').classList.add('hidden')"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-md text-sm font-medium hover:bg-green-700">
                    Reactivar
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Migrar Plan -->
<div id="migrar-form" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 z-50 overflow-y-auto">
    <div class="flex min-h-full items-center justify-center p-4">
    <div class="bg-white rounded-lg w-full max-w-2xl my-8 p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Migrar a Otro Plan</h3>
            <button type="button" onclick="cerrarModalMigrar()" class="text-gray-400 hover:text-gray-500">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
            <!-- Información del servicio actual -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <h4 class="text-sm font-medium text-blue-900 mb-2">Servicio Actual</h4>
                <p class="text-sm text-blue-800">{{ $servicio->catalogoServicio->nombre }}</p>
                <p class="text-xs text-blue-600 mt-1">{{ $servicio->moneda }} {{ number_format($servicio->precio, 2) }} - {{ ucfirst($servicio->periodo_facturacion) }}</p>
            </div>

        <form action="{{ route('servicios.migrar-plan', $servicio) }}" method="POST" id="form-migrar">
            @csrf

            <!-- Selector de nuevo servicio -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Nuevo Plan *</label>
                <div id="loading-servicios" class="text-sm text-gray-500">Cargando servicios disponibles...</div>
                <select name="nuevo_servicio_id" id="nuevo_servicio_id" required
                        class="hidden w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                        onchange="actualizarPrecioSugerido()">
                    <option value="">Seleccione un plan</option>
                </select>
                <p class="mt-1 text-xs text-gray-500">Solo se muestran planes de la misma categoría</p>
            </div>

            <!-- Precio -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Precio *</label>
                <div class="flex gap-2">
                    <select name="moneda" required class="w-24 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="PEN" {{ $servicio->moneda === 'PEN' ? 'selected' : '' }}>PEN</option>
                        <option value="USD" {{ $servicio->moneda === 'USD' ? 'selected' : '' }}>USD</option>
                    </select>
                    <input type="number" name="precio" id="precio" step="0.01" min="0" required
                           class="flex-1 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                           placeholder="0.00">
                </div>
            </div>

            <!-- Fecha de vencimiento -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Fecha de Vencimiento del Nuevo Servicio *</label>
                <input type="date" name="fecha_vencimiento" id="fecha_vencimiento" required
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <p class="mt-1 text-xs text-gray-500">
                    <strong>Importante:</strong> Esta es la fecha hasta la cual el cliente estará cubierto después de pagar.
                    El sistema enviará la orden de pago automáticamente según la periodicidad del nuevo plan.
                </p>
                <p class="mt-1 text-xs text-blue-600" id="fecha-sugerencia"></p>
            </div>

            <!-- Generar orden de pago ahora -->
            <div class="mb-4">
                <label class="flex items-start">
                    <input type="checkbox" name="generar_orden_inmediata" id="generar_orden_inmediata" value="1" checked
                           class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <span class="ml-2">
                        <span class="text-sm font-medium text-gray-700">Generar orden de pago inmediatamente</span>
                        <span class="block text-xs text-gray-500 mt-1">
                            Si marcas esta opción, el servicio aparecerá automáticamente en "Órdenes de Pago" para que puedas enviar la orden al cliente de inmediato.
                            <strong>Recomendado cuando el cliente debe pagar ahora.</strong>
                        </span>
                    </span>
                </label>
            </div>

            <!-- Motivo (opcional) -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-2">Motivo de Migración (opcional)</label>
                <textarea name="motivo_migracion" rows="2"
                          class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                          placeholder="Ej: Cliente solicitó cambio a plan anual para obtener descuento"></textarea>
            </div>

            <!-- Advertencia -->
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                <h4 class="text-sm font-medium text-yellow-900 mb-1">⚠️ Importante</h4>
                <ul class="text-xs text-yellow-800 space-y-1 list-disc list-inside">
                    <li>El servicio actual será suspendido</li>
                    <li>Se creará un nuevo contrato con el plan seleccionado</li>
                    <li>Se mantendrá trazabilidad completa de la migración</li>
                </ul>
            </div>

            <!-- Botones -->
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="cerrarModalMigrar()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-4 py-2 text-white rounded-md text-sm font-medium"
                        style="background-color: #6366f1;"
                        onmouseover="this.style.backgroundColor='#4f46e5'"
                        onmouseout="this.style.backgroundColor='#6366f1'">
                    Migrar Plan
                </button>
            </div>
        </form>
    </div>
    </div>
</div>

<script>
let serviciosCompatibles = [];

function abrirModalMigrar() {
    document.getElementById('migrar-form').classList.remove('hidden');
    cargarServiciosCompatibles();
}

function cerrarModalMigrar() {
    document.getElementById('migrar-form').classList.add('hidden');
}

async function cargarServiciosCompatibles() {
    const loadingEl = document.getElementById('loading-servicios');
    const selectEl = document.getElementById('nuevo_servicio_id');

    try {
        const response = await fetch(`{{ route('servicios.compatibles') }}?servicio_id={{ $servicio->id }}`);
        const data = await response.json();

        if (data.success) {
            serviciosCompatibles = data.data;

            // Limpiar y poblar el select
            selectEl.innerHTML = '<option value="">Seleccione un plan</option>';

            data.data.forEach(servicio => {
                const option = document.createElement('option');
                option.value = servicio.id;
                option.textContent = servicio.nombre;
                option.dataset.precio = servicio.precio_base;
                option.dataset.moneda = servicio.moneda;
                selectEl.appendChild(option);
            });

            // Mostrar select y ocultar loading
            loadingEl.classList.add('hidden');
            selectEl.classList.remove('hidden');
        } else {
            loadingEl.textContent = 'Error al cargar servicios: ' + data.error;
        }
    } catch (error) {
        console.error('Error:', error);
        loadingEl.textContent = 'Error al cargar servicios disponibles';
    }
}

function actualizarPrecioSugerido() {
    const selectEl = document.getElementById('nuevo_servicio_id');
    const precioInput = document.getElementById('precio');
    const fechaInput = document.getElementById('fecha_vencimiento');
    const sugerenciaEl = document.getElementById('fecha-sugerencia');
    const selectedOption = selectEl.options[selectEl.selectedIndex];

    if (selectedOption && selectedOption.value) {
        // Actualizar precio
        const precioBase = selectedOption.dataset.precio;
        if (precioBase) {
            precioInput.value = parseFloat(precioBase).toFixed(2);
        }

        // Calcular fecha sugerida según periodicidad
        const nombreServicio = selectedOption.textContent.toLowerCase();
        let meses = 1;
        let periodoNombre = 'mensual';

        if (nombreServicio.includes('anual')) {
            meses = 12;
            periodoNombre = 'anual';
        } else if (nombreServicio.includes('semestral')) {
            meses = 6;
            periodoNombre = 'semestral';
        } else if (nombreServicio.includes('trimestral')) {
            meses = 3;
            periodoNombre = 'trimestral';
        }

        // Usar fecha de vencimiento ACTUAL del servicio (no hoy)
        const fechaVencimientoActual = new Date('{{ $servicio->fecha_vencimiento->format('Y-m-d') }}');

        // MANTENER la misma fecha de vencimiento
        // El cliente debe pagar primero, luego el sistema renovará
        const fechaFormateada = fechaVencimientoActual.toISOString().split('T')[0];
        fechaInput.value = fechaFormateada;

        // Mostrar sugerencia
        const fechaLegible = fechaVencimientoActual.toLocaleDateString('es-PE', {day: '2-digit', month: '2-digit', year: 'numeric'});
        sugerenciaEl.textContent = `✓ Manteniendo fecha actual: ${fechaLegible}. El cliente debe pagar este periodo por adelantado (${periodoNombre}). Después de pagar, el sistema renovará por ${meses} ${meses === 1 ? 'mes' : 'meses'}.`;
    }
}

// Cerrar modal al hacer clic fuera
document.getElementById('migrar-form')?.addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModalMigrar();
    }
});
</script>
@endsection
