<?php

namespace App\Http\Controllers;

use App\Models\EnvioWhatsapp;
use App\Models\ServicioContratado;
use App\Models\SesionEnvio;
use App\Models\ColaEnvio;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class EnvioController extends Controller
{
    /**
     * Mostrar lista de envíos con filtros
     */
    public function index(Request $request): View
    {
        $query = EnvioWhatsapp::with('cliente')
            ->orderBy('fecha_envio', 'desc');

        // Aplicar filtros
        if ($request->filled('search')) {
            $query->buscarCliente($request->search);
        }

        if ($request->filled('tipo')) {
            $query->tipoEnvio($request->tipo);
        }

        if ($request->filled('estado')) {
            $query->estado($request->estado);
        }

        $envios = $query->paginate(50);

        // Estadísticas
        $estadisticas = $this->getEstadisticas();

        return view('historial.index', compact('envios', 'estadisticas'));
    }

    /**
     * Obtener estadísticas de envíos
     */
    private function getEstadisticas(): array
    {
        return [
            'total' => EnvioWhatsapp::count(),
            'enviados' => EnvioWhatsapp::exitosos()->count(),
            'errores' => EnvioWhatsapp::conError()->count(),
            'ordenes_pago' => EnvioWhatsapp::where('tipo_envio', 'orden_pago')->count(),
            'hoy' => EnvioWhatsapp::hoy()->count(),
            'mes' => EnvioWhatsapp::mesActual()->count(),
        ];
    }

    /**
     * Actualizar un envío (fecha y estado)
     */
    public function update(Request $request, EnvioWhatsapp $envio): RedirectResponse
    {
        $validated = $request->validate([
            'fecha_envio' => 'required|date',
            'estado' => 'required|in:enviado,pendiente,error',
        ]);

        $envio->update($validated);

        return redirect()->route('historial.index')
            ->with('success', 'Envío actualizado correctamente');
    }

    /**
     * Eliminar un envío
     */
    public function destroy(EnvioWhatsapp $envio): RedirectResponse
    {
        $clienteNombre = $envio->cliente->razon_social;

        $envio->delete();

        return redirect()->route('historial.index')
            ->with('success', "Envío del cliente {$clienteNombre} eliminado correctamente");
    }

    /**
     * API: Obtener estadísticas en JSON
     */
    public function stats(): JsonResponse
    {
        $estadisticas = $this->getEstadisticas();

        // Estadísticas por tipo
        $porTipo = EnvioWhatsapp::select('tipo_envio', DB::raw('count(*) as total'))
            ->groupBy('tipo_envio')
            ->get();

        // Estadísticas por día (últimos 7 días)
        $porDia = EnvioWhatsapp::select(
                DB::raw('DATE(fecha_envio) as fecha'),
                DB::raw('COUNT(*) as total'),
                DB::raw("SUM(CASE WHEN estado = 'enviado' THEN 1 ELSE 0 END) as exitosos")
            )
            ->where('fecha_envio', '>=', now()->subDays(7))
            ->groupBy(DB::raw('DATE(fecha_envio)'))
            ->orderBy('fecha', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'resumen' => $estadisticas,
                'por_tipo' => $porTipo,
                'por_dia' => $porDia,
            ],
        ]);
    }

    /**
     * API: Historial de envíos de un cliente
     */
    public function historialCliente(Request $request): JsonResponse
    {
        $clienteId = $request->get('cliente_id');

        if (!$clienteId) {
            return response()->json([
                'success' => false,
                'error' => 'Cliente ID requerido',
            ], 400);
        }

        $envios = EnvioWhatsapp::where('cliente_id', $clienteId)
            ->orderBy('fecha_envio', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $envios,
        ]);
    }

    /**
     * Mostrar módulo de envíos con análisis inteligente
     */
    public function envios(): View
    {
        return view('envios.index');
    }

    /**
     * API: Analizar servicios pendientes de envío
     */
    public function analizarEnviosPendientes(): JsonResponse
    {
        try {
            $hoy = Carbon::now('America/Lima');

            // Obtener todos los servicios activos con datos del cliente
            $servicios = ServicioContratado::with(['cliente', 'catalogoServicio'])
                ->where('estado', 'activo')
                ->whereHas('cliente', function ($q) {
                    $q->where('activo', true);
                })
                ->get();

            $resultados = [];
            $debenEnviarse = 0;

            foreach ($servicios as $servicio) {
                $analisis = $this->analizarServicio($servicio, $hoy);
                $resultados[] = $analisis;

                if ($analisis['debe_enviarse']) {
                    $debenEnviarse++;
                }
            }

            // Ordenar: primero los atrasados, luego los en plazo ideal, luego los demás
            usort($resultados, function($a, $b) {
                $orden = [
                    'fuera_del_plazo' => 1,
                    'dentro_del_plazo_ideal' => 2,
                    'ya_enviado' => 3,
                    'pendiente' => 4
                ];
                return ($orden[$a['estado']] ?? 5) <=> ($orden[$b['estado']] ?? 5);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'servicios' => $resultados,
                    'total' => count($resultados),
                    'deben_enviarse' => $debenEnviarse,
                    'fecha_analisis' => $hoy->format('Y-m-d H:i:s'),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Analizar un servicio individual
     */
    private function analizarServicio(ServicioContratado $servicio, Carbon $hoy): array
    {
        $periodo = $servicio->periodo_facturacion;
        $fechaVencimiento = Carbon::parse($servicio->fecha_vencimiento, 'America/Lima');
        $fechaInicio = Carbon::parse($servicio->fecha_inicio, 'America/Lima');

        // Normalizar fechas a solo día (sin horas) para comparaciones
        $hoySoloFecha = Carbon::parse($hoy->format('Y-m-d'), 'America/Lima');
        $fechaVencimientoSoloFecha = Carbon::parse($fechaVencimiento->format('Y-m-d'), 'America/Lima');

        // Días de anticipación según periodicidad
        $diasAnticipacion = match($periodo) {
            'mensual' => 4,
            'trimestral' => 7,
            'semestral' => 15,
            'anual' => 30,
            default => 7,
        };

        // Calcular fecha ideal de envío
        $fechaIdealEnvio = Carbon::parse($fechaVencimientoSoloFecha->format('Y-m-d'), 'America/Lima')->subDays($diasAnticipacion);

        // Días hasta vencer (solo días enteros)
        $diasHastaVencer = (int) $hoySoloFecha->diffInDays($fechaVencimientoSoloFecha, false);

        // Determinar estado
        $estado = '';
        $debeEnviarse = false;

        if ($diasHastaVencer < 0) {
            // Ya venció - debe enviarse
            $estado = 'fuera_del_plazo';
            $debeEnviarse = true;
        } elseif ($hoySoloFecha->greaterThanOrEqualTo($fechaIdealEnvio) && $hoySoloFecha->lessThanOrEqualTo($fechaVencimientoSoloFecha)) {
            // Dentro de la ventana ideal
            $estado = 'dentro_del_plazo_ideal';
            $debeEnviarse = true;
        } else {
            // Aún no llega la fecha ideal
            $estado = 'pendiente';
            $debeEnviarse = false;
        }

        // ⚠️ VALIDACIÓN CRÍTICA: Verificar si ya se envió orden HOY a este cliente
        // (sin importar el servicio - máximo 1 orden por cliente por día)
        $yaEnviadoHoy = EnvioWhatsapp::where('cliente_id', $servicio->cliente_id)
            ->where('tipo_envio', 'orden_pago')
            ->whereDate('fecha_envio', $hoy->format('Y-m-d'))
            ->exists();

        if ($yaEnviadoHoy) {
            $debeEnviarse = false;
            $estado = 'ya_enviado';
        }

        // Calcular siguiente vencimiento
        $siguienteVencimiento = $this->calcularSiguienteVencimiento($fechaVencimiento, $periodo);

        return [
            'contrato_id' => $servicio->id,
            'cliente_id' => $servicio->cliente_id,
            'empresa' => $servicio->cliente->razon_social,
            'ruc' => $servicio->cliente->ruc,
            'whatsapp' => $servicio->cliente->whatsapp,
            'servicio_nombre' => $servicio->catalogoServicio->nombre,
            'periodicidad' => $periodo,
            'precio' => $servicio->precio,
            'moneda' => $servicio->moneda,
            'fecha_inicio' => $fechaInicio->format('d/m/Y'),
            'fecha_vencimiento_periodo_actual' => $fechaVencimiento->format('d/m/Y'),
            'fecha_ideal_envio' => $fechaIdealEnvio->format('d/m/Y'),
            'dias_anticipacion' => $diasAnticipacion,
            'dias_hasta_vencer' => $diasHastaVencer,
            'estado' => $estado,
            'debe_enviarse' => $debeEnviarse,
            'siguiente_vencimiento' => $siguienteVencimiento->format('d/m/Y'),
        ];
    }

    /**
     * Calcular siguiente vencimiento según periodicidad
     */
    private function calcularSiguienteVencimiento(Carbon $fechaVencimiento, string $periodo): Carbon
    {
        $siguiente = $fechaVencimiento->copy();

        return match($periodo) {
            'mensual' => $siguiente->addMonth()->endOfMonth(),
            'trimestral' => $siguiente->addMonths(3)->subDay(),
            'semestral' => $siguiente->addMonths(6)->subDay(),
            'anual' => $siguiente->addYear()->subDay(),
            default => $siguiente,
        };
    }

    /**
     * API: Enviar órdenes de pago seleccionadas
     */
    public function enviarOrdenes(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'servicios' => 'required|array|min:1',
            'servicios.*.contrato_id' => 'required|integer',
            'servicios.*.cliente_id' => 'required|integer',
            'servicios.*.whatsapp' => 'required|string',
            'servicios.*.imagen_base64' => 'required|string',
        ]);

        try {
            DB::beginTransaction();

            $hoy = Carbon::now('America/Lima');

            // Crear sesión de envío
            $sesion = SesionEnvio::create([
                'tipo_envio' => 'orden_pago',
                'total_clientes' => count($validated['servicios']),
                'procesados' => 0,
                'exitosos' => 0,
                'fallidos' => 0,
                'estado' => 'pendiente',
                'configuracion' => [
                    'creado_desde' => 'modulo_envios_inteligente',
                    'timestamp' => $hoy->toIso8601String(),
                ],
                'fecha_creacion' => $hoy,
            ]);

            $trabajosAgregados = 0;

            // Agregar trabajos a la cola
            foreach ($validated['servicios'] as $servicioData) {
                // Obtener datos completos del servicio y cliente
                $servicio = ServicioContratado::with('cliente')->find($servicioData['contrato_id']);

                if (!$servicio || !$servicio->cliente) {
                    continue; // Saltar si no se encuentra el servicio
                }

                // ⚠️ VALIDACIÓN CRÍTICA: Verificar que NO se haya enviado orden hoy a este cliente
                $yaEnviadoHoy = EnvioWhatsapp::where('cliente_id', $servicio->cliente_id)
                    ->where('tipo_envio', 'orden_pago')
                    ->whereDate('fecha_envio', $hoy->format('Y-m-d'))
                    ->exists();

                if ($yaEnviadoHoy) {
                    continue; // Saltar este cliente - ya recibió orden hoy
                }

                $fechaVencimiento = Carbon::parse($servicio->fecha_vencimiento, 'America/Lima');
                $diasRestantes = $hoy->diffInDays($fechaVencimiento, false);

                ColaEnvio::create([
                    'sesion_id' => $sesion->id,
                    'cliente_id' => $servicio->cliente_id,
                    'servicio_contratado_id' => $servicio->id,
                    'tipo_envio' => 'orden_pago',
                    'prioridad' => $diasRestantes < 0 ? 10 : 5, // Mayor prioridad si ya venció
                    'estado' => 'pendiente',
                    'intentos' => 0,
                    'max_intentos' => 3,
                    'ruc' => $servicio->cliente->ruc,
                    'razon_social' => $servicio->cliente->razon_social,
                    'whatsapp' => $servicio->cliente->whatsapp,
                    'monto' => $servicio->precio,
                    'fecha_vencimiento' => $fechaVencimiento,
                    'tipo_servicio' => $servicio->periodo_facturacion,
                    'mensaje_texto' => "Hola! 👋\n\nLe recordamos que tiene una orden de pago pendiente por *{$servicio->moneda} {$servicio->precio}* que vence el {$fechaVencimiento->format('d/m/Y')}.\n\nPor favor, realice el pago a las cuentas indicadas en la imagen adjunta.\n\nGracias! 🙏",
                    'imagen_base64' => $servicioData['imagen_base64'],
                    'dias_restantes' => (int) $diasRestantes,
                    'fecha_creacion' => $hoy,
                ]);

                $trabajosAgregados++;

                // Despachar Job con delay aleatorio (simula comportamiento humano)
                \App\Jobs\ProcesarEnvioWhatsapp::dispatch($trabajo->id);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => [
                    'sesion_id' => $sesion->id,
                    'trabajos_agregados' => $trabajosAgregados,
                    'mensaje' => "✅ {$trabajosAgregados} órdenes agregadas a la cola. Se enviarán con delays de 2-5 segundos entre cada una (comportamiento humano).",
                ],
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'error' => 'Error al crear sesión de envío: ' . $e->getMessage(),
            ], 500);
        }
    }
}
