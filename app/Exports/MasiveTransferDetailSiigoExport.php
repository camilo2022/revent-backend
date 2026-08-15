<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class MasiveTransferDetailSiigoExport extends DefaultValueBinder implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $transfer_details;

    public function __construct($transfer_details)
    {
        $this->transfer_details = $transfer_details;
    }

    public function headings(): array
    {
        return [
            'codigo',
            'bodega_salida',
            'bodega_entrada',
            'cantidad'
        ];
    }

    public function title(): string
    {
        return 'traslado_detalles';
    }

    public function generator(): Generator
    {
        foreach ($this->transfer_details as $transfer_detail) {
            yield [
                'codigo' => $transfer_detail['codigo'],
                'bodega_salida' => $transfer_detail['bodega_salida'],
                'bodega_entrada' => $transfer_detail['bodega_entrada'],
                'cantidad' => $transfer_detail['cantidad']
            ];
        }
    }
}
