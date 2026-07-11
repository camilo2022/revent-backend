<?php

namespace App\Http\Resources\User;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class UserCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'users' => $this->collection->map(function ($user) {
                return [
                    'id' => $user->id,
                    'employee_id' => $user->employee_id,
                    'username' => $user->username,
                    'created_at' => $this->formatDate($user->created_at),
                    'updated_at' => $this->formatDate($user->updated_at),
                    'deleted_at' => $this->formatDate($user->deleted_at),
                    'employee' => $user->employee,
                    'roles' => $user->roles,
                    'permissions' => $user->permissions,
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
