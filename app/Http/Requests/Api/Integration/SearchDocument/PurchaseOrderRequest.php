<?php

namespace App\Http\Requests\Api\Integration\SearchDocument;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Búsqueda de una orden de compra (19): requiere la cookie completa del monolito de Siigo. */
class PurchaseOrderRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [
            'query' => ['required', 'string', 'max:100'],
            'cookie' => ['required', 'string', 'max:10000', $this->siigoCookie()],
        ];
    }

    public function attributes(): array
    {
        return parent::attributes() + ['query' => 'Documento'];
    }
}
