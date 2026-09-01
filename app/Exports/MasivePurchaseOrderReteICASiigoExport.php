<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MasivePurchaseOrderReteICASiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $retes_ica;

    public function __construct($retes_ica)
    {
        $this->retes_ica = $retes_ica;
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
        return 'rete_ica';
    }

    public function generator(): Generator
    {
        foreach($this->retes_ica as $rete_ica) {
            yield [
                'id' => $rete_ica['Id'],
                'nombre' => $rete_ica['Name'] ?: 'No aplica',
                'valor' => $rete_ica['Value']
            ];
        }
    }
}
