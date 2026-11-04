<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Excel del reporte: una columna por campo elegido y la fila de títulos resaltada. */
class ReportExport implements FromArray, ShouldAutoSize, WithHeadings, WithStyles
{
    /**
     * @param array<int, array{data_key: string, label: string}> $columns
     * @param array<int, array<string, mixed>> $rows
     */
    public function __construct(private array $columns, private array $rows)
    {
    }

    public function headings(): array
    {
        return array_column($this->columns, 'label');
    }

    public function array(): array
    {
        return array_map(
            fn ($row) => array_map(fn ($column) => $row[$column['data_key']] ?? '', $this->columns),
            $this->rows
        );
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '321FDB']]]];
    }
}
