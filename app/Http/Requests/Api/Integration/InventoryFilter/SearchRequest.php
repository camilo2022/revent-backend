<?php

namespace App\Http\Requests\Api\Integration\InventoryFilter;

use App\Http\Requests\Api\Integration\IntegrationRequest;
use App\Rules\Integration\AccessibleWarehouse;

/** Inventario de una bodega: el usuario debe tener la bodega (o su tránsito) entre sus tiendas. */
class SearchRequest extends IntegrationRequest
{
    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', new AccessibleWarehouse()],
        ];
    }

    public function messages(): array
    {
        return [
            ...parent::messages(),
            'warehouse_id.required' => 'Selecciona una bodega.',
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'warehouse_id' => 'Bodega',
        ];
    }
}
