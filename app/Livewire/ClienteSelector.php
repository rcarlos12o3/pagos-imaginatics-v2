<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Cliente;

class ClienteSelector extends Component
{
    public $busqueda = '';
    public $clienteSeleccionado = null;
    public $clientes = [];
    public $mostrarDropdown = false;
    public $clienteId = null;
    public $required = true;

    public function mount($clienteId = null, $required = true)
    {
        $this->required = $required;
        $this->clienteId = $clienteId;

        if ($clienteId) {
            $this->clienteSeleccionado = Cliente::find($clienteId);
            if ($this->clienteSeleccionado) {
                $this->busqueda = $this->clienteSeleccionado->razon_social;
            }
        }
    }

    public function updatedBusqueda()
    {
        if (strlen($this->busqueda) >= 2) {
            $this->clientes = Cliente::where('activo', true)
                ->where(function ($query) {
                    $query->where('razon_social', 'LIKE', "%{$this->busqueda}%")
                          ->orWhere('ruc', 'LIKE', "%{$this->busqueda}%");
                })
                ->orderBy('razon_social')
                ->limit(10)
                ->get();
            $this->mostrarDropdown = true;
        } else {
            $this->clientes = [];
            $this->mostrarDropdown = false;
        }
    }

    public function seleccionarCliente($clienteId)
    {
        $this->clienteSeleccionado = Cliente::find($clienteId);
        $this->clienteId = $clienteId;
        $this->busqueda = $this->clienteSeleccionado->razon_social;
        $this->mostrarDropdown = false;
        $this->clientes = [];
    }

    public function limpiarSeleccion()
    {
        $this->clienteSeleccionado = null;
        $this->clienteId = null;
        $this->busqueda = '';
        $this->clientes = [];
        $this->mostrarDropdown = false;
    }

    public function render()
    {
        return view('livewire.cliente-selector');
    }
}
