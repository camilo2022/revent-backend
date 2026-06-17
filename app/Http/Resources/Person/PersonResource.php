<?php

namespace App\Http\Resources\Person;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'person' => [
                'id' => $this->id,
                'document' => $this->document,
                'names' => $this->names,
                'last_names' => $this->last_names,
                'birth_date' => $this->birth_date,
                'address' => $this->address,
                'phone' => $this->phone,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'gender' => $this->gender,
                'photo' => $this->photo,
                'blood_type' => $this->blood_type,
                'employee' => $this->employee
            ]
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
