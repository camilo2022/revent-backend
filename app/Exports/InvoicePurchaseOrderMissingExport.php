<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class InvoicePurchaseOrderMissingExport extends DefaultValueBinder implements FromCollection, WithHeadings, ShouldAutoSize, WithCustomValueBinder
{
    public function __construct(private array $rows) {}

    public function collection(): Collection
    {
        return collect($this->rows)->map(function ($row) {

            $docName = $row['DocName'] ?? '';
            $acEntryID = $row['ACEntryID'] ?? null;

            $urlDocument = $acEntryID ? 'https://siigonube.siigo.com/#/asp/' . base64_encode('Default.aspx?TabID=1671&ERPDocumentID=' . $acEntryID) . '?TabID=1671' : null;

            return [
                'ORDEN DE COMPRA' => $urlDocument ? '=HYPERLINK("' . $urlDocument . '","' . str_replace('"', '""', $docName) . '")' : $docName,
                'RAZÓN SOCIAL' => $row['FullName'] ?? '',
                'NOMBRE COMERCIAL' => $row['CompanyName'] ?? '',
                'IDENTIFICACIÓN' => $row['Identification'] ?? '',
                'FECHA' => $row['Date'] ?? '',
                'DESCRIPCIÓN DE LA REFERENCIA' => $row['Description'] ?? '',
                'REFERENCIA' => $row['Reference'] ?? '',
                'MODELO' => $row['Model'] ?? '',
                'COLOR' => $row['Color'] ?? '',
                'TALLA' => $row['Size'] ?? '',
                'BODEGA' => $row['Warehouse'] ?? '',
                'SOLICITADA' => $row['Requested'] ?? 0,
                'RECIBIDA' => $row['Received'] ?? 0,
                'PENDIENTE' => $row['Pending'] ?? 0,
            ];
        });
    }

    public function headings(): array
    {
        return [
            'ORDEN DE COMPRA',
            'RAZÓN SOCIAL',
            'NOMBRE COMERCIAL',
            'IDENTIFICACIÓN',
            'FECHA',
            'DESCRIPCIÓN DE LA REFERENCIA',
            'REFERENCIA',
            'MODELO',
            'COLOR',
            'TALLA',
            'BODEGA',
            'SOLICITADA',
            'RECIBIDA',
            'PENDIENTE',
        ];
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value) && str_starts_with($value, '=HYPERLINK(')) {
            $cell->setValueExplicit($value, DataType::TYPE_FORMULA);
            return true;
        }

        return parent::bindValue($cell, $value);
    }
}