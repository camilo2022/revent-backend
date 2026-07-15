<?php

namespace App\Http\Resources\Store;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class StoreCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'stores' => $this->collection->map(function ($store) {
                return [
                    'id' => $store->id,
                    'code' => $store->code,
                    'name' => $store->name,
                    'location_id' => $store->location_id,
                    'location_type' => $store->location_type,
                    'address' => $store->address,
                    'neighborhood' => $store->neighborhood,
                    'created_at' => $this->formatDate($store->created_at),
                    'updated_at' => $this->formatDate($store->updated_at),
                    'deleted_at' => $this->formatDate($store->deleted_at),
                    'location' => $store->location,
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
