<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class InvoiceSiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $token;
    protected $sellers;
    protected $cost_centers;
    protected $invoices;
    protected $credit_notes;
    protected $purchases;
    protected $products;
    protected $stores;
    protected $baseUrl;

    public function __construct($token, $sellers, $cost_centers, $invoices, $credit_notes, $purchases, $products, $stores, $baseUrl)
    {
        $this->token = $token;
        $this->sellers = $sellers;
        $this->cost_centers = $cost_centers;
        $this->invoices = $invoices;
        $this->credit_notes = $credit_notes;
        $this->purchases = $purchases;
        $this->products = $products;
        $this->stores = $stores;
        $this->baseUrl = $baseUrl;
    }

    public function headings(): array
    {
        return [
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
            'IMPUESTO',
            'TOTAL',
            'CANTIDAD',
            'BODEGA',
            'FECHA',
            'SEGUNDA_FECHA',
            'DIFERENCIA',
        ];
    }

    public function title(): string
    {
        return 'facturas_de_venta';
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

                    if (!isset($item['taxes'])) {
                        continue;
                    }

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

                    $name = trim($parts[0] ?? '#N/A');
                    $color = trim($parts[1] ?? '#N/A');
                    $provider = '#N/A';
                    $category = '#N/A';
                    $size = '#N/A';

                    if ($count === 3) {
                        $size = trim($parts[2] ?? '#N/A');
                    } elseif ($count === 4) {
                        $category = trim($parts[2] ?? '#N/A');
                        $size = trim($parts[3] ?? '#N/A');
                    } elseif ($count >= 5) {
                        $provider = trim($parts[$count - 3] ?? '#N/A');
                        $category = trim($parts[$count - 2] ?? '#N/A');
                        $size = trim($parts[$count - 1] ?? '#N/A');
                    }

                    $multiplier = $documentGroup['is_credit_note'] ? -1 : 1;

                    yield [
                        'PREFIJO' => $documentGroup['is_credit_note'] ? '#N/A' : ($document['prefix'] ?? '#N/A'),
                        'NUMERO' => $document['number'] ?? '#N/A',
                        'DOCUMENTO' => $document['name'] ?? '#N/A',
                        'DOCUMENTO RELACIONADO' => $documentGroup['is_credit_note'] ? ($document['invoice']['name'] ?? '#N/A') : '#N/A',
                        'FECHA DOCUMENTO' => $document['date'] ?? '#N/A',
                        'CENTRO DE COSTO' => $cost_center['name'] ?? '#N/A',
                        'VENDEDOR' => $seller['first_name'] ?? '#N/A',
                        'MODELO' => $this->products[$item['code'] ?? '']['model'] ?? '#N/A',
                        'CODIGO' => $item['code'] ?? '#N/A',
                        'DESCRIPCION' => $item['description'] ?? '#N/A',
                        'NOMBRE' => $name,
                        'COLOR' => $color,
                        'PROVEEDOR' => $provider,
                        'CATEGORIA' => $category,
                        'TALLA' => $size,
                        'PRECIO' => (($item['price'] ?? 0) * ($item['quantity'] ?? 0)) * $multiplier,
                        'IMPUESTO' => collect($item['taxes'])->sum('value') * $multiplier,
                        'TOTAL' => ($item['total'] ?? 0) * $multiplier,
                        'CANTIDAD' => ($item['quantity'] ?? 0) * $multiplier,
                        'BODEGA' => ($warehouse['code'] ?? '#N/A') . ' - ' . ($warehouse['name'] ?? ($warehouseData['name'] ?? '#N/A')),
                        'FECHA' => $firstDate,
                        'SEGUNDA_FECHA' => $secondDate,
                        'DIFERENCIA' => $diffDays,
                    ];
                }
            }
        }
    }
}
