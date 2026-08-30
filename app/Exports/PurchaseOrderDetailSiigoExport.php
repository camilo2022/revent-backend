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
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class PurchaseOrderDetailSiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle, WithEvents
{
    use Exportable;

    protected $type_details;
    protected $warehouses;
    protected $imp_cargo;
    protected $imp_retencion;

    protected string $typeDetailsSheetTitle = 'tipo_detalles';
    protected string $warehousesSheetTitle = 'bodegas';
    protected string $impCargoSheetTitle = 'imp_cargo';
    protected string $impRetencionSheetTitle = 'imp_retencion';

    protected int $startRow = 2;

    public function __construct($type_details, $warehouses, $imp_cargo, $imp_retencion)
    {
        $this->type_details = $type_details;
        $this->warehouses = $warehouses;
        $this->imp_cargo = $imp_cargo;
        $this->imp_retencion = $imp_retencion;
    }

    public function headings(): array
    {
        return [
            'tipo_detalle',
            'item',
            'bodega',
            'cantidad',
            'valor_unitario',
            'descuento',
            'imp_cargo',
            'imp_retencion'
        ];
    }

    public function title(): string
    {
        return 'orden_compra_detalles';
    }

    public function generator(): Generator
    {
        yield [
            'tipo_detalle' => '',
            'item' => '',
            'bodega' => '',
            'cantidad' => '',
            'valor_unitario' => '',
            'descuento' => '',
            'imp_cargo' => '',
            'imp_retencion' => ''
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $this->applyListValidation($sheet, 'tipo_detalle', $this->typeDetailsSheetTitle, count($this->type_details));
                $this->applyListValidation($sheet, 'bodega', $this->warehousesSheetTitle, count($this->warehouses));
                $this->applyListValidation($sheet, 'imp_cargo', $this->impCargoSheetTitle, count($this->imp_cargo));
                $this->applyListValidation($sheet, 'imp_retencion', $this->impRetencionSheetTitle, count($this->imp_retencion));
            },
        ];
    }

    protected function applyListValidation(Worksheet $sheet, string $headingKey, string $listSheetName, int $count): void
    {
        if ($count === 0) return;

        $columnLetter = $this->getColumnLetterFor($headingKey);
        if (!$columnLetter) return;

        $endRow = $this->startRow + $count - 1;
        $lastDataRow = 1000;

        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1("'{$listSheetName}'!\$A\${$this->startRow}:\$A\${$endRow}");

        $sheet->setDataValidation("{$columnLetter}2:{$columnLetter}{$lastDataRow}", $validation);
    }

    protected function getColumnLetterFor(string $headingKey): ?string
    {
        $headings = $this->headings();
        $index = array_search($headingKey, $headings);

        if ($index === false) return null;

        return Coordinate::stringFromColumnIndex($index + 1);
    }
}
