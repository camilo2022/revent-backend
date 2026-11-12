<?php

namespace App\Http\Requests\Api\Integration\AccountPayable;

use App\Http\Requests\Api\Integration\IntegrationRequest;

class PaymentsWeeklyRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d', 'required_with:date_to'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'required_with:date_from', 'after_or_equal:date_from'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'required_with' => 'Es obligatorio cuando se indica el otro extremo del rango.',
            'after_or_equal' => 'Debe ser igual o posterior a la fecha inicial.',
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + [
            'date_from' => 'Fecha inicial',
            'date_to' => 'Fecha final',
        ];
    }
}
