<?php

namespace App\Imports;

use App\Models\Cliente;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class ClientesImport implements ToCollection, WithHeadingRow
{
    public $data = [];
    public $allRows = []; // Todas las filas procesadas (válidas e inválidas)
    public $errors = [];
    public $warnings = [];

    /**
     * Procesa cada fila del archivo
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 porque: +1 por index 0-based, +1 por header

            $validatedRow = $this->validateRow($row, $rowNumber);

            // Siempre agregamos a allRows para mostrar en vista previa
            $this->allRows[] = $validatedRow;

            // Solo agregamos a data si es válido (sin errores)
            if ($validatedRow && $validatedRow['valido']) {
                $this->data[] = $validatedRow;
            }
        }
    }

    /**
     * Valida una fila
     */
    private function validateRow($row, $rowNumber)
    {
        $errors = [];
        $warnings = [];

        // Normalizar keys (acepta tanto español como inglés)
        $ruc = $row['ruc'] ?? $row['RUC'] ?? null;
        $razonSocial = $row['razon_social'] ?? $row['RAZON_SOCIAL'] ?? $row['razon social'] ?? null;
        $whatsapp = $row['whatsapp'] ?? $row['WHATSAPP'] ?? null;
        $estado = $row['estado'] ?? $row['ESTADO'] ?? 'activo';

        // Validar RUC (requerido)
        if (empty($ruc)) {
            $errors[] = "RUC es requerido";
            $this->errors[] = "Fila {$rowNumber}: RUC es requerido";
        } else {
            // Limpiar RUC (solo números)
            $ruc = preg_replace('/[^0-9]/', '', $ruc);

            // Validar longitud (11 dígitos en Perú)
            if (strlen($ruc) != 11) {
                $errors[] = "RUC debe tener 11 dígitos (tiene " . strlen($ruc) . ")";
                $this->errors[] = "Fila {$rowNumber}: RUC debe tener 11 dígitos (tiene " . strlen($ruc) . ")";
            }

            // Verificar si ya existe
            if (Cliente::where('ruc', $ruc)->exists()) {
                $errors[] = "RUC {$ruc} ya existe en la base de datos";
                $this->warnings[] = "Fila {$rowNumber}: RUC {$ruc} ya existe en la base de datos (se omitirá)";
            }
        }

        // Validar Razón Social (requerido)
        if (empty($razonSocial)) {
            $errors[] = "Razón Social es requerida";
            $this->errors[] = "Fila {$rowNumber}: Razón Social es requerida";
        }

        // Validar WhatsApp (opcional, pero si está presente validar formato)
        if (!empty($whatsapp)) {
            $whatsapp = preg_replace('/[^0-9]/', '', $whatsapp);

            if (strlen($whatsapp) != 9) {
                $warnings[] = "WhatsApp debe tener 9 dígitos (tiene " . strlen($whatsapp) . ")";
                $this->warnings[] = "Fila {$rowNumber}: WhatsApp debe tener 9 dígitos (tiene " . strlen($whatsapp) . ")";
            }
        }

        // Validar Estado
        $estado = strtolower(trim($estado));
        if (!in_array($estado, ['activo', 'inactivo', '1', '0', 'si', 'no'])) {
            $warnings[] = "Estado '{$estado}' no válido, se usará 'activo' por defecto";
            $this->warnings[] = "Fila {$rowNumber}: Estado '{$estado}' no válido, se usará 'activo' por defecto";
            $estado = 'activo';
        }

        // Normalizar estado a boolean
        $estadoActivo = in_array($estado, ['activo', '1', 'si']);

        return [
            'ruc' => $ruc ?: '',
            'razon_social' => trim($razonSocial ?: ''),
            'whatsapp' => $whatsapp ?: null,
            'activo' => $estadoActivo,
            'fila' => $rowNumber,
            'valido' => empty($errors),
            'errores' => $errors,
            'advertencias' => $warnings,
        ];
    }

    /**
     * Obtiene los datos validados (solo filas válidas)
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Obtiene todas las filas procesadas (válidas e inválidas)
     */
    public function getAllRows()
    {
        return $this->allRows;
    }

    /**
     * Obtiene los errores
     */
    public function getErrors()
    {
        return $this->errors;
    }

    /**
     * Obtiene los warnings
     */
    public function getWarnings()
    {
        return $this->warnings;
    }

    /**
     * Verifica si hay errores
     */
    public function hasErrors()
    {
        return !empty($this->errors);
    }
}
