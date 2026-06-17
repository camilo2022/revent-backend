<?php

namespace App\Http\Resources\RiskManager;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class RiskManagerCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'risk_managers' => $this->collection->map(function ($risk_manager) {
                return [
                    'id' => $risk_manager->id,
                    'item_id' => $risk_manager->item_id,
                    'name' => $risk_manager->name,
                    'description' => $risk_manager->description,
                    'created_at' => $this->formatDate($risk_manager->created_at),
                    'updated_at' => $this->formatDate($risk_manager->updated_at),
                    'deleted_at' => $this->formatDate($risk_manager->deleted_at),
                    'item' => $risk_manager->item,
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
