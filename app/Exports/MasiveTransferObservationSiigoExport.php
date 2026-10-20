<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class MasiveTransferObservationSiigoExport extends DefaultValueBinder implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $transfer_observations;

    public function __construct($transfer_observations)
    {
        $this->transfer_observations = $transfer_observations;
    }

    public function headings(): array
    {
        return [
            'bodega_salida',
            'bodega_entrada',
            'observacion'
        ];
    }

    public function title(): string
    {
        return 'traslado_observaciones';
    }

    public function generator(): Generator
    {
        foreach ($this->transfer_observations as $transfer_observation) {
            yield [
                'bodega_salida' => $transfer_observation['bodega_salida'],
                'bodega_entrada' => $transfer_observation['bodega_entrada'],
                'observacion' => $transfer_observation['observacion']
            ];
        }
    }
}
