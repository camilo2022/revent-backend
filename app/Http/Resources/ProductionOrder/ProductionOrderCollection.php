<?php

namespace App\Http\Resources\ProductionOrder;

use App\Models\ProductionOrder;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductionOrderCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'production_orders' => $this->collection->map(function ($production_order) {
                return [
                    'id' => $production_order->id,
                    'consecutive' => $production_order->consecutive,
                    'due_date' => $production_order->due_date,
                    'supplier_id' => $production_order->supplier_id,
                    'vat_percentage' => $production_order->vat_percentage,
                    'delivery_note_percentage' => $production_order->delivery_note_percentage,
                    'status' => $production_order->status,
                    'created_at' => $this->formatDate($production_order->created_at),
                    'updated_at' => $this->formatDate($production_order->updated_at),
                    'supplier' => $production_order->supplier,
                    'production_order_details' => $production_order->production_order_details
                ];
            }),
            'statuses' => ProductionOrder::statuses(),
            'meta' => [
                'pagination' => $this->paginationMeta(),
            ],
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }

    protected function paginationMeta()
    {
        return [
            'total' => $this->total(),
            'count' => $this->count(),
            'per_page' => $this->perPage(),
            'current_page' => $this->currentPage(),
            'total_pages' => $this->lastPage(),
        ];
    }
}
