<?php

namespace App\Http\Resources\Size;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class SizeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'sizes' => $this->collection->map(function ($size) {
                return [
                    'id' => $size->id,
                    'item_id' => $size->item_id,
                    'name' => $size->name,
                    'description' => $size->description,
                    'settings' => $size->settings,
                    'created_at' => $this->formatDate($size->created_at),
                    'updated_at' => $this->formatDate($size->updated_at),
                    'deleted_at' => $this->formatDate($size->deleted_at),
                    'item' => $size->item,
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
