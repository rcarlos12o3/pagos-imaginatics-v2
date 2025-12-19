<?php

namespace App\Http\Controllers;

use App\Models\HistorialPago;
use App\Models\ServicioContratado;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PagoController extends Controller
{
    /**
     * Mostrar listado de pagos con estadísticas
     */
    public function index(Request $request): View
    {
        // Obtener mes y año actuales o de los filtros
        $mes = $request->input('mes', now()->month);
        $anio = $request->input('anio', now()->year);

        // Construir query base
        $query = HistorialPago::with('cliente')
            ->join('clientes', 'historial_pagos.cliente_id', '=', 'clientes.id');

        // Búsqueda instantánea
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('clientes.ruc', 'like', "%{$search}%")
                  ->orWhere('clientes.razon_social', 'like', "%{$search}%")
                  ->orWhere('clientes.nombre_comercial', 'like', "%{$search}%");
            });
        }

        // Filtro por mes y año
        if ($mes && $anio) {
            $query->whereYear('historial_pagos.fecha_pago', $anio)
                  ->whereMonth('historial_pagos.fecha_pago', $mes);
        }

        // Filtro por método
        if ($request->filled('metodo')) {
            $query->where('historial_pagos.metodo_pago', $request->input('metodo'));
        }

        // Filtro por banco
        if ($request->filled('banco')) {
            $query->where('historial_pagos.banco', $request->input('banco'));
        }

        // Seleccionar columnas específicas para evitar conflictos
        $query->select('historial_pagos.*');

        // Obtener pagos paginados
        $pagos = $query->orderBy('historial_pagos.fecha_pago', 'desc')
                       ->orderBy('historial_pagos.id', 'desc')
                       ->paginate(15)
                       ->withQueryString();

        // Calcular estadísticas del mes actual
        $estadisticasMes = HistorialPago::whereYear('fecha_pago', now()->year)
            ->whereMonth('fecha_pago', now()->month)
            ->selectRaw('
                COALESCE(SUM(monto_pagado), 0) as total_recaudado,
                COUNT(*) as cantidad_pagos,
                COALESCE(AVG(monto_pagado), 0) as pago_promedio,
                COALESCE(MAX(monto_pagado), 0) as mayor_pago
            ')
            ->first();

        // Obtener lista de bancos únicos para el filtro
        $bancos = HistorialPago::whereNotNull('banco')
            ->distinct()
            ->pluck('banco')
            ->filter()
            ->sort()
            ->values();

        return view('pagos.index', compact('pagos', 'estadisticasMes', 'bancos', 'mes', 'anio'));
    }

    /**
     * Mostrar formulario para registrar pago
     */
    public function create(Request $request)
    {
        $clienteId = $request->get('cliente_id');
        $servicioId = $request->get('servicio_id');

        $cliente = null;
        $serviciosSeleccionados = [];

        if ($clienteId) {
            $cliente = Cliente::findOrFail($clienteId);

            // Si hay un servicio específico, lo incluimos
            if ($servicioId) {
                $serviciosSeleccionados = ServicioContratado::where('id', $servicioId)
                    ->where('cliente_id', $clienteId)
                    ->with('catalogoServicio')
                    ->get();
            } else {
                // Obtener todos los servicios activos del cliente
                $serviciosSeleccionados = ServicioContratado::where('cliente_id', $clienteId)
                    ->where('estado', 'activo')
                    ->with('catalogoServicio')
                    ->get();
            }
        }

        return view('pagos.create', compact('cliente', 'serviciosSeleccionados'));
    }

    /**
     * Registrar nuevo pago
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'cliente_id' => 'required|exists:clientes,id',
            'servicios_pagados' => 'nullable|array',
            'servicios_pagados.*' => 'exists:servicios_contratados,id',
            'monto_pagado' => 'required|numeric|min:0',
            'fecha_pago' => 'required|date',
            'metodo_pago' => 'required|in:transferencia,deposito,yape,plin,efectivo,otro',
            'numero_operacion' => 'nullable|string|max:50',
            'banco' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            // Crear el registro de pago
            $pago = HistorialPago::create([
                'cliente_id' => $validated['cliente_id'],
                'monto_pagado' => $validated['monto_pagado'],
                'fecha_pago' => $validated['fecha_pago'],
                'metodo_pago' => $validated['metodo_pago'],
                'numero_operacion' => $validated['numero_operacion'] ?? null,
                'banco' => $validated['banco'] ?? null,
                'observaciones' => $validated['observaciones'] ?? null,
                'registrado_por' => Auth::user()->nombre,
                'servicios_pagados' => $validated['servicios_pagados'] ?? []
            ]);

            // Si hay servicios asociados, actualizar su fecha de último pago y renovar vencimiento
            if (!empty($validated['servicios_pagados'])) {
                foreach ($validated['servicios_pagados'] as $servicioId) {
                    $servicio = ServicioContratado::find($servicioId);

                    if ($servicio) {
                        // Actualizar fecha de última factura
                        $servicio->fecha_ultima_factura = $validated['fecha_pago'];

                        // Renovar fecha de vencimiento según el periodo
                        if ($servicio->auto_renovacion) {
                            $nuevaFechaVencimiento = $this->calcularNuevaFechaVencimiento(
                                $servicio->fecha_vencimiento,
                                $servicio->periodo_facturacion
                            );
                            $servicio->fecha_vencimiento = $nuevaFechaVencimiento;
                        }

                        $servicio->save();
                    }
                }
            }

            DB::commit();

            return redirect()
                ->route('pagos-pendientes.index')
                ->with('success', 'Pago registrado correctamente');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->withErrors(['error' => 'Error al registrar el pago: ' . $e->getMessage()]);
        }
    }

    /**
     * Mostrar detalles de un pago (JSON para modal)
     */
    public function show(HistorialPago $pago)
    {
        $pago->load('cliente');

        // Obtener servicios asociados
        $servicios = [];
        if ($pago->servicios_pagados) {
            $servicios = ServicioContratado::whereIn('id', $pago->servicios_pagados)
                ->with('catalogoServicio')
                ->get();
        }

        return response()->json([
            'success' => true,
            'pago' => $pago,
            'servicios' => $servicios
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(HistorialPago $pago): View
    {
        $pago->load('cliente');

        return view('pagos.edit', compact('pago'));
    }

    /**
     * Actualizar pago
     */
    public function update(Request $request, HistorialPago $pago): RedirectResponse
    {
        $validated = $request->validate([
            'monto_pagado' => 'required|numeric|min:0',
            'fecha_pago' => 'required|date',
            'metodo_pago' => 'required|in:transferencia,deposito,yape,plin,efectivo,otro',
            'numero_operacion' => 'nullable|string|max:50',
            'banco' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string'
        ]);

        $pago->update($validated);

        return redirect()->route('pagos.index')
            ->with('success', 'Pago actualizado exitosamente');
    }

    /**
     * Eliminar pago
     */
    public function destroy(HistorialPago $pago): RedirectResponse
    {
        try {
            $pago->delete();

            return redirect()->route('pagos.index')
                ->with('success', 'Pago eliminado exitosamente');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Error al eliminar el pago: ' . $e->getMessage()]);
        }
    }

    /**
     * Calcular nueva fecha de vencimiento según el periodo
     */
    private function calcularNuevaFechaVencimiento($fechaActual, $periodo)
    {
        $fecha = Carbon::parse($fechaActual);

        switch ($periodo) {
            case 'mensual':
                return $fecha->addMonth()->format('Y-m-d');
            case 'trimestral':
                return $fecha->addMonths(3)->format('Y-m-d');
            case 'semestral':
                return $fecha->addMonths(6)->format('Y-m-d');
            case 'anual':
                return $fecha->addYear()->format('Y-m-d');
            default:
                return $fecha->addMonth()->format('Y-m-d');
        }
    }
}
