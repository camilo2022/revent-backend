<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MasivePurchaseOrderReteIVASiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $retes_iva;

    public function __construct($retes_iva)
    {
        $this->retes_iva = $retes_iva;
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
        return 'rete_iva';
    }

    public function generator(): Generator
    {
        foreach($this->retes_iva as $rete_iva) {
            yield [
                'id' => $rete_iva['Id'],
                'nombre' => $rete_iva['Name'] ?: 'No aplica',
                'valor' => $rete_iva['Value']
            ];
        }
    }
}
