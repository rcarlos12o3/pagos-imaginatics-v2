<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'celular' => 'required|string',
            'password' => 'required|string',
        ]);

        $celular = preg_replace('/\D/', '', $request->celular);

        $usuario = Usuario::where('celular', $celular)
            ->where('activo', true)
            ->first();

        if (!$usuario) {
            return back()->withErrors([
                'celular' => 'Número de celular no registrado',
            ])->withInput($request->only('celular'));
        }

        if ($usuario->estaBloqueado()) {
            return back()->withErrors([
                'celular' => 'Usuario bloqueado temporalmente. Intente más tarde.',
            ])->withInput($request->only('celular'));
        }

        if (!Hash::check($request->password, $usuario->password_hash)) {
            $usuario->incrementarIntentosFallidos();

            return back()->withErrors([
                'password' => 'Contraseña incorrecta',
            ])->withInput($request->only('celular'));
        }

        $usuario->limpiarIntentosFallidos();

        Auth::login($usuario);

        $request->session()->regenerate();

        if ($usuario->primera_vez) {
            return redirect()->route('auth.primera-vez');
        }

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }

    public function showPrimeraVez(): View
    {
        if (!Auth::user()->primera_vez) {
            return redirect()->route('dashboard');
        }

        return view('auth.primera-vez');
    }

    public function primeraVez(Request $request): RedirectResponse
    {
        $request->validate([
            'nueva_password' => 'required|string|min:6|confirmed',
        ]);

        $usuario = Auth::user();

        $usuario->password_hash = Hash::make($request->nueva_password);
        $usuario->primera_vez = false;
        $usuario->save();

        return redirect()->route('dashboard')
            ->with('success', 'Contraseña actualizada exitosamente');
    }
}
