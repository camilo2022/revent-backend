<?php

namespace App\Http\Resources\Navegation\Module;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Carbon;

class ModuleCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'modules' => $this->collection->map(function ($module) {
                return [
                    'id' => $module->id,
                    'name' => $module->name,
                    'icon' => $module->icon,
                    'created_at' => $this->formatDate($module->created_at),
                    'updated_at' => $this->formatDate($module->updated_at),
                    'deleted_at' => $this->formatDate($module->deleted_at),
                    'submodules' => $module->submodules,
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
