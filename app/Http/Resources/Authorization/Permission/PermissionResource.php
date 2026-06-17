<?php

namespace App\Http\Resources\Authorization\Permission;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PermissionResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'permission' => [
                'id' => $this->id,
                'item_id' => $this->item_id,
                'name' => $this->name,
                'title' => $this->title,
                'description' => $this->description,
                'settings' => $this->settings,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'item' => $this->item,
                'roles' => $this->roles,
            ]
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
