<?php

namespace App\Http\Controllers;

use App\Models\ServicioContratado;
use App\Models\CatalogoServicio;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class PagosPendientesController extends Controller
{
    /**
     * Mostrar dashboard de pagos pendientes
     */
    public function index(Request $request): View
    {
        $filtro = $request->get('filtro', 'todos');
        $servicioId = $request->get('servicio_id');
        $busqueda = $request->get('busqueda');

        // Query base con cálculo de urgencia
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
        if ($filtro !== 'todos') {
            $query->havingRaw("urgencia = ?", [$filtro]);
        }

        // Filtrar por servicio
        if ($servicioId) {
            $query->where('servicios_contratados.servicio_id', $servicioId);
        }

        // Buscar por cliente
        if ($busqueda) {
            $query->where(function($q) use ($busqueda) {
                $q->where('clientes.razon_social', 'LIKE', "%{$busqueda}%")
                  ->orWhere('clientes.ruc', 'LIKE', "%{$busqueda}%");
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

        $servicios = $query->paginate(20)->withQueryString();

        // Calcular métricas
        $metricas = $this->calcularMetricas();

        // Obtener catálogo de servicios para filtro
        $catalogoServicios = CatalogoServicio::where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('pagos-pendientes.index', compact('servicios', 'metricas', 'catalogoServicios', 'filtro', 'servicioId', 'busqueda'));
    }

    /**
     * Calcular métricas del dashboard
     */
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
