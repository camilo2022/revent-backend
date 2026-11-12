<?php

namespace App\Http\Requests\Api\Integration\PurchaseOrderReception;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Busca una orden de compra para recibir. Pide el token del usuario de Siigo (se valida contra Siigo). */
class SearchRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [
            'token' => ['required', 'string', $this->siigoToken()],
            'query' => ['required', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'token.required' => 'Carga tu token de Siigo en Autorización Siigo.',
            'query.required' => 'Escribe el número de la orden de compra.',
            'max' => 'Se permite máximo :max caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'query' => 'Orden de compra',
        ];
    }
}
