<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MasivePurchaseOrderImpCargoSiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $imps_cargo;

    public function __construct($imps_cargo)
    {
        $this->imps_cargo = $imps_cargo;
    }

    public function headings(): array
    {
        return [
            'id',
            'nombre',
            'valor'
        ];
    }

    public function title(): string
    {
        return 'imp_cargo';
    }

    public function generator(): Generator
    {
        foreach($this->imps_cargo as $imp_cargo) {
            yield [
                'id' => $imp_cargo['Id'],
                'nombre' => $imp_cargo['Name'],
                'valor' => $imp_cargo['Value']
            ];
        }
    }
}
