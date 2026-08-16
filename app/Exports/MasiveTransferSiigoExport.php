<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class MasiveTransferSiigoExport extends DefaultValueBinder implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $transfer;

    public function __construct($transfer)
    {
        $this->transfer = $transfer;
    }

    public function headings(): array
    {
        return [
            'token',
            'fecha',
            'validar_disponible',
            'tipo',
            'observacion'
        ];
    }

    public function title(): string
    {
        return 'traslado';
    }

    public function generator(): Generator
    {
        foreach ($this->transfer as $transfer) {
            yield [
                'token' => $transfer['token'],
                'fecha' => $transfer['fecha'],
                'validar_disponible' => $transfer['validar_disponible'],
                'tipo' => $transfer['tipo'],
                'observacion' => $transfer['observacion']
            ];
        }
    }
}
