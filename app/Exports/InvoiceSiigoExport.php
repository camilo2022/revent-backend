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
        foreach($this->invoices as $invoice) {
            $cost_center = $this->cost_centers[$invoice['cost_center']] ?? [];
            $seller = $this->sellers[$invoice['seller']] ?? [];
            foreach($invoice['items'] ?? [] as $item) {
                if(isset($item['taxes'])) {
                    $firstDate = $this->purchases[$item['code']][$item['warehouse']['id']]['first_date'] ?? null;
                    $secondDate = $this->purchases[$item['code']][$item['warehouse']['id']]['second_date'] ?? null;
                    $diffDays = ($firstDate && $secondDate) ? (new \DateTime($secondDate))->diff(new \DateTime($firstDate))->days : null;

                    $warehouse = $this->stores[$item['warehouse']['id']] ?? [];

                    $parts = preg_split('/[*-]/', $item['description'] ?? '');

                    $count = count($parts);

                    $name = $parts[0] ?? '#N/A';
                    $color = $parts[1] ?? '#N/A';
                    $category = '#N/A';
                    $size = '#N/A';

                    if ($count === 3) {
                        $size = $parts[2] ?? '#N/A';
                    } elseif ($count === 4) {
                        $category = $parts[2] ?? '#N/A';
                        $size = $parts[3] ?? '#N/A';
                    } elseif ($count >= 5) {
                        $category = $parts[$count - 2] ?? '#N/A';
                        $size = $parts[$count - 1] ?? '#N/A';
                    }

                    yield [
                        'PREFIJO' => $invoice['prefix'],
                        'NUMERO' => $invoice['number'],
                        'DOCUMENTO' => $invoice['name'],
                        'DOCUMENTO RELACIONADO' => '#N/A',
                        'FECHA DOCUMENTO' => $invoice['date'],
                        'CENTRO DE COSTO' => $cost_center['name'] ?? '#N/A',
                        'VENDEDOR' => $seller['first_name'] ?? '#N/A',
                        'MODELO' => $this->products[$item['code']]['model'] ?? '#N/A',
                        'CODIGO' => $item['code'],
                        'DESCRIPCION' => $item['description'],
                        'NOMBRE' => $name,
                        'COLOR' => $color,
                        'CATEGORIA' => $category,
                        'TALLA' => $size,
                        'PRECIO' => $item['price'],
                        'IMPUESTO' => collect($item['taxes'])->sum('value'),
                        'TOTAL' => $item['total'] ?? 0,
                        'CANTIDAD' => $item['quantity'],
                        'BODEGA' => ($warehouse['code'] ?? '#N/A') . ' - ' . ($warehouse['name'] ?? ($item['warehouse']['name'] ?? '#N/A')),
                        'FECHA' => $firstDate,
                        'SEGUNDA_FECHA' => $secondDate,
                        'DIFERENCIA' => $diffDays,
                    ];
                }
            }
        }

        foreach($this->credit_notes as $credit_note) {
            $cost_center = $this->cost_centers[$credit_note['cost_center']] ?? [];
            $seller = $this->sellers[$credit_note['seller']] ?? [];
            foreach($credit_note['items'] ?? [] as $item) {
                if(isset($item['taxes'])) {
                    $firstDate = $this->purchases[$item['code']][$item['warehouse']['id']]['first_date'] ?? null;
                    $secondDate = $this->purchases[$item['code']][$item['warehouse']['id']]['second_date'] ?? null;
                    $diffDays = ($firstDate && $secondDate) ? (new \DateTime($secondDate))->diff(new \DateTime($firstDate))->days : null;

                    $warehouse = $this->stores[$item['warehouse']['id']] ?? [];

                    $parts = preg_split('/[*-]/', $item['description'] ?? '');

                    $count = count($parts);

                    $name = $parts[0] ?? '#N/A';
                    $color = $parts[1] ?? '#N/A';
                    $category = '#N/A';
                    $size = '#N/A';

                    if ($count === 3) {
                        $size = $parts[2] ?? '#N/A';
                    } elseif ($count === 4) {
                        $category = $parts[2] ?? '#N/A';
                        $size = $parts[3] ?? '#N/A';
                    } elseif ($count >= 5) {
                        $category = $parts[$count - 2] ?? '#N/A';
                        $size = $parts[$count - 1] ?? '#N/A';
                    }

                    yield [
                        'PREFIJO' => '#N/A',
                        'NUMERO' => $credit_note['number'],
                        'DOCUMENTO' => $credit_note['name'],
                        'DOCUMENTO RELACIONADO' => $credit_note['invoice']['name'] ?? '#N/A',
                        'FECHA DOCUMENTO' => $credit_note['date'],
                        'CENTRO DE COSTO' => $cost_center['name'] ?? '#N/A',
                        'VENDEDOR' => $seller['first_name'] ?? '#N/A',
                        'MODELO' => $this->products[$item['code']]['model'] ?? '#N/A',
                        'CODIGO' => $item['code'],
                        'DESCRIPCION' => $item['description'],
                        'NOMBRE' => $name,
                        'COLOR' => $color,
                        'CATEGORIA' => $category,
                        'TALLA' => $size,
                        'PRECIO' => -abs($item['price']),
                        'IMPUESTO' => -abs(collect($item['taxes'])->sum('value')),
                        'TOTAL' => -abs($item['total'] ?? 0),
                        'CANTIDAD' => -abs($item['quantity']),
                        'BODEGA' => ($warehouse['code'] ?? '#N/A') . ' - ' . ($warehouse['name'] ?? ($item['warehouse']['name'] ?? '#N/A')),
                        'FECHA' => $firstDate,
                        'SEGUNDA_FECHA' => $secondDate,
                        'DIFERENCIA' => $diffDays,
                    ];
                }
            }
        }
    }
}
