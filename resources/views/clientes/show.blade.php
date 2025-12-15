@extends('layouts.app')

@section('title', 'Detalle del Cliente')

@section('actions')
    <a href="{{ route('clientes.edit', $cliente) }}"
       class="inline-flex items-center px-3 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        Editar
    </a>
    <a href="{{ route('clientes.index') }}"
       class="inline-flex items-center px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-50 transition-colors">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Volver
    </a>
@endsection

@section('content')
<div class="max-w-4xl">

    <div class="bg-white shadow-sm rounded-lg p-6 space-y-6">
        <div class="grid grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500">RUC</h3>
                <p class="mt-1 text-lg text-gray-900">{{ $cliente->ruc }}</p>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500">Tipo de Documento</h3>
                <p class="mt-1 text-lg text-gray-900">{{ $cliente->tipo_documento }}</p>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-medium text-gray-500">Razón Social</h3>
            <p class="mt-1 text-lg text-gray-900">{{ $cliente->razon_social }}</p>
        </div>

        <div class="grid grid-cols-2 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500">WhatsApp</h3>
                <p class="mt-1 text-lg text-gray-900">{{ $cliente->whatsapp }}</p>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500">Email</h3>
                <p class="mt-1 text-lg text-gray-900">{{ $cliente->email ?? '-' }}</p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-6">
            <div>
                <h3 class="text-sm font-medium text-gray-500">Monto</h3>
                <p class="mt-1 text-lg text-gray-900">S/ {{ number_format($cliente->monto ?? 0, 2) }}</p>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500">Tipo de Servicio</h3>
                <p class="mt-1 text-lg text-gray-900">{{ ucfirst($cliente->tipo_servicio) }}</p>
            </div>

            <div>
                <h3 class="text-sm font-medium text-gray-500">Fecha Vencimiento</h3>
                <p class="mt-1 text-lg text-gray-900">{{ $cliente->fecha_vencimiento?->format('d/m/Y') ?? '-' }}</p>
            </div>
        </div>

        <div>
            <h3 class="text-sm font-medium text-gray-500">Estado</h3>
            <div class="mt-1">
                @if($cliente->activo)
                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                        Activo
                    </span>
                @else
                    <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                        Inactivo
                    </span>
                @endif
            </div>
        </div>

        <div class="border-t border-gray-200 pt-4">
            <p class="text-sm text-gray-500">
                Creado: {{ $cliente->fecha_creacion?->format('d/m/Y H:i') }} |
                Actualizado: {{ $cliente->fecha_actualizacion?->format('d/m/Y H:i') }}
            </p>
        </div>
    </div>
</div>
@endsection
