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
                    'document' => $person->document,
                    'names' => $person->names,
                    'last_names' => $person->last_names,
                    'birth_date' => $person->birth_date,
                    'address' => $person->address,
                    'phone' => $person->phone,
                    'created_at' => $this->formatDate($person->created_at),
                    'updated_at' => $this->formatDate($person->updated_at),
                    'deleted_at' => $this->formatDate($person->deleted_at),
                    'gender' => $person->gender,
                    'photo' => $person->photo,
                    'blood_type' => $person->blood_type,
                    'employee' => $person->employee
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
