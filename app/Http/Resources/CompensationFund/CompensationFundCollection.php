<?php

namespace App\Http\Resources\CompensationFund;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CompensationFundCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'compensation_funds' => $this->collection->map(function ($compensation_fund) {
                return [
                    'id' => $compensation_fund->id,
                    'item_id' => $compensation_fund->item_id,
                    'name' => $compensation_fund->name,
                    'description' => $compensation_fund->description,
                    'created_at' => $this->formatDate($compensation_fund->created_at),
                    'updated_at' => $this->formatDate($compensation_fund->updated_at),
                    'deleted_at' => $this->formatDate($compensation_fund->deleted_at),
                    'item' => $compensation_fund->item,
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
