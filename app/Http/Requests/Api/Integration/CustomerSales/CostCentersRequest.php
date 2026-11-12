<?php

namespace App\Http\Requests\Api\Integration\CustomerSales;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Centros de costo para el filtro: no recibe datos. */
class CostCentersRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [];
    }
}
