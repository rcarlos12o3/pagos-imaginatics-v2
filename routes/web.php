<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClienteController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EnvioController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PagosPendientesController;
use App\Http\Controllers\RucController;
use App\Http\Controllers\ServicioContratadoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('auth.login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');

Route::middleware(['auth'])->group(function () {
    Route::get('/primera-vez', [AuthController::class, 'showPrimeraVez'])->name('auth.primera-vez');
    Route::post('/primera-vez', [AuthController::class, 'primeraVez'])->name('auth.primera-vez.submit');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::resource('clientes', ClienteController::class);

    // Historial de Envíos WhatsApp
    Route::get('/historial', [EnvioController::class, 'index'])->name('historial.index');
    Route::put('/historial/{envio}', [EnvioController::class, 'update'])->name('historial.update');
    Route::delete('/historial/{envio}', [EnvioController::class, 'destroy'])->name('historial.destroy');

    // API Historial
    Route::get('/api/historial/stats', [EnvioController::class, 'stats'])->name('historial.stats');
    Route::get('/api/historial/cliente', [EnvioController::class, 'historialCliente'])->name('historial.cliente');

    // Módulo de Envíos (Análisis Inteligente)
    Route::get('/envios', [EnvioController::class, 'envios'])->name('envios.index');
    Route::get('/api/envios/analizar-pendientes', [EnvioController::class, 'analizarEnviosPendientes'])->name('envios.analizar');
    Route::post('/api/envios/enviar-ordenes', [EnvioController::class, 'enviarOrdenes'])->name('envios.enviar');

    // Consulta RUC
    Route::get('/api/ruc/consultar', [RucController::class, 'consultar'])->name('ruc.consultar');
    Route::delete('/api/ruc/limpiar-cache', [RucController::class, 'limpiarCache'])->name('ruc.limpiar-cache');
    Route::get('/api/ruc/estadisticas', [RucController::class, 'estadisticas'])->name('ruc.estadisticas');

    // Servicios Contratados
    Route::resource('servicios', ServicioContratadoController::class);
    Route::post('/servicios/{servicio}/suspender', [ServicioContratadoController::class, 'suspender'])->name('servicios.suspender');
    Route::post('/servicios/{servicio}/reactivar', [ServicioContratadoController::class, 'reactivar'])->name('servicios.reactivar');

    // API Servicios
    Route::get('/api/servicios/cliente', [ServicioContratadoController::class, 'serviciosCliente'])->name('servicios.cliente');

    // Pagos Pendientes
    Route::get('/pagos-pendientes', [PagosPendientesController::class, 'index'])->name('pagos-pendientes.index');

    // Registro de Pagos
    Route::get('/pagos/create', [PagoController::class, 'create'])->name('pagos.create');
    Route::post('/pagos', [PagoController::class, 'store'])->name('pagos.store');
});
