<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;

class MasiveTransferSiigoExport extends DefaultValueBinder implements FromGenerator, Responsable, WithHeadings, WithTitle, WithEvents
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $cellC = $sheet->getCell('C2');
                $validationC = $cellC->getDataValidation();
                $validationC->setType(DataValidation::TYPE_LIST);
                $validationC->setErrorStyle(DataValidation::STYLE_STOP);
                $validationC->setAllowBlank(false);
                $validationC->setShowInputMessage(true);
                $validationC->setShowErrorMessage(true);
                $validationC->setShowDropDown(true);
                $validationC->setErrorTitle('Valor no válido');
                $validationC->setError('Seleccione SI o NO.');
                $validationC->setPromptTitle('Seleccione un valor');
                $validationC->setPrompt('Elija SI o NO de la lista.');
                $validationC->setFormula1('"SI,NO"');

                $cellD = $sheet->getCell('D2');
                $validationD = $cellD->getDataValidation();
                $validationD->setType(DataValidation::TYPE_LIST);
                $validationD->setErrorStyle(DataValidation::STYLE_STOP);
                $validationD->setAllowBlank(false);
                $validationD->setShowInputMessage(true);
                $validationD->setShowErrorMessage(true);
                $validationD->setShowDropDown(true);
                $validationD->setErrorTitle('Valor no válido');
                $validationD->setError('Seleccione DIRECTO o TRANSITO.');
                $validationD->setPromptTitle('Seleccione un valor');
                $validationD->setPrompt('Elija DIRECTO o TRANSITO de la lista.');
                $validationD->setFormula1('"DIRECTO,TRANSITO"');
            },
        ];
    }
}
