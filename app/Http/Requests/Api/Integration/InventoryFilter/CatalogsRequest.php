<?php

namespace App\Http\Requests\Api\Integration\InventoryFilter;

use App\Http\Requests\Api\Integration\IntegrationRequest;

/** Bodegas y grupos de color del filtro de inventario: no recibe datos. */
class CatalogsRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [];
    }
}
