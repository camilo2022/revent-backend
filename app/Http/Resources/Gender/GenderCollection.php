<?php

namespace App\Http\Resources\Gender;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class GenderCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'genders' => $this->collection->map(function ($gender) {
                return [
                    'id' => $gender->id,
                    'item_id' => $gender->item_id,
                    'name' => $gender->name,
                    'description' => $gender->description,
                    'created_at' => $this->formatDate($gender->created_at),
                    'updated_at' => $this->formatDate($gender->updated_at),
                    'deleted_at' => $this->formatDate($gender->deleted_at),
                    'item' => $gender->item,
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
