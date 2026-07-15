<?php

namespace App\Http\Resources\Store;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class StoreResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'store' => [
                'id' => $this->id,
                'code' => $this->code,
                'name' => $this->name,
                'location_id' => $this->location_id,
                'location_type' => $this->location_type,
                'address' => $this->address,
                'neighborhood' => $this->neighborhood,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'location' => $this->location
            ]
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
