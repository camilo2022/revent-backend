<?php

namespace App\Http\Resources\CompensationFund;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class CompensationFundResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'compensation_fund' => [
                'id' => $this->id,
                'item_id' => $this->item_id,
                'name' => $this->name,
                'description' => $this->description,
                'settings' => $this->settings,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'item' => $this->item,
            ]
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
