<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MasivePurchaseOrderTypeDetailSiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $type_details;

    public function __construct($type_details)
    {
        $this->type_details = $type_details;
    }

    public function headings(): array
    {
        return [
            'id',
            'nombre'
        ];
    }

    public function title(): string
    {
        return 'tipo_detalles';
    }

    public function generator(): Generator
    {
        foreach($this->type_details as $id => $type_detail) {
            yield [
                'id' => (string) $id,
                'nombre' => $type_detail
            ];
        }
    }
}
