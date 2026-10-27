<?php

namespace App\Exports;

use Exception;
use Generator;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;

class InventorySiigoExport implements FromGenerator, Responsable, WithHeadings, WithTitle
{
    use Exportable;

    protected $name;
    protected $token;
    protected $purchases;
    protected $stores;
    protected $filters;
    protected $baseUrl;
    protected $transfers;

    public function __construct($name, $token, $purchases, $stores, $filters, $baseUrl, $transfers = [])
    {
        $this->name = $name;
        $this->token = $token;
        $this->purchases = $purchases;
        $this->stores = $stores;
        $this->filters = $filters;
        $this->baseUrl = $baseUrl;
        $this->transfers = $transfers;
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
        $allowedFilters = [
            'updated_start',
            'updated_end',
            'type',
            'account_group',
            'code',
            'id',
            'page',
            'page_size',
        ];

        $queryParams = array_filter(
            array_intersect_key(
                $this->filters,
                array_flip($allowedFilters)
            ),
            function ($value) {
                return $value !== null && $value !== '';
            }
        );

        $url = "{$this->baseUrl}/v1/products?" . http_build_query($queryParams);

        do {
            $response = Http::retry(5, 10000)->timeout(180)->withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $this->token,
                'Partner-Id' => 'consultadeFacturas',
            ])->get($url);

            if ($response->status() === 429) {
                sleep(1);
                continue;
            }

            if (! $response->successful()) {
                throw new Exception($response->body());
            }

            $data = $response->json();

            foreach ($data['results'] ?? [] as $product) {
                $parts = preg_split(
                    '/[*-]/',
                    $product['name'] ?? ''
                );

                $count = count($parts);

                $name = $parts[0] ?? '';
                $color = $parts[1] ?? '';
                $provider = '';
                $category = '';
                $size = '';

                if ($count === 3) {
                    $size = $parts[2] ?? '';
                } elseif ($count === 4) {
                    $category = $parts[2] ?? '';
                    $size = $parts[3] ?? '';
                } elseif ($count >= 5) {
                    $provider = $parts[$count - 3] ?? '';
                    $category = $parts[$count - 2] ?? '';
                    $size = $parts[$count - 1] ?? '';
                }

                if (in_array($product['account_group']['id'] ?? null, config('siigo.inventory.excluded_account_groups'), true)) continue;

                $operator = ($this->filters['positive'] ?? true) ? '>' : '<';
                foreach (collect($product['warehouses'] ?? [])->where('quantity', $operator, 0)->whereNotNull('id')->whereNotIn('id', config('siigo.inventory.excluded_warehouses')) as $warehouse) {
                    // Las dos fechas de ingreso más antiguas entre las compras y las notas de traslado.
                    $entryDates = array_values(array_unique(array_filter([
                        $this->purchases[$product['code']][$warehouse['id']]['first_date'] ?? null,
                        $this->purchases[$product['code']][$warehouse['id']]['second_date'] ?? null,
                        ...($this->transfers[$warehouse['id']][$product['code']] ?? []),
                    ])));
                    sort($entryDates);

                    $firstDate = $entryDates[0] ?? null;

                    $secondDate = $entryDates[1] ?? null;

                    $diffDays = ($firstDate && $secondDate) ? (new \DateTime($secondDate))->diff(new \DateTime($firstDate))->days : null;

                    $warehouseId = $warehouse['id'] ?? null;
                    $store = $this->stores[$warehouseId] ?? null;

                    yield [
                        'CUENTA DE GRUPO' => $product['account_group']['name'] ?? null,
                        'CODIGO' => $product['code'] ?? null,
                        'DESCRIPCION' => $product['name'] ?? null,
                        'NOMBRE' => $name,
                        'COLOR' => $color,
                        'PROVEEDOR' => $provider,
                        'CATEGORIA' => $category,
                        'TALLA' => $size,
                        'CODIGO_BARRAS' => $product['additional_fields']['barcode'] ?? null,
                        'MARCA' => $product['additional_fields']['brand'] ?? null,
                        'MODELO' => $product['additional_fields']['model'] ?? null,
                        'ESTADO' => $store['status'] ?? 'APROBADO',
                        'BODEGA' => ($store['code'] ?? 'S/C') . ' - ' . ($store['name'] ?? ($warehouse['name'] ?? null)),
                        'CANTIDAD' => $warehouse['quantity'] ?? 0,
                        'FECHA' => $firstDate,
                        'SEGUNDA_FECHA' => $secondDate,
                        'DIFERENCIA' => $diffDays,
                    ];
                }
            }

            $url = $data['_links']['next']['href'] ?? null;

            if ($url) usleep(500000);

            unset($data, $response);
        } while ($url);
    }
}
