<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MasivePurchaseOrderWarehousesSiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $warehouses;

    public function __construct($warehouses)
    {
        $this->warehouses = $warehouses;
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
        return 'bodegas';
    }

    public function generator(): Generator
    {
        foreach($this->warehouses as $warehouse) {
            yield [
                'id' => $warehouse['id'],
                'nombre' => $warehouse['name']
            ];
        }
    }
}
