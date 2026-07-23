<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SiigoInventoryService
{
    private string $baseUrl;
    private string $username;
    private string $accessKey;

    public function __construct()
    {
        $this->baseUrl  = config('services.siigo.base_url');
        $this->username = config('services.siigo.username');
        $this->accessKey = config('services.siigo.access_key');
    }

    public function auth(): string
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Partner-Id'   => 'consultadeFacturas',
        ])->post("{$this->baseUrl}/auth", [
            'username'   => $this->username,
            'access_key' => $this->accessKey,
        ]);

        if (!$response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json('access_token');
    }

    public function getPurchases(string $token, array $filters = []): array
    {
        $purchases = [];
        $allowedFilters = ['page', 'page_size'];

        $queryParams = array_filter(
            array_intersect_key($filters, array_flip($allowedFilters)),
            fn($v) => $v !== null && $v !== ''
        );

        $url = "{$this->baseUrl}/v1/purchases?" . http_build_query($queryParams);

        do {
            $response = Http::retry(5, 10000)->withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => $token,
                'Partner-Id'    => 'consultadeFacturas',
            ])->get($url);

            if ($response->status() === 429) {
                sleep(1);
                continue;
            }

            if (!$response->successful()) {
                throw new \Exception($response->body());
            }

            $data = $response->json();

            if (!empty($data['results'])) {
                $this->processPurchase($data['results'], $purchases);
            }

            $url = /*$data['_links']['next']['href'] ??*/ null;

            if ($url) usleep(500000);

            unset($data, $response);

        } while ($url);

        return $purchases;
    }

    private function processPurchase(array $results, array &$purchases): void
    {
        foreach ($results as $invoice) {
            foreach ($invoice['items'] ?? [] as $item) {
                if (!isset($item['warehouse'])) continue;

                $code = $item['code'];
                $wid  = $item['warehouse']['id'];
                $date = $invoice['date'];

                if (!isset($purchases[$code][$wid])) {
                    $purchases[$code][$wid] = [
                        'warehouse_name' => $item['warehouse']['name'],
                        'first_date'     => $date,
                        'second_date'    => null,
                    ];
                    continue;
                }

                $entry = &$purchases[$code][$wid];

                if ($date < $entry['first_date']) {
                    $entry['second_date'] = $entry['first_date'];
                    $entry['first_date']  = $date;
                } elseif (
                    $date !== $entry['first_date'] &&
                    ($entry['second_date'] === null || ($date > $entry['first_date'] && $date < $entry['second_date']))
                ) {
                    $entry['second_date'] = $date;
                }
            }
        }
    }

    public function getProducts(string $token, array $filters = []): array
    {
        $products = [];
        $allowedFilters = ['page', 'page_size'];

        $queryParams = array_filter(
            array_intersect_key($filters, array_flip($allowedFilters)),
            fn($v) => $v !== null && $v !== ''
        );

        $url = "{$this->baseUrl}/v1/products?" . http_build_query($queryParams);

        do {
            $response = Http::retry(5, 10000)->withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => $token,
                'Partner-Id'    => 'consultadeFacturas',
            ])->get($url);

            if ($response->status() === 429) {
                sleep(1);
                continue;
            }

            if (!$response->successful()) {
                throw new \Exception($response->body());
            }

            $data = $response->json();

            if (!empty($data['results'])) {
                $this->processProducts($data['results'], $products);
            }

            $url = /*$data['_links']['next']['href'] ??*/ null;

            if ($url) usleep(500000);

            unset($data, $response);

        } while ($url);

        return $products;
    }

    private function processProducts(array $results, array &$products): void
    {
        foreach ($results as $product) {
            $code = $product['code'] ?? null;

            if ($code === null) {
                continue;
            }

            $products[$code] = [
                'id' => $product['id'] ?? null,
                'code' => $product['code'] ?? null,
                'name' => $product['name'] ?? null,
                'account_group' => $product['account_group']['name'] ?? null,
                'account_group_id' => $product['account_group']['id'] ?? null,
                'reference' => $product['reference'] ?? null,
                'barcode' => $product['additional_fields']['barcode'] ?? null,
                'brand' => $product['additional_fields']['brand'] ?? null,
                'model' => $product['additional_fields']['model'] ?? null,
                'tariff' => $product['additional_fields']['tariff'] ?? null,
                'unit_label' => $product['unit_label'] ?? null,
                'active' => $product['active'] ?? null,
                'stock_control' => $product['stock_control'] ?? null,
                'available_quantity'=> $product['available_quantity'] ?? 0,
                'warehouses' => $product['warehouses'] ?? [],
            ];
        }
    }

    public function stores(): array
    {
        return [
            3 => [
                'name' => 'ALEGRA',
                'code' => 'G2',
            ],
            4 => [
                'name' => 'PUNTO DE VENTA',
                'code' => 'P7',
            ],
            9 => [
                'name' => 'MAYALES',
                'code' => 'M3',
            ],
            15 => [
                'name' => 'REVENT',
                'code' => 'R1',
            ],
            17 => [
                'name' => 'OCEAN MALL',
                'code' => 'P2',
            ],
            19 => [
                'name' => 'NUESTRO',
                'code' => 'P1',
            ],
            22 => [
                'name' => 'ALAMEDAS',
                'code' => 'P3',
            ],
            24 => [
                'name' => 'PORTAL',
                'code' => 'M2',
            ],
            28 => [
                'name' => 'PLAZA DEL SOL',
                'code' => 'M8',
            ],
            31 => [
                'name' => 'WEB',
                'code' => 'G1',
            ],
            32 => [
                'name' => 'CASTELLANA',
                'code' => 'G4',
            ],
            33 => [
                'name' => 'UNICO BQ',
                'code' => 'G3',
            ],
            37 => [
                'name' => 'CARNAVAL',
                'code' => 'P4',
            ],
            46 => [
                'name' => 'GUATAPURI',
                'code' => 'M7',
            ],
            49 => [
                'name' => 'VENTURA PLAZA',
                'code' => 'M6',
            ],
            50 => [
                'name' => 'FABRICATO',
                'code' => 'M5',
            ],
            51 => [
                'name' => 'UNICO CALI',
                'code' => 'M1',
            ],
            56 => [
                'name' => 'CARIBE PLAZA 2',
                'code' => 'G5',
            ],
            57 => [
                'name' => 'MAYORCA',
                'code' => 'M4',
            ],
            58 => [
                'name' => 'GRAN MANZANA',
                'code' => 'I3',
            ],
            59 => [
                'name' => 'NUESTRO ATLANTICO',
                'code' => 'P6',
            ],
        ];
    }
}
