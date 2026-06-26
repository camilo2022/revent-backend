<?php

namespace App\Http\Resources\Product;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'product' => [
                'id' => $this->id,
                'trademark_id' => $this->trademark_id,
                'code' => $this->code,
                'category_id' => $this->category_id,
                'subcategory_id' => $this->subcategory_id,
                'observation' => $this->observation,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'trademark' => $this->trademark,
                'category' => $this->category,
                'subcategory' => $this->subcategory
            ]
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
