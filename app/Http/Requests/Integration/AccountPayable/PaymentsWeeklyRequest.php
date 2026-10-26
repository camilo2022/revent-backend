<?php

namespace App\Http\Requests\Integration\AccountPayable;

use Illuminate\Foundation\Http\FormRequest;

class PaymentsWeeklyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_from' => ['nullable', 'date_format:Y-m-d', 'required_with:date_to'],
            'date_to' => ['nullable', 'date_format:Y-m-d', 'required_with:date_from', 'after_or_equal:date_from'],
        ];
    }

    public function messages(): array
    {
        return [
            'required_with' => 'Es obligatorio cuando se indica el otro extremo del rango.',
            'date_format' => 'Debe tener el formato AAAA-MM-DD.',
            'after_or_equal' => 'Debe ser igual o posterior a la fecha inicial.',
        ];
    }

    public function attributes(): array
    {
        return [
            'date_from' => 'Fecha inicial',
            'date_to' => 'Fecha final',
        ];
    }
}
