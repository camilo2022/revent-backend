<?php

namespace App\Http\Resources\Color;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ColorCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'colors' => $this->collection->map(function ($color) {
                return [
                    'id' => $color->id,
                    'item_id' => $color->item_id,
                    'name' => $color->name,
                    'description' => $color->description,
                    'settings' => $color->settings,
                    'created_at' => $this->formatDate($color->created_at),
                    'updated_at' => $this->formatDate($color->updated_at),
                    'deleted_at' => $this->formatDate($color->deleted_at),
                    'item' => $color->item,
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
