<?php

namespace App\Http\Requests\Api\Integration\MasivePurchaseOrders;

use App\Http\Requests\Api\Integration\IntegrationRequest;
use App\Services\Integration\MasivePurchaseOrderFormatService;
use Illuminate\Validation\Rule;

/**
 * Formato de órdenes de compra masivas: pide la cookie de Siigo (se valida y de ella se toma el token del monolito)
 * y el tipo de orden de compra.
 */
class FormatRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [
            'cookie' => ['required', 'string', $this->siigoCookie()],
            'purchase_order_type_id' => ['required', Rule::in(array_keys(MasivePurchaseOrderFormatService::TYPES))],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'cookie.required' => 'Carga tu cookie de Siigo en Autorización Siigo.',
            'purchase_order_type_id.required' => 'Selecciona el tipo de orden de compra.',
            'purchase_order_type_id.in' => 'El tipo de orden de compra no es válido.',
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'purchase_order_type_id' => 'Tipo de orden de compra',
        ];
    }
}
