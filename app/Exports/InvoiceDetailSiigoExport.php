<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class InvoiceDetailSiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $sellers;
    protected $cost_centers;
    protected $invoices;
    protected $credit_notes;
    protected $purchases;
    protected $products;
    protected $stores;
    protected $column;

    public function __construct($sellers, $cost_centers, $invoices, $credit_notes, $purchases, $products, $stores, $column = true)
    {
        $this->sellers = $sellers;
        $this->cost_centers = $cost_centers;
        $this->invoices = $invoices;
        $this->credit_notes = $credit_notes;
        $this->purchases = $purchases;
        $this->products = $products;
        $this->stores = $stores;
        $this->column = $column;
    }

    public function headings(): array
    {
        $headings = [
            'PREFIJO',
            'NUMERO',
            'DOCUMENTO',
            'DOCUMENTO RELACIONADO',
            'FECHA DOCUMENTO',
            'CENTRO DE COSTO',
            'VENDEDOR',
            'MODELO',
            'CODIGO',
            'DESCRIPCION',
            'NOMBRE',
            'COLOR',
            'PROVEEDOR',
            'CATEGORIA',
            'TALLA',
            'PRECIO',
            'IMPUESTO'
        ];

        if($this->column) {
            $headings = [
                ...$headings,
                'DESCUENTO',
                'SUBTOTAL'
            ];
        }

        $headings = [
            ...$headings,
            'TOTAL',
            'CANTIDAD',
            'BODEGA',
            'FECHA',
            'SEGUNDA_FECHA',
            'DIFERENCIA'
        ];

        return $headings;
    }

    public function title(): string
    {
        return 'facturas_de_venta_detalles';
    }

    public function generator(): Generator
    {
        $documents = [
            [
                'data' => $this->invoices,
                'is_credit_note' => false,
            ],
            [
                'data' => $this->credit_notes,
                'is_credit_note' => true,
            ],
        ];

        foreach ($documents as $documentGroup) {

            foreach ($documentGroup['data'] as $document) {

                $cost_center = $this->cost_centers[$document['cost_center'] ?? ''] ?? [];
                $seller = $this->sellers[$document['seller'] ?? ''] ?? [];

                foreach ($document['items'] ?? [] as $item) {
                    if ($item['code'] == 'G18022025' && $this->column) continue;

                    $warehouseData = $item['warehouse'] ?? [];
                    $warehouseId = $warehouseData['id'] ?? null;

                    $firstDate = $warehouseId
                        ? ($this->purchases[$item['code'] ?? ''][$warehouseId]['first_date'] ?? null)
                        : null;

                    $secondDate = $warehouseId
                        ? ($this->purchases[$item['code'] ?? ''][$warehouseId]['second_date'] ?? null)
                        : null;

                    $diffDays = ($firstDate && $secondDate)
                        ? (new \DateTime($secondDate))->diff(new \DateTime($firstDate))->days
                        : null;

                    $warehouse = $warehouseId
                        ? ($this->stores[$warehouseId] ?? [])
                        : [];

                    $parts = preg_split('/[*-]/', $item['description'] ?? '');

                    $count = count($parts);

                    $name = trim($parts[0] ?? '');
                    $color = trim($parts[1] ?? '');
                    $provider = '';
                    $category = '';
                    $size = '';

                    if ($count === 3) {
                        $size = trim($parts[2] ?? '');
                    } elseif ($count === 4) {
                        $category = trim($parts[2] ?? '');
                        $size = trim($parts[3] ?? '');
                    } elseif ($count >= 5) {
                        $provider = trim($parts[$count - 3] ?? '');
                        $category = trim($parts[$count - 2] ?? '');
                        $size = trim($parts[$count - 1] ?? '');
                    }

                    $multiplier = $documentGroup['is_credit_note'] ? -1 : 1;

                    $row = [
                        'PREFIJO' => $documentGroup['is_credit_note'] ? '' : ($document['prefix'] ?? ''),
                        'NUMERO' => $document['number'] ?? '',
                        'DOCUMENTO' => $document['name'] ?? '',
                        'DOCUMENTO RELACIONADO' => $documentGroup['is_credit_note'] ? ($document['invoice']['name'] ?? '') : '',
                        'FECHA DOCUMENTO' => $document['date'] ?? '',
                        'CENTRO DE COSTO' => $cost_center['name'] ?? '',
                        'VENDEDOR' => $seller['first_name'] ?? '',
                        'MODELO' => $this->products[$item['code'] ?? '']['model'] ?? '',
                        'CODIGO' => $item['code'] ?? '',
                        'DESCRIPCION' => $item['description'] ?? '',
                        'NOMBRE' => $name,
                        'COLOR' => $color,
                        'PROVEEDOR' => $provider,
                        'CATEGORIA' => $category,
                        'TALLA' => $size,
                        'PRECIO' => ($item['price'] ?? 0) * $multiplier,
                        'IMPUESTO' => collect($item['taxes'] ?? [])->sum('value') * $multiplier,
                    ];

                    if($this->column) {
                        $row = [
                            ...$row,
                            'DESCUENTO' => ($item['discount']['value'] ?? 0) * $multiplier,
                            'SUBTOTAL' => (($item['total'] ?? 0) - collect($item['taxes'] ?? [])->sum('value')) * $multiplier,
                        ];
                    }

                    $row = [
                        ...$row,
                        'TOTAL' => ($item['total'] ?? 0) * $multiplier,
                        'CANTIDAD' => ($item['quantity'] ?? 0) * $multiplier,
                        'BODEGA' => ($warehouse['code'] ?? '') . ' - ' . ($warehouse['name'] ?? ($warehouseData['name'] ?? '')),
                        'FECHA' => $firstDate,
                        'SEGUNDA_FECHA' => $secondDate,
                        'DIFERENCIA' => $diffDays,
                    ];

                    yield $row;
                }
            }
        }
    }
}
