<?php

namespace App\Http\Resources\Identification\PersonType;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PersonTypeCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'person_types' => $this->collection->map(function ($person_type) {
                return [
                    'id' => $person_type->id,
                    'item_id' => $person_type->item_id,
                    'name' => $person_type->name,
                    'description' => $person_type->description,
                    'settings' => $person_type->settings,
                    'created_at' => $this->formatDate($person_type->created_at),
                    'updated_at' => $this->formatDate($person_type->updated_at),
                    'deleted_at' => $this->formatDate($person_type->deleted_at),
                    'item' => $person_type->item,
                    'document_types' => $person_type->document_types,
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
