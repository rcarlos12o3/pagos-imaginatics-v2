@extends('layouts.app')

@section('title', 'Pagos Pendientes')

@section('content')
<div x-data="{
    busqueda: '{{ $busqueda ?? '' }}',
    filtro: '{{ $filtro }}',
    servicioId: '{{ $servicioId }}',

    get serviciosFiltrados() {
        let servicios = Array.from(document.querySelectorAll('tbody tr[data-servicio]'));

        return servicios.filter(row => {
            const texto = row.dataset.searchText.toLowerCase();
            const urgencia = row.dataset.urgencia;
            const servId = row.dataset.servicioId;

            // Filtro de búsqueda
            if (this.busqueda && !texto.includes(this.busqueda.toLowerCase())) {
                return false;
            }

            // Filtro de urgencia
            if (this.filtro !== 'todos' && urgencia !== this.filtro) {
                return false;
            }

            // Filtro de servicio
            if (this.servicioId && servId !== this.servicioId) {
                return false;
            }

            return true;
        });
    }
}" x-init="$watch('busqueda', () => filtrarTabla()); $watch('filtro', () => filtrarTabla()); $watch('servicioId', () => filtrarTabla())">

<script>
function filtrarTabla() {
    const servicios = Array.from(document.querySelectorAll('tbody tr[data-servicio]'));
    const busqueda = document.querySelector('[x-model=busqueda]')?.value?.toLowerCase() || '';
    const filtro = document.querySelector('[x-model=filtro]')?.value || 'todos';
    const servicioId = document.querySelector('[x-model=servicioId]')?.value || '';

    let visibles = 0;

    servicios.forEach(row => {
        const texto = row.dataset.searchText.toLowerCase();
        const urgencia = row.dataset.urgencia;
        const servId = row.dataset.servicioId;

        const coincideBusqueda = !busqueda || texto.includes(busqueda);
        const coincideUrgencia = filtro === 'todos' || urgencia === filtro;
        const coincideServicio = !servicioId || servId === servicioId;

        if (coincideBusqueda && coincideUrgencia && coincideServicio) {
            row.style.display = '';
            visibles++;
        } else {
            row.style.display = 'none';
        }
    });

    // Mostrar/ocultar mensaje de sin resultados
    const emptyRow = document.querySelector('tr[data-empty]');
    if (emptyRow) {
        emptyRow.style.display = visibles === 0 ? '' : 'none';
    }
}
</script>
<!-- Métricas -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
    <div class="bg-red-50 rounded-lg shadow-sm p-3">
        <div class="text-red-600 text-xs font-medium">Muy Vencidos</div>
        <div class="text-xl font-bold text-red-700 mt-1">{{ $metricas['muy_vencidos'] ?? 0 }}</div>
        <div class="text-xs text-red-500 mt-1">+30 días</div>
    </div>
    <div class="bg-orange-50 rounded-lg shadow-sm p-3">
        <div class="text-orange-600 text-xs font-medium">Vencidos</div>
        <div class="text-xl font-bold text-orange-700 mt-1">{{ $metricas['vencidos'] ?? 0 }}</div>
        <div class="text-xs text-orange-500 mt-1">&lt;30 días</div>
    </div>
    <div class="bg-yellow-50 rounded-lg shadow-sm p-3">
        <div class="text-yellow-600 text-xs font-medium">Próximos a Vencer</div>
        <div class="text-xl font-bold text-yellow-700 mt-1">{{ $metricas['proximos_vencer'] ?? 0 }}</div>
        <div class="text-xs text-yellow-600 mt-1">7 días</div>
    </div>
    <div class="bg-blue-50 rounded-lg shadow-sm p-3">
        <div class="text-blue-600 text-xs font-medium">Clientes Afectados</div>
        <div class="text-xl font-bold text-blue-700 mt-1">{{ $metricas['clientes_afectados'] ?? 0 }}</div>
    </div>
    <div class="bg-purple-50 rounded-lg shadow-sm p-3">
        <div class="text-purple-600 text-xs font-medium">Monto Total</div>
        @if(($metricas['monto_vencido']['PEN'] ?? 0) > 0)
            <div class="text-sm font-bold text-purple-700">PEN {{ number_format($metricas['monto_vencido']['PEN'], 2) }}</div>
        @endif
        @if(($metricas['monto_vencido']['USD'] ?? 0) > 0)
            <div class="text-sm font-bold text-purple-700">USD {{ number_format($metricas['monto_vencido']['USD'], 2) }}</div>
        @endif
    </div>
</div>

<!-- Filtros en Tiempo Real -->
<div class="bg-white rounded-lg shadow-sm p-4 mb-6">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Urgencia</label>
            <select x-model="filtro" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="todos">Todos</option>
                <option value="muy_vencido">Muy Vencidos</option>
                <option value="vencido">Vencidos</option>
                <option value="proximo_vencer">Próximos a Vencer</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Servicio</label>
            <select x-model="servicioId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Todos los Servicios</option>
                @foreach($catalogoServicios as $servicio)
                    <option value="{{ $servicio->id }}">{{ $servicio->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar Cliente</label>
            <input type="text" x-model="busqueda" placeholder="RUC o Razón Social..."
                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
    </div>
</div>

<!-- Lista de Servicios con Pagos Pendientes -->
<div class="bg-white shadow-sm rounded-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Servicio</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Periodo</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Monto</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vencimiento</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Urgencia</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($servicios as $servicio)
                    <tr class="hover:bg-gray-50"
                        data-servicio="true"
                        data-search-text="{{ $servicio->razon_social }} {{ $servicio->ruc }} {{ $servicio->servicio_nombre }}"
                        data-urgencia="{{ $servicio->urgencia }}"
                        data-servicio-id="{{ $servicio->catalogo_servicio_id ?? '' }}">
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900 max-w-xs truncate" title="{{ $servicio->razon_social }}">
                                {{ $servicio->razon_social }}
                            </div>
                            <div class="text-sm text-gray-500 whitespace-nowrap">RUC: {{ $servicio->ruc }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-medium text-gray-900">{{ $servicio->servicio_nombre }}</div>
                            <div class="text-sm text-gray-500">{{ $servicio->servicio_categoria }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                {{ ucfirst($servicio->periodo_facturacion) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900">{{ $servicio->moneda }} {{ number_format($servicio->precio, 2) }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($servicio->fecha_vencimiento)->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500">
                                @if($servicio->dias_para_vencer < 0)
                                    Vencido hace {{ abs($servicio->dias_para_vencer) }} días
                                @else
                                    Vence en {{ $servicio->dias_para_vencer }} días
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $urgenciaClasses = [
                                    'muy_vencido' => 'bg-red-100 text-red-800',
                                    'vencido' => 'bg-orange-100 text-orange-800',
                                    'proximo_vencer' => 'bg-yellow-100 text-yellow-800',
                                    'al_dia' => 'bg-green-100 text-green-800',
                                ];
                                $urgenciaLabels = [
                                    'muy_vencido' => 'Muy Vencido',
                                    'vencido' => 'Vencido',
                                    'proximo_vencer' => 'Próximo a Vencer',
                                    'al_dia' => 'Al Día',
                                ];
                                $class = $urgenciaClasses[$servicio->urgencia] ?? 'bg-gray-100 text-gray-800';
                                $label = $urgenciaLabels[$servicio->urgencia] ?? 'Desconocido';
                            @endphp
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $class }}">
                                {{ $label }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <a href="{{ route('servicios.show', $servicio->contrato_id) }}"
                               class="text-blue-600 hover:text-blue-900 mr-3">
                                Ver Detalle
                            </a>
                            <a href="{{ route('envios.index', ['cliente_id' => $servicio->cliente_id]) }}"
                               class="text-green-600 hover:text-green-900 mr-3">
                                Ver Órdenes
                            </a>
                            <a href="{{ route('pagos.create', ['cliente_id' => $servicio->cliente_id, 'servicio_id' => $servicio->contrato_id]) }}"
                               class="text-purple-600 hover:text-purple-900">
                                Registrar Pago
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr data-empty="true" style="display: none;">
                        <td colspan="7" class="px-6 py-8 text-center">
                            <div class="flex flex-col items-center justify-center text-gray-500">
                                <svg class="w-12 h-12 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-lg font-medium">No hay pagos pendientes</p>
                                <p class="text-sm mt-1">Todos los servicios están al día</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($servicios->hasPages())
        <div class="bg-white px-4 py-3 border-t border-gray-200">
            {{ $servicios->links() }}
        </div>
    @endif
</div>

</div><!-- Cierre del div x-data -->
@endsection
