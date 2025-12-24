<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Imports\ClientesImport;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Session;

class ClienteController extends Controller
{
    public function index(Request $request): View
    {
        $query = Cliente::query();

        // Filtro de búsqueda
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function($q) use ($search) {
                $q->where('razon_social', 'like', "%{$search}%")
                  ->orWhere('ruc', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%");
            });
        }

        // Filtro de estado activo/inactivo
        if ($request->filled('activo')) {
            $query->where('activo', $request->input('activo'));
        }

        $clientes = $query->orderBy('razon_social')->paginate(15)->withQueryString();

        return view('clientes.index', compact('clientes'));
    }

    public function create(): View
    {
        return view('clientes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ruc' => 'required|string|max:11|unique:clientes,ruc',
            'tipo_documento' => 'required|in:RUC,DNI,CE,PASAPORTE',
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'whatsapp' => 'required|string|max:15',
            'email' => 'nullable|email|max:255',
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_cargo' => 'nullable|string|max:100',
            'direccion' => 'nullable|string',
            'estado_sunat' => 'nullable|string|max:50',
            'activo' => 'required|boolean',
        ]);

        Cliente::create($validated);

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente creado exitosamente');
    }

    public function show(Cliente $cliente): View
    {
        $cliente->load(['serviciosContratados', 'enviosWhatsapp']);

        return view('clientes.show', compact('cliente'));
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $validated = $request->validate([
            'ruc' => 'required|string|max:11|unique:clientes,ruc,' . $cliente->id,
            'tipo_documento' => 'required|in:RUC,DNI,CE,PASAPORTE',
            'razon_social' => 'required|string|max:255',
            'nombre_comercial' => 'nullable|string|max:255',
            'whatsapp' => 'required|string|max:15',
            'email' => 'nullable|email|max:255',
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_cargo' => 'nullable|string|max:100',
            'direccion' => 'nullable|string',
            'estado_sunat' => 'nullable|string|max:50',
            'activo' => 'required|boolean',
        ]);

        $cliente->update($validated);

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente actualizado exitosamente');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $cliente->delete();

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente eliminado exitosamente');
    }

    /**
     * Descargar plantilla CSV
     */
    public function descargarPlantilla()
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="plantilla_clientes.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');

            // Escribir BOM para UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            // Cabeceras
            fputcsv($file, ['RUC', 'RAZON_SOCIAL', 'WHATSAPP', 'ESTADO']);

            // Datos de ejemplo
            fputcsv($file, ['20123456789', 'Empresa Ejemplo SAC', '987654321', 'activo']);
            fputcsv($file, ['20987654321', 'Comercializadora ABC EIRL', '912345678', 'inactivo']);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Validar archivo cargado
     */
    public function validarCarga(Request $request)
    {
        try {
            $request->validate([
                'archivo' => 'required|file|mimes:csv,xlsx,xls|max:2048'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Archivo no válido. Debe ser CSV, XLSX o XLS (máximo 2MB)'
            ], 422);
        }

        try {
            $import = new ClientesImport();
            Excel::import($import, $request->file('archivo'));

            $validData = $import->getData();
            $allRows = $import->getAllRows();
            $errors = $import->getErrors();
            $warnings = $import->getWarnings();

            // Guardar solo datos válidos en sesión para el procesamiento
            Session::put('clientes_importacion', $validData);

            return response()->json([
                'success' => true,
                'data' => $validData,
                'allRows' => $allRows,
                'errors' => $errors,
                'warnings' => $warnings,
                'total' => count($validData),
                'totalRows' => count($allRows),
                'message' => count($validData) . ' clientes válidos de ' . count($allRows) . ' procesados'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al procesar archivo: ' . $e->getMessage()
            ], 400);
        }
    }

    /**
     * Procesar la importación confirmada
     */
    public function procesarCarga(Request $request)
    {
        $clientes = Session::get('clientes_importacion');

        if (!$clientes) {
            return response()->json([
                'success' => false,
                'message' => 'No hay datos para procesar'
            ], 400);
        }

        try {
            $creados = 0;

            foreach ($clientes as $clienteData) {
                Cliente::create([
                    'ruc' => $clienteData['ruc'],
                    'tipo_documento' => 'RUC',
                    'razon_social' => $clienteData['razon_social'],
                    'whatsapp' => $clienteData['whatsapp'],
                    'activo' => $clienteData['activo'],
                    'fecha_creacion' => now(),
                ]);

                $creados++;
            }

            // Limpiar sesión
            Session::forget('clientes_importacion');

            return response()->json([
                'success' => true,
                'creados' => $creados,
                'message' => "{$creados} clientes importados exitosamente"
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al guardar clientes: ' . $e->getMessage()
            ], 500);
        }
    }
}
