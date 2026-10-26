<?php

namespace App\Http\Requests\Integration\MasivePurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;

class MasivePurchaseOrderFormatRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'cookie' => ['required', 'string'],
            'purchase_order_type_id' => ['required'],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'El campo :attribute es obligatorio.',
            'string' => 'El campo :attribute debe ser texto.',
        ];
    }

    public function attributes(): array
    {
        return [
            'cookie' => 'cookie de Siigo',
            'purchase_order_type_id' => 'tipo de orden de compra',
        ];
    }
}
