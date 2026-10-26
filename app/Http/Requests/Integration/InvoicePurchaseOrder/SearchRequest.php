<?php

namespace App\Http\Requests\Integration\InvoicePurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

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
            'query' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'Es obligatorio.',
            'string' => 'Debe ser una cadena de texto.',
            'max' => 'Se permite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'token' => 'Token',
            'query' => 'Orden de compra',
        ];
    }
}
