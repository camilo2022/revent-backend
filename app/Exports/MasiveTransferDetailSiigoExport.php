<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class MasiveTransferDetailSiigoExport extends DefaultValueBinder implements FromGenerator, Responsable, WithHeadings, WithTitle, WithEvents
{
    use Exportable;

    protected $transfer_details;
    protected $warehouses;

    protected string $warehousesSheetTitle = 'bodegas';

    public function __construct($transfer_details, $warehouses = [])
    {
        $this->transfer_details = $transfer_details;
        $this->warehouses = $warehouses;
    }

    public function headings(): array
    {
        return [
            'codigo',
            'descripcion',
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
                'descripcion' => $transfer_detail['descripcion'],
                'bodega_salida' => $transfer_detail['bodega_salida'],
                'bodega_entrada' => $transfer_detail['bodega_entrada'],
                'cantidad' => $transfer_detail['cantidad']
            ];
        }
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Lista "ID-NOMBRE" de la hoja bodegas (columna A); al cargar se toma solo el ID.
                $this->applyWarehouseList($sheet, 'C');
                $this->applyWarehouseList($sheet, 'D');
            },
        ];
    }

    protected function applyWarehouseList($sheet, string $columnLetter): void
    {
        if (empty($this->warehouses)) {
            return;
        }

        $endRow = count($this->warehouses) + 1;

        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1("'{$this->warehousesSheetTitle}'!\$A\$2:\$A\${$endRow}");

        $sheet->setDataValidation("{$columnLetter}2:{$columnLetter}1000", $validation);
    }
}
