<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

/**
 * Excel de la trazabilidad con auditoría de tránsitos: las columnas del Excel de trazabilidad de siempre
 * (movimientos con saldo acumulado) más el resultado de cada traslado y sus bodegas de origen y destino.
 * Es el mismo contenido de la pantalla.
 */
class ProductTraceabilityAuditExport extends DefaultValueBinder implements FromGenerator, Responsable, WithHeadings, WithTitle, WithCustomValueBinder
{
    use Exportable;

    private const RESULTS = [
        'transito' => 'POR TRÁNSITO',
        'directo' => 'TRASLADO DIRECTO',
        'parcial' => 'PARCIAL',
        'sin_bodega_transito' => 'BODEGA SIN TRÁNSITO',
        'salida_transito' => 'SALE A TRÁNSITO',
        'salida_directa' => 'SALIDA DIRECTA A OTRA BODEGA',
        'sin_origen' => 'SIN DATO DE ORIGEN',
    ];

    public function __construct(private array $audit)
    {
    }

    public function headings(): array
    {
        return [
            'BODEGA',
            'CODIGO PRODUCTO',
            'NOMBRE PRODUCTO',
            'REFERENCIA FABRICA',
            'COMPROBANTE',
            'SECUENCIA',
            'FECHA',
            'HORA',
            'CANTIDAD INICIAL',
            'CANTIDAD ENTRADA',
            'CANTIDAD SALIDA',
            'SALDO CANTIDADES',
            'VALOR ENTRADA',
            'VALOR SALIDA',
            'RESULTADO',
            'BODEGA ORIGEN',
            'BODEGA DESTINO',
        ];
    }

    public function title(): string
    {
        return 'trazabilidad_de_producto';
    }

    public function generator(): Generator
    {
        foreach ($this->audit['products'] as $product) {
            if (!$product['rows']) {
                yield [
                    'BODEGA' => '-',
                    'CODIGO PRODUCTO' => $product['code'],
                    'NOMBRE PRODUCTO' => '-',
                    'REFERENCIA FABRICA' => null,
                    'COMPROBANTE' => null,
                    'SECUENCIA' => 0,
                    'FECHA' => null,
                    'HORA' => null,
                    'CANTIDAD INICIAL' => null,
                    'CANTIDAD ENTRADA' => null,
                    'CANTIDAD SALIDA' => null,
                    'SALDO CANTIDADES' => null,
                    'VALOR ENTRADA' => null,
                    'VALOR SALIDA' => null,
                    'RESULTADO' => null,
                    'BODEGA ORIGEN' => null,
                    'BODEGA DESTINO' => null,
                ];

                continue;
            }

            foreach ($product['rows'] as $row) {
                yield [
                    'BODEGA' => $this->audit['warehouse'],
                    'CODIGO PRODUCTO' => $product['code'],
                    'NOMBRE PRODUCTO' => $product['name'],
                    'REFERENCIA FABRICA' => $product['factory_reference'],
                    'COMPROBANTE' => $row['voucher'] && $row['url']
                        ? '=HYPERLINK("' . $row['url'] . '","' . $row['voucher'] . '")'
                        : $row['voucher'],
                    'SECUENCIA' => $row['sequence'],
                    'FECHA' => $row['date'],
                    'HORA' => $row['time'],
                    'CANTIDAD INICIAL' => $this->quantity($row['initial']),
                    'CANTIDAD ENTRADA' => $row['entry'],
                    'CANTIDAD SALIDA' => $row['output'],
                    'SALDO CANTIDADES' => $this->quantity($row['balance']),
                    'VALOR ENTRADA' => $row['entry_value'],
                    'VALOR SALIDA' => $row['output_value'],
                    'RESULTADO' => $this->result($row),
                    'BODEGA ORIGEN' => $row['origin'],
                    'BODEGA DESTINO' => $row['destination'],
                ];
            }
        }
    }

    private function result(array $row): ?string
    {
        if ($row['status']) {
            $label = self::RESULTS[$row['status']];

            return $row['status'] === 'parcial'
                ? "{$label} ({$this->number($row['via_transit'])} por tránsito, {$this->number($row['direct'])} directas)"
                : $label;
        }

        return $row['entry'] > 0 ? 'NO ES TRASLADO' : null;
    }

    /** Cantidad como texto numérico: la librería de Excel deja vacía una celda con valor 0, y un saldo de 0 debe verse. */
    private function quantity(float $n): string
    {
        return $this->number($n) ?: '0';
    }

    private function number(float $n): string
    {
        return rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
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
