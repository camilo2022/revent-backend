<?php

namespace App\Exports;

use Carbon\Carbon;
use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

class InvoiceSiigoExport extends DefaultValueBinder implements FromGenerator, Responsable, WithHeadings, WithTitle, WithCustomValueBinder, WithColumnFormatting
{
    use Exportable;

    protected $sellers;
    protected $cost_centers;
    protected $invoices;
    protected $documents;

    public function __construct($sellers, $cost_centers, $invoices, $documents)
    {
        $this->sellers = $sellers;
        $this->cost_centers = $cost_centers;
        $this->invoices = $invoices;
        $this->documents = $documents;
    }

    public function headings(): array
    {
        return [
            'PREFIJO',
            'NUMERO',
            'DOCUMENTO',
            'CLIENTE',
            'FECHA DOCUMENTO',
            'DIA SEMANA',
            'MES',
            'HORA',
            'CENTRO DE COSTO',
            'VENDEDOR',
            'IMPUESTO',
            'DESCUENTO',
            'SUBTOTAL',
            'CANTIDAD',
            'TOTAL',
            'METODOS PAGOS',
            '360 CANTIDAD',
            '360 VALOR',
            '360'
        ];
    }

    public function title(): string
    {
        return 'facturas_de_venta';
    }

    public function generator(): Generator
    {
        Carbon::setLocale('es');

        foreach ($this->invoices as $document) {

            $cost_center = $this->cost_centers[$document['cost_center'] ?? ''] ?? [];
            $seller = $this->sellers[$document['seller'] ?? ''] ?? [];

            $items = collect($document['items'])->whereNotIn('code', ['G18022025', 'P18022025'])->values();

            $impuesto = $items->sum(fn ($item) => collect($item['taxes'] ?? [])->sum('value'));
            $descuento = $items->sum('discount.value');
            $cantidad = $items->sum('quantity');
            $total = $items->sum('total');

            $fecha_documento = !empty($this->documents[$document['name']]['date'])
                ? Carbon::parse($this->documents[$document['name']]['date'])->locale('es')
                : (!empty($document['date']) ? Carbon::parse($document['date'])->locale('es') : null);

            $cantidad360 = $this->get360Cantidad($cantidad);
            $valor360 = $this->get360Valor($total);
            $resultado360 = $this->get360Resultado($cantidad360, $valor360);

            yield [
                'PREFIJO' => $document['prefix'] ?? '',
                'NUMERO' => $document['number'] ?? '',
                'DOCUMENTO' => isset($this->documents[$document['name']])
                    ? '=HYPERLINK("' . ($this->documents[$document['name']]['url'] ?? $document['public_url']) . '","' . ($document['name'] ?? '') . '")'
                    : ($document['name'] ?? ''),
                'CLIENTE' => isset($this->documents[$document['name']]) ? $this->documents[$document['name']]['name'] : 'GENERICO',
                'FECHA DOCUMENTO' => $fecha_documento
                    ? $fecha_documento->format('d/m/Y')
                    : ($document['date'] ?? ''),
                'DIA SEMANA' => $fecha_documento
                    ? mb_strtoupper($fecha_documento->translatedFormat('l'))
                    : '',
                'MES' => $fecha_documento
                    ? mb_strtoupper($fecha_documento->translatedFormat('F'))
                    : '',
                'HORA' => $fecha_documento
                    ? $fecha_documento->format('h:i:s A')
                    : '',
                'CENTRO DE COSTO' => $cost_center['name'] ?? '',
                'VENDEDOR' => mb_strtoupper($seller ? ($seller['first_name'] . ' ' . $seller['last_name']) : ''),
                'IMPUESTO' => (float) ($impuesto ?? 0),
                'DESCUENTO' => (float) ($descuento ?? 0),
                'SUBTOTAL' => (float) ($total - $impuesto),
                'CANTIDAD' => (int) $cantidad,
                'TOTAL' => (float) $total,
                'METODOS PAGOS' => collect($document['payments'])->count(),
                '360 CANTIDAD' => $cantidad360,
                '360 VALOR' => $valor360,
                '360' => $resultado360,
            ];
        }
    }

    public function bindValue(Cell $cell, $value)
    {
        if (is_string($value) && str_starts_with($value, '=HYPERLINK(')) {
            $cell->setValueExplicit($value, DataType::TYPE_FORMULA);
            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function columnFormats(): array
    {
        $formatoContable = '_-$* #,##0_-;-$* #,##0_-;_-$* "-"_-;_-@_-';

        return [
            'K' => NumberFormat::FORMAT_ACCOUNTING_USD, // IMPUESTO
            'L' => NumberFormat::FORMAT_ACCOUNTING_USD, // DESCUENTO
            'M' => NumberFormat::FORMAT_ACCOUNTING_USD, // SUBTOTAL
            'O' => NumberFormat::FORMAT_ACCOUNTING_USD, // TOTAL
        ];
    }

    private function get360Cantidad(int $pares): string
    {
        return match (true) {
            $pares <= 2 => '-',
            $pares <= 5 => '360',
            $pares <= 8 => '360X2',
            $pares <= 11 => '360X3',
            $pares <= 14 => '360X4',
            $pares <= 17 => '360X5',
            default => '360X6',
        };
    }

    private function get360Valor(float $valor): string
    {
        return match (true) {
            $valor < 200000 => '-',
            $valor < 400000 => '360',
            $valor < 600000 => '360X2',
            $valor < 800000 => '360X3',
            $valor < 1000000 => '360X4',
            $valor < 1200000 => '360X5',
            default => '360X6',
        };
    }

    private function get360Resultado(string $cantidad, string $factura): float|string
    {
        if ($cantidad === '-' || $factura === '-') {
            return '-';
        }

        $niveles = [
            '360'   => 1,
            '360X2' => 2,
            '360X3' => 3,
            '360X4' => 4,
            '360X5' => 5,
            '360X6' => 6,
        ];

        return ($niveles[$cantidad] + $niveles[$factura]) / 2;
    }
}
