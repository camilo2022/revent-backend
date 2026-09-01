<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class MasivePurchaseOrderImpRetencionSiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $imps_retencion;

    public function __construct($imps_retencion)
    {
        $this->imps_retencion = $imps_retencion;
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
        return 'imp_retencion';
    }

    public function generator(): Generator
    {
        foreach($this->imps_retencion as $imp_retencion) {
            yield [
                'id' => $imp_retencion['Id'],
                'nombre' => $imp_retencion['Name'],
                'valor' => $imp_retencion['Value']
            ];
        }
    }
}
