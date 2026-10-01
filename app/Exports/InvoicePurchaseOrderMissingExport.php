<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InvoicePurchaseOrderMissingExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    public function __construct(
        private array $rows
    ) {}

    public function collection(): Collection
    {
        return collect($this->rows);
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
}