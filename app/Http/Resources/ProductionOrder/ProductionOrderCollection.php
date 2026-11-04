<?php

namespace App\Http\Resources\ProductionOrder;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductionOrderCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'production_orders' => $this->collection->map(fn ($order) => ProductionOrderResource::row($order)),
            'meta' => [
                'pagination' => [
                    'total' => $this->total(),
                    'count' => $this->count(),
                    'per_page' => $this->perPage(),
                    'current_page' => $this->currentPage(),
                    'total_pages' => $this->lastPage(),
                ],
            ],
        ];
    }
}
