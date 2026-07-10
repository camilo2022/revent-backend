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

                yield [
                    'NUMERO' => $purchase['number'] ?? '',
                    'DOCUMENTO' => $purchase['name'] ?? '',
                    'FECHA DOCUMENTO' => $purchase['date'] ?? '',
                    'CENTRO DE COSTO' => $cost_center['name'] ?? '',
                    'OBSERVACIONES' => $purchase['observations'] ?? '',
                    'MODELO' => $this->products[$item['code'] ?? '']['model'] ?? '',
                    'CODIGO' => $item['code'] ?? '',
                    'DESCRIPCION' => $item['description'] ?? '',
                    'NOMBRE' => $name,
                    'COLOR' => $color,
                    'PROVEEDOR' => $provider,
                    'CATEGORIA' => $category,
                    'TALLA' => $size,
                    'CANTIDAD' => $item['quantity'],
                    'PRECIO' => $item['price'] ?? 0,
                    'TOTAL' => $item['total'],
                    'IMPUESTO' => collect($item['taxes'] ?? [])->sum('value'),
                    'BODEGA' => ($warehouse['code'] ?? '') . ' - ' . ($warehouse['name'] ?? ($warehouseData['name'] ?? '')),
                ];
            }
        }
    }
}
