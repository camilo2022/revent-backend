<?php

namespace App\Http\Resources\ProductionOrder;

use App\Models\ProductionOrder;
use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductionOrderResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'production_order' => [
                'id' => $this->id,
                'consecutive' => $this->consecutive,
                'due_date' => $this->due_date,
                'supplier_id' => $this->supplier_id,
                'vat_percentage' => $this->vat_percentage,
                'delivery_note_percentage' => $this->delivery_note_percentage,
                'status' => $this->status,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'supplier' => $this->supplier,
                'production_order_details' => $this->production_order_details
            ],
            'statuses' => ProductionOrder::statuses()
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
