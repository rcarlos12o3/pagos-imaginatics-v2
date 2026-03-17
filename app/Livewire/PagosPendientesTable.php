<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Debounce;
use App\Models\ServicioContratado;
use App\Models\CatalogoServicio;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class PagosPendientesTable extends Component
{
    use WithPagination;

    #[Debounce(300)]
    public $filtro = 'todos';

    #[Debounce(300)]
    public $servicioId = '';

    #[Debounce(500)]
    public $busqueda = '';

    protected $queryString = [];

    public function updatingBusqueda()
    {
        $this->resetPage();
    }

    public function updatingFiltro()
    {
        $this->resetPage();
    }

    public function updatingServicioId()
    {
        $this->resetPage();
    }

    #[Computed]
    public function metricas()
    {
        return $this->calcularMetricas();
    }

    #[Computed]
    public function catalogoServicios()
    {
        return Cache::remember('catalogo_servicios_activos', 3600, function() {
            return CatalogoServicio::where('activo', true)
                ->orderBy('nombre')
                ->get();
        });
    }

    public function render()
    {
        $servicios = $this->getServicios();

        return view('livewire.pagos-pendientes-table', [
            'servicios' => $servicios,
        ]);
    }

    private function getServicios()
    {
        $query = ServicioContratado::query()
            ->select([
                'servicios_contratados.id as contrato_id',
                'servicios_contratados.cliente_id',
                'clientes.razon_social',
                'clientes.ruc',
                'clientes.whatsapp',
                'catalogo_servicios.nombre as servicio_nombre',
                'catalogo_servicios.categoria as servicio_categoria',
                'servicios_contratados.precio',
                'servicios_contratados.moneda',
                'servicios_contratados.periodo_facturacion',
                'servicios_contratados.fecha_vencimiento',
                DB::raw('DATEDIFF(servicios_contratados.fecha_vencimiento, CURDATE()) as dias_para_vencer'),
                DB::raw("
                    CASE
                        WHEN servicios_contratados.fecha_vencimiento < CURDATE() AND DATEDIFF(CURDATE(), servicios_contratados.fecha_vencimiento) > 30 THEN 'muy_vencido'
                        WHEN servicios_contratados.fecha_vencimiento < CURDATE() THEN 'vencido'
                        WHEN DATEDIFF(servicios_contratados.fecha_vencimiento, CURDATE()) <= 7 THEN 'proximo_vencer'
                        ELSE 'al_dia'
                    END as urgencia
                ")
            ])
            ->join('clientes', 'servicios_contratados.cliente_id', '=', 'clientes.id')
            ->join('catalogo_servicios', 'servicios_contratados.servicio_id', '=', 'catalogo_servicios.id')
            ->whereIn('servicios_contratados.estado', ['activo', 'vencido'])
            ->where('clientes.activo', true);

        // Aplicar filtro de urgencia
        if ($this->filtro !== 'todos') {
            $query->havingRaw("urgencia = ?", [$this->filtro]);
        }

        // Filtrar por servicio
        if ($this->servicioId) {
            $query->where('servicios_contratados.servicio_id', $this->servicioId);
        }

        // Buscar por cliente
        if ($this->busqueda) {
            $query->where(function($q) {
                $q->where('clientes.razon_social', 'LIKE', "%{$this->busqueda}%")
                  ->orWhere('clientes.ruc', 'LIKE', "%{$this->busqueda}%");
            });
        }

        // Ordenar por urgencia
        $query->orderByRaw("
            CASE urgencia
                WHEN 'muy_vencido' THEN 1
                WHEN 'vencido' THEN 2
                WHEN 'proximo_vencer' THEN 3
                ELSE 4
            END
        ")
        ->orderBy('servicios_contratados.fecha_vencimiento', 'asc');

        return $query->paginate(20);
    }

    private function calcularMetricas(): array
    {
        // Servicios con urgencia
        $serviciosConUrgencia = ServicioContratado::query()
            ->select([
                DB::raw("
                    CASE
                        WHEN servicios_contratados.fecha_vencimiento < CURDATE() AND DATEDIFF(CURDATE(), servicios_contratados.fecha_vencimiento) > 30 THEN 'muy_vencido'
                        WHEN servicios_contratados.fecha_vencimiento < CURDATE() THEN 'vencido'
                        WHEN DATEDIFF(servicios_contratados.fecha_vencimiento, CURDATE()) <= 7 THEN 'proximo_vencer'
                        ELSE 'al_dia'
                    END as urgencia
                "),
                'servicios_contratados.cliente_id',
                'servicios_contratados.moneda',
                'servicios_contratados.precio'
            ])
            ->join('clientes', 'servicios_contratados.cliente_id', '=', 'clientes.id')
            ->whereIn('servicios_contratados.estado', ['activo', 'vencido'])
            ->where('clientes.activo', true)
            ->get();

        $metricas = [
            'proximos_vencer' => $serviciosConUrgencia->where('urgencia', 'proximo_vencer')->count(),
            'vencidos' => $serviciosConUrgencia->where('urgencia', 'vencido')->count(),
            'muy_vencidos' => $serviciosConUrgencia->where('urgencia', 'muy_vencido')->count(),
            'clientes_afectados' => $serviciosConUrgencia->whereIn('urgencia', ['muy_vencido', 'vencido', 'proximo_vencer'])->pluck('cliente_id')->unique()->count(),
            'monto_vencido' => [
                'PEN' => $serviciosConUrgencia->whereIn('urgencia', ['muy_vencido', 'vencido'])->where('moneda', 'PEN')->sum('precio'),
                'USD' => $serviciosConUrgencia->whereIn('urgencia', ['muy_vencido', 'vencido'])->where('moneda', 'USD')->sum('precio')
            ],
            'monto_proximo' => [
                'PEN' => $serviciosConUrgencia->where('urgencia', 'proximo_vencer')->where('moneda', 'PEN')->sum('precio'),
                'USD' => $serviciosConUrgencia->where('urgencia', 'proximo_vencer')->where('moneda', 'USD')->sum('precio')
            ]
        ];

        return $metricas;
    }
}
