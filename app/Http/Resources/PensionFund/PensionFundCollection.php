<?php

namespace App\Http\Resources\PensionFund;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PensionFundCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'pension_funds' => $this->collection->map(function ($pension_fund) {
                return [
                    'id' => $pension_fund->id,
                    'item' => $pension_fund->item,
                    'name' => $pension_fund->name,
                    'description' => $pension_fund->description,
                    'created_at' => $this->formatDate($pension_fund->created_at),
                    'updated_at' => $this->formatDate($pension_fund->updated_at),
                    'deleted_at' => $this->formatDate($pension_fund->deleted_at)
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
