<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Imaginatics</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-8 py-6">
                <h1 class="text-2xl font-bold text-white text-center">Imaginatics Perú</h1>
                <p class="text-blue-100 text-center mt-1">Sistema de Gestión</p>
            </div>

            <div class="p-8">
                <form method="POST" action="{{ route('auth.login') }}" class="space-y-6">
                    @csrf

                    <div>
                        <label for="celular" class="block text-sm font-medium text-gray-700 mb-2">
                            Número de Celular
                        </label>
                        <input type="text"
                               id="celular"
                               name="celular"
                               value="{{ old('celular') }}"
                               placeholder="999999999"
                               required
                               autofocus
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('celular') border-red-500 @enderror">
                        @error('celular')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                            Contraseña
                        </label>
                        <input type="password"
                               id="password"
                               name="password"
                               placeholder="••••••••"
                               required
                               class="w-full px-4 py-3 rounded-lg border border-gray-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror">
                        @error('password')
                            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold py-3 px-4 rounded-lg hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all">
                        Iniciar Sesión
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-sm text-gray-600">
                        Sistema de gestión de clientes y pagos
                    </p>
                </div>
            </div>
        </div>

        <div class="mt-6 text-center text-sm text-gray-600">
            <p>&copy; {{ date('Y') }} Imaginatics Perú SAC. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
