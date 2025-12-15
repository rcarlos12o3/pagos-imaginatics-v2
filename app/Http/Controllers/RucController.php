<?php

namespace App\Http\Controllers;

use App\Models\ConsultaRuc;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class RucController extends Controller
{
    /**
     * Consultar RUC con caché de 24 horas
     */
    public function consultar(Request $request): JsonResponse
    {
        $ruc = $request->get('ruc');

        if (empty($ruc)) {
            return response()->json([
                'success' => false,
                'error' => 'RUC requerido'
            ], 400);
        }

        // Validar RUC
        $rucValidado = $this->validarRuc($ruc);
        if (!$rucValidado['valid']) {
            return response()->json([
                'success' => false,
                'error' => $rucValidado['message']
            ], 400);
        }

        $ruc = $rucValidado['ruc'];

        try {
            // Verificar caché (últimas 24 horas)
            $cache = ConsultaRuc::porRuc($ruc)
                ->exitosas()
                ->recientes()
                ->orderBy('fecha_consulta', 'desc')
                ->first();

            if ($cache) {
                Log::info('Consulta RUC desde caché', [
                    'ruc' => $ruc,
                    'cache_fecha' => $cache->fecha_consulta
                ]);

                return response()->json([
                    'success' => true,
                    'data' => $cache->respuesta_api,
                    'source' => 'cache',
                    'cache_date' => $cache->fecha_consulta->format('Y-m-d H:i:s')
                ]);
            }

            // No hay caché, consultar API externa
            $resultado = $this->consultarApiExterna($ruc, $request->ip());

            if ($resultado['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $resultado['data'],
                    'source' => 'api'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $resultado['error']
                ], $resultado['status_code'] ?? 500);
            }

        } catch (\Exception $e) {
            Log::error('Error en consulta RUC', [
                'ruc' => $ruc,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Consultar API externa (Factiliza)
     */
    private function consultarApiExterna(string $ruc, string $ipOrigen): array
    {
        // Obtener token de configuración
        $token = DB::table('configuracion')
            ->where('clave', 'token_ruc')
            ->value('valor');

        if (!$token) {
            return [
                'success' => false,
                'error' => 'Token de API no configurado',
                'status_code' => 500
            ];
        }

        $url = "https://api.factiliza.com/v1/ruc/info/{$ruc}";

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Authorization' => "Bearer {$token}",
                    'Content-Type' => 'application/json',
                    'User-Agent' => 'Imaginatics-RUC-Consultor/1.0'
                ])
                ->get($url);

            $httpCode = $response->status();
            $data = $response->json();

            if ($httpCode === 200 && $data && isset($data['success']) && $data['success']) {
                // Consulta exitosa
                $responseData = [
                    'nombre_o_razon_social' => $data['data']['nombre_o_razon_social'] ?? '',
                    'estado' => $data['data']['estado'] ?? '',
                    'condicion' => $data['data']['condicion'] ?? '',
                    'direccion' => $data['data']['direccion'] ?? '',
                    'ubigeo' => $data['data']['ubigeo'] ?? '',
                    'tipo_contribuyente' => $data['data']['tipo_contribuyente'] ?? '',
                    'fecha_inscripcion' => $data['data']['fecha_inscripcion'] ?? '',
                    'fecha_inicio_actividades' => $data['data']['fecha_inicio_actividades'] ?? '',
                    'actividad_economica' => $data['data']['actividad_economica'] ?? []
                ];

                // Guardar en caché
                ConsultaRuc::create([
                    'ruc' => $ruc,
                    'respuesta_api' => $responseData,
                    'estado_consulta' => 'exitosa',
                    'ip_origen' => $ipOrigen
                ]);

                Log::info('Consulta RUC API exitosa', [
                    'ruc' => $ruc,
                    'razon_social' => $responseData['nombre_o_razon_social']
                ]);

                return [
                    'success' => true,
                    'data' => $responseData
                ];

            } else {
                // Error en la respuesta
                $estadoConsulta = 'error';
                $errorMessage = 'Error desconocido';

                switch ($httpCode) {
                    case 400:
                        $estadoConsulta = 'no_encontrado';
                        $errorMessage = 'RUC no válido o no encontrado';
                        break;
                    case 401:
                        $errorMessage = 'Token de acceso inválido';
                        break;
                    case 429:
                        $errorMessage = 'Límite de consultas excedido. Intente más tarde';
                        break;
                    case 500:
                        $errorMessage = 'Error interno del servidor de la API';
                        break;
                    default:
                        if (isset($data['message'])) {
                            $errorMessage = $data['message'];
                        }
                }

                // Guardar error en logs
                ConsultaRuc::create([
                    'ruc' => $ruc,
                    'respuesta_api' => $response->body(),
                    'estado_consulta' => $estadoConsulta,
                    'ip_origen' => $ipOrigen
                ]);

                Log::warning('Error en consulta RUC API', [
                    'ruc' => $ruc,
                    'http_code' => $httpCode,
                    'error' => $errorMessage
                ]);

                return [
                    'success' => false,
                    'error' => $errorMessage,
                    'status_code' => $httpCode
                ];
            }

        } catch (\Exception $e) {
            // Error de conexión
            ConsultaRuc::create([
                'ruc' => $ruc,
                'respuesta_api' => ['error' => $e->getMessage()],
                'estado_consulta' => 'error',
                'ip_origen' => $ipOrigen
            ]);

            Log::error('Error de conexión con API RUC', [
                'ruc' => $ruc,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => 'Error de conexión con la API',
                'status_code' => 500
            ];
        }
    }

    /**
     * Validar formato de RUC
     */
    private function validarRuc(string $ruc): array
    {
        $ruc = preg_replace('/[^0-9]/', '', $ruc);

        if (strlen($ruc) !== 11) {
            return ['valid' => false, 'message' => 'El RUC debe tener 11 dígitos'];
        }

        if (!ctype_digit($ruc)) {
            return ['valid' => false, 'message' => 'El RUC debe contener solo números'];
        }

        return ['valid' => true, 'ruc' => $ruc];
    }

    /**
     * Limpiar caché de un RUC específico
     */
    public function limpiarCache(Request $request): JsonResponse
    {
        $ruc = $request->get('ruc');

        if (empty($ruc)) {
            return response()->json([
                'success' => false,
                'error' => 'RUC requerido'
            ], 400);
        }

        $rucValidado = $this->validarRuc($ruc);
        if (!$rucValidado['valid']) {
            return response()->json([
                'success' => false,
                'error' => $rucValidado['message']
            ], 400);
        }

        try {
            $deleted = ConsultaRuc::where('ruc', $rucValidado['ruc'])->delete();

            Log::info('Cache RUC limpiado', [
                'ruc' => $rucValidado['ruc'],
                'registros_eliminados' => $deleted
            ]);

            return response()->json([
                'success' => true,
                'message' => "Cache limpiado para RUC {$rucValidado['ruc']}",
                'deleted_records' => $deleted
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Estadísticas de consultas RUC
     */
    public function estadisticas(): JsonResponse
    {
        try {
            $stats = DB::table('consultas_ruc')
                ->selectRaw('
                    COUNT(*) as total_consultas,
                    COUNT(DISTINCT ruc) as rucs_unicos,
                    SUM(CASE WHEN estado_consulta = "exitosa" THEN 1 ELSE 0 END) as exitosas,
                    SUM(CASE WHEN estado_consulta = "error" THEN 1 ELSE 0 END) as errores,
                    SUM(CASE WHEN estado_consulta = "no_encontrado" THEN 1 ELSE 0 END) as no_encontrados,
                    COUNT(CASE WHEN fecha_consulta >= CURDATE() THEN 1 END) as consultas_hoy,
                    COUNT(CASE WHEN fecha_consulta >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 END) as consultas_semana
                ')
                ->first();

            // Top RUCs más consultados
            $topRucs = DB::table('consultas_ruc')
                ->select('ruc', DB::raw('COUNT(*) as consultas'))
                ->groupBy('ruc')
                ->orderByDesc('consultas')
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'resumen' => $stats,
                    'top_rucs' => $topRucs
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
