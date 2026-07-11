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
                'names' => $this->names,
                'last_names' => $this->last_names,
                'document_type_id' => $this->document_type_id,
                'document' => $this->document,
                'gender_id' => $this->gender_id,
                'birth_date' => $this->birth_date,
                'blood_type_id' => $this->blood_type_id,
                'location_id' => $this->location_id,
                'location_type' => $this->location_type,
                'address' => $this->address,
                'neighborhood' => $this->neighborhood,
                'phone_country_id' => $this->phone_country_id,
                'phone' => $this->phone,
                'email' => $this->email,
                'created_at' => $this->formatDate($this->created_at),
                'updated_at' => $this->formatDate($this->updated_at),
                'deleted_at' => $this->formatDate($this->deleted_at),
                'document_type' => $this->document_type,
                'gender' => $this->gender,
                'blood_type' => $this->blood_type,
                'location' => $this->location,
                'phone_country' => $this->phone_country,
                'employee' => $this->employee,
                'photo' => $this->photo
            ]
        ];
    }

    protected function formatDate($date)
    {
        return $date ? Carbon::parse($date)->format('Y-m-d H:i:s') : null;
    }
}
