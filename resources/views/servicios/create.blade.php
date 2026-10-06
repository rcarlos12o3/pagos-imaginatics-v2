@extends('layouts.app')

@section('title', 'Contratar Servicio')

@section('actions')
    <a href="{{ route('servicios.index') }}"
       class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Volver
    </a>
@endsection

@section('content')
<div class="max-w-3xl" x-data="{ clienteId: '{{ $cliente->id ?? old('cliente_id') }}', servicioId: '' }">
    <form action="{{ route('servicios.store') }}" method="POST" class="bg-white shadow-sm rounded-lg p-6 space-y-6">
        @csrf

        <!-- Selección de Cliente -->
        @if($cliente)
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Cliente *</label>
                <input type="hidden" name="cliente_id" value="{{ $cliente->id }}">
                <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <div class="font-medium text-gray-900">{{ $cliente->razon_social }}</div>
                    <div class="text-sm text-gray-600">RUC: {{ $cliente->ruc }}</div>
                </div>
            </div>
        @else
            <livewire:cliente-selector :cliente-id="old('cliente_id')" />
        @endif

        <!-- Selección de Servicio -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">Servicio del Catálogo *</label>
            <select name="servicio_id" x-model="servicioId" required
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="">Seleccione un servicio</option>
                @foreach($catalogoServicios as $servicio)
                    <option value="{{ $servicio->id }}">
                        {{ $servicio->nombre }} - {{ $servicio->categoria }} (Base: {{ $servicio->moneda }} {{ number_format($servicio->precio_base, 2) }})
                    </option>
                @endforeach
            </select>
            @error('servicio_id')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Precio y Moneda -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Precio *</label>
                <input type="number" step="0.01" name="precio" value="{{ old('precio') }}" required min="0"
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('precio')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Moneda *</label>
                <select name="moneda" required
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="PEN" {{ old('moneda') == 'PEN' ? 'selected' : '' }}>PEN (Soles)</option>
                    <option value="USD" {{ old('moneda') == 'USD' ? 'selected' : '' }}>USD (Dólares)</option>
                </select>
                @error('moneda')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div x-data="vencimientoSugerido(@js(old('periodo_facturacion', 'anual')), @js(old('fecha_inicio', date('Y-m-d'))), @js(old('fecha_vencimiento')))" class="space-y-6">
        <!-- Periodo de Facturación -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Periodo de Facturación *</label>
            <select name="periodo_facturacion" required x-model="periodo" @change="sugerir()"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="mensual" {{ old('periodo_facturacion') == 'mensual' ? 'selected' : '' }}>Mensual</option>
                <option value="trimestral" {{ old('periodo_facturacion') == 'trimestral' ? 'selected' : '' }}>Trimestral</option>
                <option value="semestral" {{ old('periodo_facturacion') == 'semestral' ? 'selected' : '' }}>Semestral</option>
                <option value="anual" {{ old('periodo_facturacion', 'anual') == 'anual' ? 'selected' : '' }}>Anual</option>
            </select>
            @error('periodo_facturacion')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Fechas -->
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Inicio *</label>
                <input type="date" name="fecha_inicio" x-model="inicio" @change="sugerir()" required
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                @error('fecha_inicio')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Fecha de Vencimiento *</label>
                <input type="date" name="fecha_vencimiento" x-model="vencimiento" required
                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                <p x-show="vencimiento && vencimiento === sugerida" class="mt-1 text-xs text-gray-500">
                    Sugerida según el periodo (+<span x-text="meses()"></span> <span x-text="meses() === 1 ? 'mes' : 'meses'"></span>). Puedes editarla.
                </p>
                @error('fecha_vencimiento')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
        </div>
        </div>

        <!-- Auto Renovación -->
        <div class="flex items-center">
            <input type="checkbox" name="auto_renovacion" value="1" {{ old('auto_renovacion', true) ? 'checked' : '' }}
                   class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <label class="ml-2 block text-sm text-gray-900">Renovación Automática</label>
        </div>

        <!-- Notas -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Notas</label>
            <textarea name="notas" rows="3"
                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">{{ old('notas') }}</textarea>
            @error('notas')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <!-- Botones -->
        <div class="flex justify-end gap-4 pt-4 border-t">
            <a href="{{ route('servicios.index') }}"
               class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50">
                Cancelar
            </a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700">
                Contratar Servicio
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    function vencimientoSugerido(periodo, inicio, vencimiento) {
        return {
            periodo,
            inicio,
            vencimiento: vencimiento || '',
            sugerida: '',
            init() {
                if (!this.vencimiento) this.sugerir();
                else this.sugerida = this.calcular();
            },
            meses() {
                return { mensual: 1, trimestral: 3, semestral: 6, anual: 12 }[this.periodo] || 0;
            },
            calcular() {
                if (!this.inicio || !this.meses()) return '';
                const [y, m, d] = this.inicio.split('-').map(Number);
                // Vence el día anterior al mismo día del mes destino (06/10 → 05/10).
                // Si ese día no existe en el mes destino (ej. 30 en febrero), usar el último día del mes.
                // Si inicia el día 1, d - 1 = 0 da el último día del mes anterior.
                const ultimoDia = new Date(y, m - 1 + this.meses() + 1, 0).getDate();
                const fecha = new Date(y, m - 1 + this.meses(), Math.min(d - 1, ultimoDia));
                return [fecha.getFullYear(), String(fecha.getMonth() + 1).padStart(2, '0'), String(fecha.getDate()).padStart(2, '0')].join('-');
            },
            sugerir() {
                this.sugerida = this.calcular();
                this.vencimiento = this.sugerida;
            },
        };
    }
</script>
@endpush
