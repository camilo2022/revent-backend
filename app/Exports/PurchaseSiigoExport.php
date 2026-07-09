<?php

namespace App\Exports;

use Generator;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class PurchaseSiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $token;
    protected $cost_centers;
    protected $purchases;
    protected $products;
    protected $stores;
    protected $baseUrl;

    public function __construct($token, $cost_centers, $purchases, $products, $stores, $baseUrl)
    {
        $this->token = $token;
        $this->cost_centers = $cost_centers;
        $this->purchases = $purchases;
        $this->products = $products;
        $this->stores = $stores;
        $this->baseUrl = $baseUrl;
    }

    public function headings(): array
    {
        return [
            'NUMERO',
            'DOCUMENTO',
            'FECHA DOCUMENTO',
            'CENTRO DE COSTO',
            'OBSERVACIONES',
            'MODELO',
            'CODIGO',
            'DESCRIPCION',
            'NOMBRE',
            'COLOR',
            'PROVEEDOR',
            'CATEGORIA',
            'TALLA',
            'CANTIDAD',
            'PRECIO',
            'TOTAL',
            'IMPUESTO',
            'BODEGA'
        ];
    }

    public function title(): string
    {
        return 'facturas_de_venta';
    }

    public function generator(): Generator
    {
        foreach ($this->purchases as $purchase) {
            $cost_center = $this->cost_centers[$purchase['cost_center'] ?? ''] ?? [];

            foreach ($purchase['items'] ?? [] as $item) {

                $warehouseData = $item['warehouse'] ?? [];
                $warehouseId = $warehouseData['id'] ?? null;

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

                yield [
                    'NUMERO' => $purchase['number'] ?? '#N/A',
                    'DOCUMENTO' => $purchase['name'] ?? '#N/A',
                    'FECHA DOCUMENTO' => $purchase['date'] ?? '#N/A',
                    'CENTRO DE COSTO' => $cost_center['name'] ?? '#N/A',
                    'OBSERVACIONES' => $purchase['observations'] ?? '#N/A',
                    'MODELO' => $this->products[$item['code'] ?? '']['model'] ?? '#N/A',
                    'CODIGO' => $item['code'] ?? '#N/A',
                    'DESCRIPCION' => $item['description'] ?? '#N/A',
                    'NOMBRE' => $name,
                    'COLOR' => $color,
                    'PROVEEDOR' => $provider,
                    'CATEGORIA' => $category,
                    'TALLA' => $size,
                    'CANTIDAD' => $item['quantity'],
                    'PRECIO' => $item['price'] ?? 0,
                    'TOTAL' => $item['total'],
                    'IMPUESTO' => collect($item['taxes'] ?? [])->sum('value'),
                    'BODEGA' => ($warehouse['code'] ?? '#N/A') . ' - ' . ($warehouse['name'] ?? ($warehouseData['name'] ?? '#N/A')),
                ];
            }
        }
    }
}
