<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ColaEnvio;
use App\Models\SesionEnvio;
use App\Models\EnvioWhatsapp;
use App\Jobs\EnviarComunicacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ComunicacionController extends Controller
{
    /**
     * Muestra la vista principal de comunicaciones
     */
    public function index()
    {
        return view('comunicaciones.index');
    }

    /**
     * Obtiene la lista de clientes activos con WhatsApp
     */
    public function obtenerClientes()
    {
        try {
            $clientes = Cliente::where('activo', true)
                ->whereNotNull('whatsapp')
                ->where('whatsapp', '!=', '')
                ->select('id', 'ruc', 'razon_social', 'nombre_comercial', 'whatsapp', 'email')
                ->orderBy('razon_social')
                ->get();

            return response()->json([
                'success' => true,
                'clientes' => $clientes
            ]);
        } catch (\Exception $e) {
            Log::error('Error al obtener clientes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al cargar clientes'
            ], 500);
        }
    }

    /**
     * Envía comunicaciones a los clientes seleccionados (usando cola)
     */
    public function enviar(Request $request)
    {
        $validated = $request->validate([
            'clientes' => 'required|array|min:1',
            'clientes.*' => 'exists:clientes,id',
            'mensaje' => 'required|string|max:4000',
            'imagen' => 'nullable|string', // base64
            'confirmar_sin_imagen' => 'nullable|boolean'
        ]);

        // Validar si no hay imagen y no se confirmó
        if (empty($validated['imagen']) && !($validated['confirmar_sin_imagen'] ?? false)) {
            return response()->json([
                'success' => false,
                'requiere_confirmacion' => true,
                'message' => '¿Estás seguro de enviar sin imagen?'
            ]);
        }

        try {
            $clientes = Cliente::whereIn('id', $validated['clientes'])->get();

            // Validar que todos tengan WhatsApp
            $sinWhatsapp = $clientes->filter(fn($c) => empty($c->whatsapp));
            if ($sinWhatsapp->isNotEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Algunos clientes no tienen WhatsApp registrado'
                ], 400);
            }

            // Crear sesión de envío
            $sesion = SesionEnvio::create([
                'tipo_envio' => 'comunicacion',
                'total_clientes' => $clientes->count(),
                'procesados' => 0,
                'exitosos' => 0,
                'fallidos' => 0,
                'estado' => 'pendiente',
                'fecha_creacion' => Carbon::now('America/Lima'),
            ]);

            $delaySegundos = 0;

            // Crear trabajos en cola para cada cliente
            foreach ($clientes as $cliente) {
                // Construir mensaje personalizado
                $mensajeCompleto = "Hola {$cliente->razon_social}\n\n{$validated['mensaje']}";

                // Formatear número
                $numeroFormateado = $this->formatearNumero($cliente->whatsapp);

                // Crear trabajo en cola
                $trabajo = ColaEnvio::create([
                    'sesion_id' => $sesion->id,
                    'cliente_id' => $cliente->id,
                    'tipo_envio' => 'comunicacion',
                    'estado' => 'pendiente',
                    'intentos' => 0,
                    'max_intentos' => 3,
                    'ruc' => $cliente->ruc,
                    'razon_social' => $cliente->razon_social,
                    'whatsapp' => $numeroFormateado,
                    'monto' => 0.00, // No aplica para comunicaciones
                    'fecha_vencimiento' => Carbon::now('America/Lima'), // No aplica para comunicaciones
                    'mensaje_texto' => $mensajeCompleto,
                    'imagen_base64' => $validated['imagen'] ?? null,
                    'fecha_creacion' => Carbon::now('America/Lima'),
                ]);

                // Despachar Job con delay acumulativo (30-90 segundos por cliente)
                $delaySegundos += rand(30, 90);

                EnviarComunicacion::dispatch($trabajo->id)
                    ->delay(now()->addSeconds($delaySegundos));
            }

            return response()->json([
                'success' => true,
                'sesion_id' => $sesion->id,
                'total' => $clientes->count(),
                'message' => "Se han programado {$clientes->count()} envíos. Los mensajes se enviarán automáticamente en los próximos minutos."
            ]);

        } catch (\Exception $e) {
            Log::error('Error al programar comunicaciones: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error al programar comunicaciones: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Formatea número de WhatsApp (agrega código de país Perú si es necesario)
     */
    private function formatearNumero(string $numero): string
    {
        $numeroLimpio = preg_replace('/[^0-9]/', '', $numero);

        // Si es número peruano de 9 dígitos, agregar código de país
        if (strlen($numeroLimpio) === 9) {
            return '51' . $numeroLimpio;
        }

        return $numeroLimpio;
    }
}
