@extends('layouts.app')

@section('title', 'Cambiar Contraseña')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                </svg>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Cambio de Contraseña Requerido</h3>
                <p class="mt-2 text-sm text-yellow-700">
                    Por seguridad, debes cambiar tu contraseña en el primer acceso al sistema.
                </p>
            </div>
        </div>
    </div>

    <div class="bg-white shadow-sm rounded-lg p-6">
        <h1 class="text-2xl font-bold text-gray-900 mb-6">Cambiar Contraseña</h1>

        <form method="POST" action="{{ route('auth.primera-vez.submit') }}" class="space-y-6">
            @csrf

            <div>
                <label for="nueva_password" class="block text-sm font-medium text-gray-700 mb-2">
                    Nueva Contraseña *
                </label>
                <input type="password"
                       id="nueva_password"
                       name="nueva_password"
                       required
                       class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('nueva_password') border-red-500 @enderror">
                <p class="mt-1 text-sm text-gray-500">Mínimo 6 caracteres</p>
                @error('nueva_password')
                    <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="nueva_password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                    Confirmar Nueva Contraseña *
                </label>
                <input type="password"
                       id="nueva_password_confirmation"
                       name="nueva_password_confirmation"
                       required
                       class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div class="pt-4">
                <button type="submit"
                        class="w-full bg-blue-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Cambiar Contraseña y Continuar
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
