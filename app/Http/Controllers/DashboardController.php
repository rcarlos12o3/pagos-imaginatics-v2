<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalClientes = Cliente::activos()->count();

        $clientesPorVencer = Cliente::activos()
            ->whereNotNull('fecha_vencimiento')
            ->whereBetween('fecha_vencimiento', [now(), now()->addDays(30)])
            ->count();

        $totalInactivos = Cliente::where('activo', false)->count();

        $enviosHoy = DB::table('envios_whatsapp')
            ->whereDate('fecha_envio', today())
            ->count();

        $enviosMes = DB::table('envios_whatsapp')
            ->whereYear('fecha_envio', now()->year)
            ->whereMonth('fecha_envio', now()->month)
            ->count();

        $clientesPorServicio = Cliente::activos()
            ->select('tipo_servicio', DB::raw('count(*) as total'))
            ->groupBy('tipo_servicio')
            ->get();

        $ultimosClientes = Cliente::activos()
            ->orderBy('fecha_creacion', 'desc')
            ->limit(5)
            ->get();

        $proximosVencimientos = Cliente::activos()
            ->whereNotNull('fecha_vencimiento')
            ->whereBetween('fecha_vencimiento', [now(), now()->addDays(30)])
            ->orderBy('fecha_vencimiento', 'asc')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'totalClientes',
            'clientesPorVencer',
            'totalInactivos',
            'enviosHoy',
            'enviosMes',
            'clientesPorServicio',
            'ultimosClientes',
            'proximosVencimientos'
        ));
    }
}
