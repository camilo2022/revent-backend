<?php

namespace App\Http\Requests\Api\Integration\InventoryFilter;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Fotos de una referencia, paginadas (máximo 12 por página, como en Integration). */
class ImagesRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [
            'referencia' => ['required', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:12'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'referencia.required' => 'Escribe la referencia.',
            'min' => 'El valor mínimo es :min.',
            'max' => 'El valor máximo permitido es :max.',
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'referencia' => 'Referencia',
            'page' => 'Página',
            'per_page' => 'Resultados por página',
        ];
    }
}
