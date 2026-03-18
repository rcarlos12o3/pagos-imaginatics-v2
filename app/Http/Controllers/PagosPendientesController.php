<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PagosPendientesController extends Controller
{
    /**
     * Mostrar dashboard de pagos pendientes
     */
    public function index(): View
    {
        return view('pagos-pendientes.index');
    }
}
