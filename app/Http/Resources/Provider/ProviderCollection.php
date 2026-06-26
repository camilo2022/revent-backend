<?php

namespace App\Http\Resources\Provider;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProviderCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'providers' => $this->collection->map(function ($provider) {
                return [
                    'id' => $provider->id,
                    'item_id' => $provider->item_id,
                    'name' => $provider->name,
                    'description' => $provider->description,
                    'created_at' => $this->formatDate($provider->created_at),
                    'updated_at' => $this->formatDate($provider->updated_at),
                    'deleted_at' => $this->formatDate($provider->deleted_at),
                    'item' => $provider->item,
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
