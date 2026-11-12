<?php

namespace App\Http\Requests\Api\Integration\TransferApproval;

use App\Http\Requests\Api\Integration\IntegrationRequest;
use Illuminate\Validation\Validator;

class ConfirmRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', $this->siigoToken()],
            'query' => ['required', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.code' => ['required', 'string', 'max:100', 'distinct'],
            'items.*.quantity' => ['required', 'integer', 'min:0'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator) {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (collect($this->input('items'))->sum(fn ($item) => (int) $item['quantity']) <= 0) {
                $validator->errors()->add('items', 'Debes aprobar al menos una unidad.');
            }
        }];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'min.array' => 'Debes enviar al menos un producto.',
            'distinct' => 'El producto está repetido.',
            'items.*.quantity.min' => 'La cantidad no puede ser negativa.',
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + [
            'query' => 'nota de traslado',
            'items' => 'productos',
            'items.*.code' => 'código',
            'items.*.quantity' => 'cantidad',
        ];
    }
}
