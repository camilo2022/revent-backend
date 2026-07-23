<?php

namespace App\Http\Resources\Product;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'products' => $this->collection->map(function ($product) {
                return [
                    'id' => $product->id,
                    'trademark_id' => $product->trademark_id,
                    'code' => $product->code,
                    'category_id' => $product->category_id,
                    'subcategory_id' => $product->subcategory_id,
                    'observation' => $product->observation,
                    'created_at' => $this->formatDate($product->created_at),
                    'updated_at' => $this->formatDate($product->updated_at),
                    'deleted_at' => $this->formatDate($product->deleted_at),
                    'trademark' => $product->trademark,
                    'category' => $product->category,
                    'subcategory' => $product->subcategory
                ];
            }),
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
