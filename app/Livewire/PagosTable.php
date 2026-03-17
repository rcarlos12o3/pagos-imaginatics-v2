<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Debounce;
use App\Models\HistorialPago;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class PagosTable extends Component
{
    use WithPagination;

    #[Debounce(500)]
    public $search = '';

    #[Debounce(300)]
    public $mes = '';

    #[Debounce(300)]
    public $anio = '';

    #[Debounce(300)]
    public $metodo = '';

    #[Debounce(300)]
    public $banco = '';

    protected $queryString = [];

    public function mount()
    {
        // Inicializar con mes y año actual
        $this->mes = now()->month;
        $this->anio = now()->year;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingMes()
    {
        $this->resetPage();
    }

    public function updatingAnio()
    {
        $this->resetPage();
    }

    public function updatingMetodo()
    {
        $this->resetPage();
    }

    public function updatingBanco()
    {
        $this->resetPage();
    }

    #[Computed]
    public function estadisticasMes()
    {
        return HistorialPago::whereYear('fecha_pago', now()->year)
            ->whereMonth('fecha_pago', now()->month)
            ->selectRaw('
                COALESCE(SUM(monto_pagado), 0) as total_recaudado,
                COUNT(*) as cantidad_pagos,
                COALESCE(AVG(monto_pagado), 0) as pago_promedio,
                COALESCE(MAX(monto_pagado), 0) as mayor_pago
            ')
            ->first();
    }

    #[Computed]
    public function bancos()
    {
        return Cache::remember('bancos_lista', 3600, function() {
            return HistorialPago::whereNotNull('banco')
                ->distinct()
                ->pluck('banco')
                ->filter()
                ->sort()
                ->values();
        });
    }

    public function render()
    {
        $pagos = $this->getPagos();

        return view('livewire.pagos-table', [
            'pagos' => $pagos,
        ]);
    }

    private function getPagos()
    {
        $query = HistorialPago::with('cliente')
            ->join('clientes', 'historial_pagos.cliente_id', '=', 'clientes.id');

        // Búsqueda instantánea
        if ($this->search) {
            $query->where(function($q) {
                $q->where('clientes.ruc', 'like', "%{$this->search}%")
                  ->orWhere('clientes.razon_social', 'like', "%{$this->search}%")
                  ->orWhere('clientes.nombre_comercial', 'like', "%{$this->search}%");
            });
        }

        // Filtro por mes y año
        if ($this->mes && $this->anio) {
            $query->whereYear('historial_pagos.fecha_pago', $this->anio)
                  ->whereMonth('historial_pagos.fecha_pago', $this->mes);
        }

        // Filtro por método
        if ($this->metodo) {
            $query->where('historial_pagos.metodo_pago', $this->metodo);
        }

        // Filtro por banco
        if ($this->banco) {
            $query->where('historial_pagos.banco', $this->banco);
        }

        // Seleccionar columnas específicas para evitar conflictos
        $query->select('historial_pagos.*');

        return $query->orderBy('historial_pagos.fecha_pago', 'desc')
                     ->orderBy('historial_pagos.id', 'desc')
                     ->paginate(15);
    }
}
