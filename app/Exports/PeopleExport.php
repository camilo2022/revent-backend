<?php

namespace App\Exports;

use App\Models\Person;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class PeopleExport implements FromCollection, WithHeadings, WithMapping
{
    public function collection()
    {
        return Person::with('gender')->get();
    }

    public function map($person): array
    {
        return [
            $person->id,
            $person->names,
            $person->last_names,
            $person->gender->description,
            $person->created_at,
        ];
    }

    public function headings(): array
    {
        return [
            'ID',
            'Nombre',
            'Apellido',
            'Género',
            'Fecha de creación'
        ];
    }
}
