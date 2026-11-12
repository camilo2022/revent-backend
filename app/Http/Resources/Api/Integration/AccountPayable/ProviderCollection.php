<?php

namespace App\Http\Resources\Api\Integration\AccountPayable;

use Illuminate\Http\Resources\Json\JsonResource;

/** Proveedores con saldo por pagar (la forma que arma SiigoAccountPayableService::providers). */
class ProviderCollection extends JsonResource
{
    public function toArray($request)
    {
        return ['providers' => array_values($this->resource)];
    }
}
