<?php

namespace App\Http\Resources\OrganizationalStructure\Position;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PositionCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'positions' => $this->collection->map(function ($position) {
                return [
                    'id' => $position->id,
                    'name' => $position->name,
                    'description' => $position->description,
                    'created_at' => $this->formatDate($position->created_at),
                    'updated_at' => $this->formatDate($position->updated_at),
                    'deleted_at' => $this->formatDate($position->deleted_at),
                    'area' => $position->area->first(),
                    'roles' => $position->roles,
                    'permissions' => $position->permissions,
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
