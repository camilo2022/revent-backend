<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class SiigoInventoryService
{
    private string $baseUrl;
    private string $username;
    private string $accessKey;

    private const STORE_CODES = [
        'ALEGRA'            => 'G2',
        'PUNTO DE VENTA'    => 'P7',
        'MAYALES'           => 'M3',
        'REVENT'            => 'R1',
        'OCEAN MALL'        => 'P2',
        'NUESTRO MONTERIA'  => 'P1',
        'ALAMEDAS'          => 'P3',
        'PORTAL'            => 'M2',
        'PLAZA DEL SOL'     => 'M8',
        'WEB'               => 'G1',
        'CASTELLANA'        => 'G4',
        'UNICO BQ'          => 'G3',
        'CARNAVAL'          => 'P4',
        'GUATAPURI'         => 'M7',
        'VENTURA PLAZA'     => 'M6',
        'FABRICATO'         => 'M5',
        'UNICO CALI'        => 'M1',
        'CARIBE PLAZA 2'    => 'G5',
        'MAYORCA'           => 'M4',
        'GRAN MANZANA'      => 'I3',
        'NUESTRO ATLANTICO' => 'P6',
    ];

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
            'Partner-Id' => 'consultadeFacturas',
        ])->post("{$this->baseUrl}/auth", [
            'username' => $this->username,
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
                'Content-Type' => 'application/json',
                'Authorization' => $token,
                'Partner-Id' => 'consultadeFacturas',
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

            $url = $data['_links']['next']['href'] ?? null;

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
                $wid = $item['warehouse']['id'];
                $date = $invoice['date'];

                if (!isset($purchases[$code][$wid])) {
                    $purchases[$code][$wid] = [
                        'warehouse_name' => $item['warehouse']['name'],
                        'first_date' => $date,
                        'second_date' => null,
                    ];
                    continue;
                }

                $entry = &$purchases[$code][$wid];

                if ($date < $entry['first_date']) {
                    $entry['second_date'] = $entry['first_date'];
                    $entry['first_date'] = $date;
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
                'Content-Type' => 'application/json',
                'Authorization' => $token,
                'Partner-Id' => 'consultadeFacturas',
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

            $url = $data['_links']['next']['href'] ?? null;

            if ($url) usleep(500000);

            unset($data, $response);

        } while ($url);

        return $products;
    }

    private function processProducts(array $results, array &$products): void
    {
        foreach ($results as $product) {
            $code = $product['code'] ?? null;

            if ($code === null) continue;

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

    private function fetchWarehouses(string $token): array
    {
        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'Partner-Id'    => 'consultadeFacturas',
        ])->get("{$this->baseUrl}/v1/warehouses");

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json();
    }

    public function stores(): array
    {
        $token = $this->auth();
        
        return collect($this->fetchWarehouses($token))
            ->map(function ($item) {
                $item = (array) $item;

                $rawName    = trim(preg_replace('/\s+/u', ' ', $item['name']));
                $esTransito = (bool) preg_match('/^TRANSITO\s+/iu', $rawName);
                $name       = $esTransito
                    ? trim(preg_replace('/^TRANSITO\s+/iu', '', $rawName))
                    : $rawName;

                return [
                    'id'     => $item['id'],
                    'name'   => $name,
                    'code'   => self::STORE_CODES[mb_strtoupper($name)] ?? 'S/C',
                    'status' => $esTransito ? 'TRANSITO' : 'APROBADO',
                ];
            })
            ->mapWithKeys(fn ($store) => [
                $store['id'] => [
                    'name'   => $store['name'],
                    'code'   => $store['code'],
                    'status' => $store['status'],
                ],
            ])
            ->toArray();
    }
}
