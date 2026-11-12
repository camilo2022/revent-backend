<?php

namespace App\Http\Requests\Api\Integration\InventoryFilter;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Inventario de una referencia en todas las bodegas. */
class AllWarehousesRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [
            'referencia' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'referencia.required' => 'Escribe la referencia.',
            'max' => 'Se permite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'referencia' => 'Referencia',
        ];
    }
}
