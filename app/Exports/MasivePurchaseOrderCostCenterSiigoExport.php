<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MasivePurchaseOrderCostCenterSiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $cost_centers;

    public function __construct($cost_centers)
    {
        $this->cost_centers = $cost_centers;
    }

    public function headings(): array
    {
        return [
            'id',
            'codigo',
            'nombre'
        ];
    }

    public function title(): string
    {
        return 'centro_costos';
    }

    public function generator(): Generator
    {
        foreach($this->cost_centers as $cost_center) {
            yield [
                'id' => $cost_center['id'],
                'codigo' => $cost_center['code'],
                'nombre' => $cost_center['name']
            ];
        }
    }
}
