<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

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
            'monto' => 'nullable|numeric|min:0',
            'fecha_vencimiento' => 'nullable|date',
            'whatsapp' => 'required|string|max:15',
            'email' => 'nullable|email|max:255',
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_cargo' => 'nullable|string|max:100',
            'tipo_servicio' => 'nullable|in:mensual,trimestral,semestral,anual',
            'direccion' => 'nullable|string',
            'estado_sunat' => 'nullable|string|max:50',
            'activo' => 'boolean',
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
            'monto' => 'nullable|numeric|min:0',
            'fecha_vencimiento' => 'nullable|date',
            'whatsapp' => 'required|string|max:15',
            'email' => 'nullable|email|max:255',
            'contacto_nombre' => 'nullable|string|max:255',
            'contacto_cargo' => 'nullable|string|max:100',
            'tipo_servicio' => 'nullable|in:mensual,trimestral,semestral,anual',
            'direccion' => 'nullable|string',
            'estado_sunat' => 'nullable|string|max:50',
            'activo' => 'boolean',
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
}
