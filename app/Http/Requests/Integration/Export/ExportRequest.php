<?php

namespace App\Http\Requests\Integration\Export;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de los exportes de Siigo por correo: destinatarios del dominio corporativo
 * y rango de fechas (mes o created_start/created_end) con tamaño de página.
 */
abstract class ExportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [...$this->emailRules(), ...$this->dateRangeRules()];
    }

    /** Reglas de los destinatarios (solo correos del dominio configurado). */
    protected function emailRules(): array
    {
        $domain = preg_quote((string) config('siigo.mail.domain'), '/');

        return [
            'emails' => ['nullable', 'array', 'min:1', 'max:50'],
            'emails.*' => ['required', 'email', 'max:254', 'regex:/^[a-zA-Z0-9._%+-]+@' . $domain . '$/'],
        ];
    }

    /** Reglas de rango de fechas (mes o created_start/created_end) y tamaño de página. */
    protected function dateRangeRules(): array
    {
        return [
            'month' => ['nullable', 'date_format:Y-m'],
            'created_start' => ['nullable', 'date', 'required_with:created_end'],
            'created_end' => ['nullable', 'date', 'required_with:created_start', 'after_or_equal:created_start'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'required_with' => 'Es obligatorio cuando se indica el otro extremo del rango.',
            'array' => 'Debe ser un arreglo.',
            'email' => 'Debe ser un correo electrónico válido.',
            'date' => 'Debe ser una fecha válida.',
            'date_format' => 'Debe tener el formato :format.',
            'after_or_equal' => 'Debe ser igual o posterior a la fecha inicial.',
            'integer' => 'Debe ser un número entero.',
            'in' => 'El valor seleccionado no es válido.',
            'string' => 'Debe ser una cadena de texto.',
            'min' => 'El valor mínimo es :min.',
            'max' => 'El valor máximo permitido es :max.',
            'emails.*.regex' => 'El correo :input debe pertenecer al dominio @' . config('siigo.mail.domain'),
        ];
    }

    public function attributes(): array
    {
        return [
            'emails' => 'Correos',
            'emails.*' => 'Correo',
            'month' => 'Mes',
            'created_start' => 'Fecha inicial',
            'created_end' => 'Fecha final',
            'page_size' => 'Tamaño de página',
            'inventory_type' => 'Tipo de inventario',
            'type' => 'Tipo',
        ];
    }
}
