<?php

namespace App\Http\Controllers;

use App\Models\HistorialPago;
use App\Models\ServicioContratado;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PagoController extends Controller
{
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
