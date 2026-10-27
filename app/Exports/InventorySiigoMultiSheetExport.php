<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Reporte de inventarios: una hoja por tipo de inventario (positivo y/o negativo). */
class InventorySiigoMultiSheetExport implements WithMultipleSheets
{
    use Exportable;

    /** @param array<string,array> $sheets nombre de la hoja => filas */
    public function __construct(private array $sheets)
    {
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->sheets as $name => $rows) {
            $sheets[] = new InventorySiigoExport($name, $rows);
        }

        return $sheets;
    }
}
