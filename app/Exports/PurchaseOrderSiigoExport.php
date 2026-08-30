<?php

namespace App\Exports;

use Carbon\Carbon;
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

class PurchaseOrderSiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle, WithEvents
{
    use Exportable;

    protected $purchase_order_type;
    protected $filters;
    protected $cost_centers;
    protected $rete_iva;
    protected $rete_ica;

    protected string $costCenterSheetTitle = 'centro_costos';
    protected string $reteIvaSheetTitle = 'rete_iva';
    protected string $reteIcaSheetTitle = 'rete_ica';

    protected int $costCenterStartRow = 2;
    protected int $reteIvaStartRow = 2;
    protected int $reteIcaStartRow = 2;

    public function __construct($purchase_order_type, $filters, $cost_centers = [], $rete_iva = [], $rete_ica = [])
    {
        $this->purchase_order_type = $purchase_order_type;
        $this->filters = $filters;
        $this->cost_centers = $cost_centers;
        $this->rete_iva = $rete_iva;
        $this->rete_ica = $rete_ica;
    }

    public function headings(): array
    {
        $headings = [
            'cookie',
            'tipo',
            'tipo_orden',
            'fecha',
            'proveedor'
        ];

        if ($this->filters['use_cost_center']) $headings[] = 'centro_costo';

        $headings[] = 'observaciones';

        if ($this->filters['is_rete_iva']) $headings[] = 'rete_iva';
        if ($this->filters['is_rete_ica']) $headings[] = 'rete_ica';

        return $headings;
    }

    public function title(): string
    {
        return 'orden_compra';
    }

    public function generator(): Generator
    {
        $generator = [
            'cookie' => '',
            'tipo' => $this->purchase_order_type->ERPDocumentTypeID,
            'tipo_orden' => "{$this->purchase_order_type->ERPDocClass} - {$this->purchase_order_type->ERPDocCode} - {$this->purchase_order_type->InternalDescription}",
            'fecha' => Carbon::now()->format('d/m/Y'),
            'proveedor' => ''
        ];

        if ($this->filters['use_cost_center']) $generator['centro_costo'] = $this->purchase_order_type->CostCenterDefaultCode;

        $generator['observaciones'] = '';

        if ($this->filters['is_rete_iva']) $generator['rete_iva'] = '';
        if ($this->filters['is_rete_ica']) $generator['rete_ica'] = '';

        yield $generator;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                if ($this->filters['use_cost_center'] && !empty($this->cost_centers)) {
                    $this->applyListValidation($sheet, 'centro_costo', $this->costCenterSheetTitle, count($this->cost_centers), $this->costCenterStartRow);
                }

                if ($this->filters['is_rete_ica'] && !empty($this->rete_ica)) {
                    $this->applyListValidation($sheet, 'rete_ica', $this->reteIcaSheetTitle, count($this->rete_ica), $this->reteIcaStartRow);
                }

                if ($this->filters['is_rete_iva'] && !empty($this->rete_iva)) {
                    $this->applyListValidation($sheet, 'rete_iva', $this->reteIvaSheetTitle, count($this->rete_iva), $this->reteIvaStartRow);
                }
            },
        ];
    }

    protected function applyListValidation(Worksheet $sheet, string $headingKey, string $listSheetName, int $count, int $startRow): void
    {
        if ($count === 0) return;

        $columnLetter = $this->getColumnLetterFor($headingKey);
        if (!$columnLetter) return;

        $endRow = $startRow + $count - 1;

        $validation = $sheet->getCell($columnLetter . '2')->getDataValidation();
        $validation->setType(DataValidation::TYPE_LIST);
        $validation->setErrorStyle(DataValidation::STYLE_STOP);
        $validation->setAllowBlank(true);
        $validation->setShowInputMessage(true);
        $validation->setShowErrorMessage(true);
        $validation->setShowDropDown(true);
        $validation->setFormula1("'{$listSheetName}'!\$A\${$startRow}:\$A\${$endRow}");
    }

    protected function getColumnLetterFor(string $headingKey): ?string
    {
        $headings = $this->headings();
        $index = array_search($headingKey, $headings);

        if ($index === false) return null;

        return Coordinate::stringFromColumnIndex($index + 1);
    }
}
