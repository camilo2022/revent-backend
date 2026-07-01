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

    public function __construct($name, $token, $purchases, $stores, $filters, $baseUrl)
    {
        $this->name = $name;
        $this->token = $token;
        $this->purchases = $purchases;
        $this->stores = $stores;
        $this->filters = $filters;
        $this->baseUrl = $baseUrl;
    }

    public function headings(): array
    {
        return [
            'CUENTA DE GRUPO',
            'CODIGO',
            'DESCRIPCION',
            'NOMBRE',
            'COLOR',
            'CATEGORIA',
            'TALLA',
            'CODIGO_BARRAS',
            'MARCA',
            'MODELO',
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
            'created_start',
            'created_end',
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
            $response = Http::retry(
                5,
                10000
            )->withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $this->token,
                'Partner-Id' => 'consultadeFacturas',
            ])->get($url);

            // Siigo puede seguir devolviendo 429 incluso después del retry
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

                if (in_array($product['account_group']['id'] ?? null, [1190, 1338], true)) {
                    continue;
                }

                $operator = ($this->filters['positive'] ?? true) ? '>' : '<';
                foreach (collect($product['warehouses'] ?? [])->where('quantity', $operator, 0)->whereNotNull('id')->whereNotIn('id', [34, 35, 36, 44, 45, 47, -1]) as $warehouse) {
                    $firstDate = $this->purchases[$product['code']][$warehouse['id']]['first_date'] ?? null;

                    $secondDate = $this->purchases[$product['code']][$warehouse['id']]['second_date'] ?? null;

                    $diffDays = ($firstDate && $secondDate) ? (new \DateTime($secondDate))->diff(new \DateTime($firstDate))->days : null;

                    yield [
                        'CUENTA DE GRUPO' => $product['account_group']['name'] ?? null,
                        'CODIGO' => $product['code'] ?? null,
                        'DESCRIPCION' => $product['name'] ?? null,
                        'NOMBRE' => $name,
                        'COLOR' => $color,
                        'CATEGORIA' => $category,
                        'TALLA' => $size,
                        'CODIGO_BARRAS' => $product['additional_fields']['barcode'] ?? null,
                        'MARCA' => $product['additional_fields']['brand'] ?? null,
                        'MODELO' => $product['additional_fields']['model'] ?? null,
                        'BODEGA' => ($this->stores[($warehouse['id'] ?? null)]['code'] ?? '#N/A') . ' - ' . ($warehouse['name'] ?? null),
                        'CANTIDAD' => $warehouse['quantity'] ?? 0,
                        'FECHA' => $firstDate,
                        'SEGUNDA_FECHA' => $secondDate,
                        'DIFERENCIA' => $diffDays,
                    ];
                }
            }

            $url = /*$data['_links']['next']['href'] ??*/ null;

            // pequeña pausa entre páginas para evitar rate limit
            if ($url) {
                usleep(500000); // 0.5 segundos
            }

            unset($data, $response);
        } while ($url);
    }
}
