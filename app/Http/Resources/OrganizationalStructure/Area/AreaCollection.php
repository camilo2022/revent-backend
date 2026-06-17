<?php

namespace App\Http\Resources\OrganizationalStructure\Area;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class AreaCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'areas' => $this->collection->map(function ($area) {
                return [
                    'id' => $area->id,
                    'item_id' => $area->item_id,
                    'name' => $area->name,
                    'description' => $area->description,
                    'created_at' => $this->formatDate($area->created_at),
                    'updated_at' => $this->formatDate($area->updated_at),
                    'deleted_at' => $this->formatDate($area->deleted_at),
                    'item' => $area->item,
                    'positions' => $area->positions,
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
