<?php

namespace App\Http\Requests\Api\Integration\MasivePurchaseOrders;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Tipos de orden de compra disponibles para la carga masiva: no recibe datos. */
class TypesRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [];
    }
}
