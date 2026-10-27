<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

/** Una hoja del reporte de inventarios (positivo o negativo) con filas ya calculadas. */
class InventorySiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $name;
    protected $rows;

    public function __construct($name, array $rows)
    {
        $this->name = $name;
        $this->rows = $rows;
    }

    public function headings(): array
    {
        return [
            'CUENTA DE GRUPO',
            'CODIGO',
            'DESCRIPCION',
            'NOMBRE',
            'COLOR',
            'PROVEEDOR',
            'CATEGORIA',
            'TALLA',
            'CODIGO_BARRAS',
            'MARCA',
            'MODELO',
            'ESTADO',
            'BODEGA',
            'CANTIDAD',
            'FECHA',
            'SEGUNDA_FECHA',
            'DIFERENCIA',
        ];
    }

    public function title(): string
    {
        return $this->name;
    }

    public function generator(): Generator
    {
        yield from $this->rows;
    }
}
