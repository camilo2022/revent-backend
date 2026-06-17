<?php

namespace App\Http\Resources\BloodType;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class BloodTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'blood_types' => $this->collection->map(function ($blood_type) {
                return [
                    'id' => $blood_type->id,
                    'item_id' => $blood_type->item_id,
                    'name' => $blood_type->name,
                    'description' => $blood_type->description,
                    'created_at' => $this->formatDate($blood_type->created_at),
                    'updated_at' => $this->formatDate($blood_type->updated_at),
                    'deleted_at' => $this->formatDate($blood_type->deleted_at),
                    'item' => $blood_type->item
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
