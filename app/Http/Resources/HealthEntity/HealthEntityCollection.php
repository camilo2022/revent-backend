<?php

namespace App\Http\Resources\HealthEntity;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class HealthEntityCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'health_entities' => $this->collection->map(function ($health_entity) {
                return [
                    'id' => $health_entity->id,
                    'item_id' => $health_entity->item_id,
                    'name' => $health_entity->name,
                    'description' => $health_entity->description,
                    'created_at' => $this->formatDate($health_entity->created_at),
                    'updated_at' => $this->formatDate($health_entity->updated_at),
                    'deleted_at' => $this->formatDate($health_entity->deleted_at),
                    'item' => $health_entity->item,
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
