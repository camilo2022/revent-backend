<?php

namespace App\Http\Requests\Integration\CustomerSales;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/** Consulta de clientes por venta: un solo día y, opcionalmente, un centro de costo. El token solo valida el acceso. */
class SearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token' => ['required', 'string'],
            'date' => ['required', 'date_format:Y-m-d'],
            'cost_center' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'token.required' => 'Pega el token de Siigo para consultar.',
            'date.required' => 'Indica la fecha a consultar.',
            'date_format' => 'El campo :attribute no tiene un formato de fecha válido.',
            'integer' => 'El centro de costo no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'token',
            'date' => 'fecha',
        ];
    }
}
