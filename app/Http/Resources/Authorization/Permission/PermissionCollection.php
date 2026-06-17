<?php

namespace App\Http\Resources\Authorization\Permission;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Carbon;

class PermissionCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'permissions' => $this->collection->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'title' => $permission->title,
                    'description' => $permission->description,
                    'created_at' => $this->formatDate($permission->created_at),
                    'updated_at' => $this->formatDate($permission->updated_at),
                    'role' => $permission->roles->first(),
                ];
            }),
            'meta' => [
                'pagination' => $this->paginationMeta(),
            ],
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : "null";
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
