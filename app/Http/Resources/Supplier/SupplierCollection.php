<?php

namespace App\Http\Resources\Supplier;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SupplierCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'suppliers' => $this->collection->map(function ($supplier) {
                return [
                    'id' => $supplier->id,
                    'item_id' => $supplier->item_id,
                    'name' => $supplier->name,
                    'description' => $supplier->description,
                    'created_at' => $this->formatDate($supplier->created_at),
                    'updated_at' => $this->formatDate($supplier->updated_at),
                    'deleted_at' => $this->formatDate($supplier->deleted_at),
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
