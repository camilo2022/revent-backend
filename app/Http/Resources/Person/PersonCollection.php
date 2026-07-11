<?php

namespace App\Http\Resources\Person;

use Carbon\Carbon;
use Illuminate\Http\Resources\Json\ResourceCollection;

class PersonCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'people' => $this->collection->map(function ($person) {
                return [
                    'id' => $person->id,
                    'names' => $person->names,
                    'last_names' => $person->last_names,
                    'document_type_id' => $person->document_type_id,
                    'document' => $person->document,
                    'gender_id' => $person->gender_id,
                    'birth_date' => $person->birth_date,
                    'blood_type_id' => $person->blood_type_id,
                    'location_id' => $person->location_id,
                    'location_type' => $person->location_type,
                    'address' => $person->address,
                    'neighborhood' => $person->neighborhood,
                    'phone_country_id' => $person->phone_country_id,
                    'phone' => $person->phone,
                    'email' => $person->email,
                    'created_at' => $this->formatDate($person->created_at),
                    'updated_at' => $this->formatDate($person->updated_at),
                    'deleted_at' => $this->formatDate($person->deleted_at),
                    'document_type' => $person->document_type,
                    'gender' => $person->gender,
                    'blood_type' => $person->blood_type,
                    'location' => $person->location,
                    'phone_country' => $person->phone_country,
                    'employee' => $person->employee,
                    'photo' => $person->photo
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
