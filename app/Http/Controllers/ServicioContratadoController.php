<?php

namespace App\Http\Controllers;

use App\Models\CatalogoServicio;
use App\Models\Cliente;
use App\Models\ServicioContratado;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;

class ServicioContratadoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = ServicioContratado::with(['cliente', 'catalogoServicio'])
            ->orderBy('fecha_vencimiento', 'asc');

        // Filtro de búsqueda por RUC o Razón Social
        if ($request->filled('buscar')) {
            $buscar = $request->buscar;
            $query->whereHas('cliente', function ($q) use ($buscar) {
                $q->where('ruc', 'LIKE', "%{$buscar}%")
                  ->orWhere('razon_social', 'LIKE', "%{$buscar}%");
            });
        }

        // Filtros
        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        if ($request->filled('periodo')) {
            $query->where('periodo_facturacion', $request->periodo);
        }

        $servicios = $query->paginate(20)->withQueryString();

        // Estadísticas
        $estadisticas = $this->getEstadisticas();

        return view('servicios.index', compact('servicios', 'estadisticas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        // Si viene con cliente_id, obtener los datos del cliente
        $cliente = null;
        if ($request->filled('cliente_id')) {
            $cliente = Cliente::findOrFail($request->cliente_id);
        }

        // Obtener catálogo de servicios activos
        $catalogoServicios = CatalogoServicio::activo()
            ->orderBy('orden_visualizacion')
            ->orderBy('nombre')
            ->get();

        return view('servicios.create', compact('catalogoServicios', 'cliente'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'servicio_id' => 'required|exists:catalogo_servicios,id',
            'precio' => 'required|numeric|min:0',
            'moneda' => 'required|in:PEN,USD',
            'periodo_facturacion' => 'required|in:mensual,trimestral,semestral,anual',
            'fecha_inicio' => 'required|date',
            'fecha_vencimiento' => 'required|date|after_or_equal:fecha_inicio',
            'auto_renovacion' => 'boolean',
            'notas' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $servicio = ServicioContratado::create([
                'cliente_id' => $validated['cliente_id'],
                'servicio_id' => $validated['servicio_id'],
                'precio' => $validated['precio'],
                'moneda' => $validated['moneda'],
                'periodo_facturacion' => $validated['periodo_facturacion'],
                'fecha_inicio' => $validated['fecha_inicio'],
                'fecha_vencimiento' => $validated['fecha_vencimiento'],
                'fecha_proximo_pago' => $validated['fecha_vencimiento'],
                'estado' => 'activo',
                'auto_renovacion' => $request->boolean('auto_renovacion', true),
                'notas' => $validated['notas'] ?? null,
                'usuario_creacion' => auth()->user()->usuario ?? 'Sistema',
            ]);

            DB::commit();

            Log::info('Servicio contratado', [
                'contrato_id' => $servicio->id,
                'cliente_id' => $validated['cliente_id'],
                'servicio_id' => $validated['servicio_id'],
            ]);

            return redirect()
                ->route('servicios.show', $servicio)
                ->with('success', 'Servicio contratado exitosamente');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al contratar servicio', ['error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'Error al contratar servicio: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ServicioContratado $servicio): View
    {
        $servicio->load(['cliente', 'catalogoServicio', 'envios']);

        return view('servicios.show', compact('servicio'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServicioContratado $servicio): View
    {
        $servicio->load(['cliente', 'catalogoServicio']);

        $catalogoServicios = CatalogoServicio::activo()
            ->orderBy('orden_visualizacion')
            ->orderBy('nombre')
            ->get();

        return view('servicios.edit', compact('servicio', 'catalogoServicios'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServicioContratado $servicio): RedirectResponse
    {
        $validated = $request->validate([
            'precio' => 'required|numeric|min:0',
            'moneda' => 'required|in:PEN,USD',
            'periodo_facturacion' => 'required|in:mensual,trimestral,semestral,anual',
            'fecha_vencimiento' => 'required|date',
            'auto_renovacion' => 'boolean',
            'notas' => 'nullable|string',
        ]);

        try {
            $servicio->update([
                'precio' => $validated['precio'],
                'moneda' => $validated['moneda'],
                'periodo_facturacion' => $validated['periodo_facturacion'],
                'fecha_vencimiento' => $validated['fecha_vencimiento'],
                'auto_renovacion' => $request->boolean('auto_renovacion'),
                'notas' => $validated['notas'] ?? null,
            ]);

            Log::info('Servicio actualizado', [
                'contrato_id' => $servicio->id,
                'campos_actualizados' => array_keys($validated)
            ]);

            return redirect()
                ->route('servicios.show', $servicio)
                ->with('success', 'Servicio actualizado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al actualizar servicio', ['error' => $e->getMessage()]);

            return back()
                ->withInput()
                ->with('error', 'Error al actualizar servicio: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage (cancelar, no eliminar físicamente).
     */
    public function destroy(Request $request, ServicioContratado $servicio): RedirectResponse
    {
        try {
            $servicio->update([
                'estado' => 'cancelado',
                'motivo_suspension' => $request->input('motivo', 'Cancelado manualmente')
            ]);

            Log::info('Servicio cancelado', [
                'contrato_id' => $servicio->id,
                'motivo' => $request->input('motivo')
            ]);

            return redirect()
                ->route('servicios.index')
                ->with('success', 'Servicio cancelado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al cancelar servicio', ['error' => $e->getMessage()]);

            return back()
                ->with('error', 'Error al cancelar servicio: ' . $e->getMessage());
        }
    }

    /**
     * Suspender un servicio
     */
    public function suspender(Request $request, ServicioContratado $servicio): RedirectResponse
    {
        if ($servicio->estado !== 'activo') {
            return back()->with('error', 'Solo se pueden suspender servicios activos');
        }

        $validated = $request->validate([
            'motivo' => 'required|string|max:500'
        ]);

        try {
            $servicio->update([
                'estado' => 'suspendido',
                'motivo_suspension' => $validated['motivo']
            ]);

            Log::info('Servicio suspendido', [
                'contrato_id' => $servicio->id,
                'motivo' => $validated['motivo']
            ]);

            return redirect()
                ->route('servicios.show', $servicio)
                ->with('success', 'Servicio suspendido exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al suspender servicio', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al suspender servicio');
        }
    }

    /**
     * Reactivar un servicio
     */
    public function reactivar(Request $request, ServicioContratado $servicio): RedirectResponse
    {
        if ($servicio->estado !== 'suspendido') {
            return back()->with('error', 'Solo se pueden reactivar servicios suspendidos');
        }

        $validated = $request->validate([
            'nueva_fecha_vencimiento' => 'required|date|after:today'
        ]);

        try {
            $servicio->update([
                'estado' => 'activo',
                'motivo_suspension' => null,
                'fecha_vencimiento' => $validated['nueva_fecha_vencimiento']
            ]);

            Log::info('Servicio reactivado', [
                'contrato_id' => $servicio->id,
                'nueva_fecha_vencimiento' => $validated['nueva_fecha_vencimiento']
            ]);

            return redirect()
                ->route('servicios.show', $servicio)
                ->with('success', 'Servicio reactivado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al reactivar servicio', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al reactivar servicio');
        }
    }

    /**
     * API: Obtener servicios de un cliente
     */
    public function serviciosCliente(Request $request): JsonResponse
    {
        $clienteId = $request->get('cliente_id');

        if (!$clienteId) {
            return response()->json([
                'success' => false,
                'error' => 'ID de cliente requerido'
            ], 400);
        }

        try {
            $servicios = ServicioContratado::with('catalogoServicio')
                ->where('cliente_id', $clienteId)
                ->orderBy('fecha_vencimiento', 'asc')
                ->get();

            // Calcular días restantes
            foreach ($servicios as $servicio) {
                $servicio->dias_restantes = Carbon::now('America/Lima')
                    ->diffInDays($servicio->fecha_vencimiento, false);
            }

            return response()->json([
                'success' => true,
                'data' => $servicios
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Migrar servicio a otro plan
     */
    public function migrarPlan(Request $request, ServicioContratado $servicio): RedirectResponse
    {
        if ($servicio->estado !== 'activo') {
            return back()->with('error', 'Solo se pueden migrar servicios activos');
        }

        $validated = $request->validate([
            'nuevo_servicio_id' => 'required|exists:catalogo_servicios,id',
            'precio' => 'required|numeric|min:0',
            'moneda' => 'required|in:PEN,USD',
            'fecha_vencimiento' => 'required|date', // Permitir cualquier fecha (incluso pasadas)
            'motivo_migracion' => 'nullable|string|max:500',
            'generar_orden_inmediata' => 'nullable|boolean'
        ]);

        try {
            DB::beginTransaction();

            // Obtener información del nuevo servicio
            $nuevoServicioCatalogo = CatalogoServicio::findOrFail($validated['nuevo_servicio_id']);

            // Extraer periodicidad del nombre del nuevo servicio
            $periodoNuevo = $this->extraerPeriodicidad($nuevoServicioCatalogo->nombre);

            // 1. Suspender servicio actual
            $motivo = 'Migrado a: ' . $nuevoServicioCatalogo->nombre;
            if (!empty($validated['motivo_migracion'])) {
                $motivo .= ' - ' . $validated['motivo_migracion'];
            }
            $motivo .= ' (' . now()->format('d/m/Y') . ')';

            $servicio->update([
                'estado' => 'suspendido',
                'motivo_suspension' => $motivo
            ]);

            // 2. Crear nuevo servicio
            $configuracion = [
                'migrado_desde' => [
                    'servicio_contratado_id' => $servicio->id,
                    'servicio_nombre' => $servicio->catalogoServicio->nombre,
                    'fecha_migracion' => now()->format('Y-m-d H:i:s'),
                    'usuario' => auth()->user()->nombre ?? 'Sistema',
                    'motivo' => $validated['motivo_migracion'] ?? 'Cambio de plan'
                ]
            ];

            // Si se marcó generar orden inmediata, agregar flag
            if ($request->boolean('generar_orden_inmediata')) {
                $configuracion['envio_inmediato'] = true;
                $configuracion['fecha_solicitud_envio'] = now()->format('Y-m-d H:i:s');
            }

            $nuevoServicio = ServicioContratado::create([
                'cliente_id' => $servicio->cliente_id,
                'servicio_id' => $validated['nuevo_servicio_id'],
                'precio' => $validated['precio'],
                'moneda' => $validated['moneda'],
                'periodo_facturacion' => $periodoNuevo,
                'fecha_inicio' => now()->format('Y-m-d'),
                'fecha_vencimiento' => $validated['fecha_vencimiento'],
                'fecha_proximo_pago' => $validated['fecha_vencimiento'],
                'estado' => 'activo',
                'auto_renovacion' => $servicio->auto_renovacion,
                'notas' => 'Migrado desde: ' . $servicio->catalogoServicio->nombre . ' (ID: ' . $servicio->id . ')',
                'usuario_creacion' => auth()->user()->usuario ?? 'Sistema',
                'configuracion' => $configuracion
            ]);

            DB::commit();

            Log::info('Servicio migrado', [
                'servicio_anterior_id' => $servicio->id,
                'servicio_nuevo_id' => $nuevoServicio->id,
                'cliente_id' => $servicio->cliente_id,
            ]);

            return redirect()
                ->route('servicios.show', $nuevoServicio)
                ->with('success', 'Servicio migrado exitosamente al nuevo plan');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al migrar servicio', ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al migrar servicio: ' . $e->getMessage());
        }
    }

    /**
     * API: Obtener servicios compatibles para migración
     */
    public function serviciosCompatibles(Request $request): JsonResponse
    {
        $servicioActualId = $request->get('servicio_id');

        if (!$servicioActualId) {
            return response()->json([
                'success' => false,
                'error' => 'ID de servicio requerido'
            ], 400);
        }

        try {
            $servicioActual = ServicioContratado::with('catalogoServicio')->findOrFail($servicioActualId);
            $categoriaActual = $servicioActual->catalogoServicio->categoria;

            // Obtener servicios de la misma categoría, excluyendo el actual
            $serviciosCompatibles = CatalogoServicio::activo()
                ->where('categoria', $categoriaActual)
                ->where('id', '!=', $servicioActual->servicio_id)
                ->orderBy('orden_visualizacion')
                ->orderBy('nombre')
                ->get()
                ->map(function ($servicio) {
                    return [
                        'id' => $servicio->id,
                        'nombre' => $servicio->nombre,
                        'descripcion' => $servicio->descripcion,
                        'precio_base' => $servicio->precio_base,
                        'moneda' => $servicio->moneda,
                        'categoria' => $servicio->categoria
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $serviciosCompatibles,
                'servicio_actual' => [
                    'nombre' => $servicioActual->catalogoServicio->nombre,
                    'categoria' => $categoriaActual
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extraer periodicidad del nombre del servicio
     */
    private function extraerPeriodicidad(string $nombreServicio): string
    {
        $nombre = strtolower($nombreServicio);

        if (str_contains($nombre, 'mensual')) {
            return 'mensual';
        } elseif (str_contains($nombre, 'trimestral')) {
            return 'trimestral';
        } elseif (str_contains($nombre, 'semestral')) {
            return 'semestral';
        } elseif (str_contains($nombre, 'anual')) {
            return 'anual';
        }

        // Por defecto mensual
        return 'mensual';
    }

    /**
     * Obtener estadísticas
     */
    private function getEstadisticas(): array
    {
        $stats = DB::table('servicios_contratados')
            ->selectRaw('
                COUNT(*) as total_contratos,
                SUM(CASE WHEN estado = "activo" THEN 1 ELSE 0 END) as activos,
                SUM(CASE WHEN estado = "suspendido" THEN 1 ELSE 0 END) as suspendidos,
                SUM(CASE WHEN estado = "cancelado" THEN 1 ELSE 0 END) as cancelados
            ')
            ->first();

        return (array) $stats;
    }
}
