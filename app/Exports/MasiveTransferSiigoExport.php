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
            'usuario',
            'contraseña',
            'fecha',
            'validar_disponible',
            'tipo',
            'separar_traslados',
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
                'usuario' => $transfer['usuario'],
                'contraseña' => $transfer['contraseña'],
                'fecha' => $transfer['fecha'],
                'validar_disponible' => $transfer['validar_disponible'],
                'tipo' => $transfer['tipo'],
                'separar_traslados' => $transfer['separar_traslados'],
                'observacion' => $transfer['observacion']
            ];
        }
    }
}
