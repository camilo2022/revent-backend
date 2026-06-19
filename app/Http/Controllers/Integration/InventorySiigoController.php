<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Traits\ApiMessage;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Exports\InventorySiigoExport;

class InventorySiigoController extends Controller
{
    use ApiMessage, ApiResponser;

    private string $siigo_base_url = 'https://api.siigo.com';
    private string $siigo_username = 'reventgestion@gmail.com';
    private string $siigo_access_key = 'NWIwZTQ3ZmUtZjg0ZS00YzU0LWJlZjYtNzliMGIyOWIxMzk2Oj0/aTw2UDlxWFo=';

    public function export_inventory(Request $request)
    {
        try {
            $token_siigo = $this->auth_siigo();
            $inventory = $this->inventory_siigo($token_siigo, [
                'created_start' => $request->input('created_start'),
                'created_end' => $request->input('created_end'),
                'page' => $request->input('page', 1),
                'page_size' => $request->input('page_size', 100)
            ]);
            $processedInventory = $this->process_inventory($inventory);

            return (new InventorySiigoExport($processedInventory))->download('inventario_siigo.xlsx');
        } catch (\Throwable $e) {
            return $this->errorResponse(
                [
                    'message' => $this->getMessage(class_basename($e)),
                    'error' => $e->getMessage()
                ],
                $this->getCode(class_basename($e))
            );
        }
    }

    private function auth_siigo()
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Partner-Id'   => 'consultadeFacturas',
        ])->post("{$this->siigo_base_url}/auth", [
            'username'   => $this->siigo_username,
            'access_key' => $this->siigo_access_key,
        ]);

        if (! $response->successful()) {
            return $response->body();
        }

        return $response->json('access_token');
    }

    private function inventory_siigo(string $token, array $filters = [])
    {
        $allowedFilters = [
            'created_start',
            'created_end',
            'updated_start',
            'updated_end',
            'code',
            'id',
            'page',
            'page_size'
        ];

        $queryParams = array_filter(
            array_intersect_key($filters, array_flip($allowedFilters)),
            fn ($value) => $value !== null && $value !== ''
        );

        $url = "{$this->siigo_base_url}/v1/products?" . http_build_query($queryParams);

        $allInventory = [];

        do {
            $response = Http::withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => $token,
                'Partner-Id'    => 'consultadeFacturas',
            ])->get($url);

            if (! $response->successful()) {
                return $response->body();
            }

            $data = $response->json();

            // Acumular inventario
            if (!empty($data['results'])) {
                $allInventory = array_merge($allInventory, $data['results']);
            }

            // Obtener siguiente página
            $url = $data['_links']['next']['href'] ?? null;

            // Ya no necesitamos query params porque next.href los trae
            $queryParams = [];

        } while ($url);

        return $allInventory;
    }

    private function process_inventory(array $inventory): array
    {
        return collect($inventory)
            ->filter(function ($product) {
                return collect($product['warehouses'] ?? [])
                    ->contains(fn ($w) => $w['quantity'] > 0);
            })
            ->flatMap(function ($product) {

                $parts = explode('*', $product['name'] ?? '');
                $count = count($parts);

                $name = $parts[0] ?? '#N/A';
                $color = $parts[1] ?? '#N/A';
                $category = '#N/A';
                $size = '#N/A';

                if ($count === 3) {
                    $category = '#N/A';
                    $size = $parts[2] ?? '#N/A';
                } elseif ($count === 4) {
                    $category = $parts[2] ?? '#N/A';
                    $size = $parts[3] ?? '#N/A';
                } elseif ($count >= 5) {
                    $category = $parts[$count - 2] ?? '#N/A';
                    $size = $parts[$count - 1] ?? '#N/A';
                } else {
                    $category = '#N/A';
                    $size = '#N/A';
                }

                $base = [
                    'code' => $product['code'],
                    'description' => $product['name'],
                    'name' => $name,
                    'color' => $color,
                    'category' => $category,
                    'size' => $size,
                    'barcode' => $product['additional_fields']['barcode'] ?? null,
                    'brand' => $product['additional_fields']['brand'] ?? null,
                    'model' => $product['additional_fields']['model'] ?? null,
                ];

                return collect($product['warehouses'] ?? [])
                    ->filter(fn ($w) => $w['quantity'] != 0)
                    ->map(function ($warehouse) use ($base) {
                        return array_merge($base, [
                            'warehouse' => $warehouse['name'],
                            'quantity' => $warehouse['quantity'],
                        ]);
                    });
            })
            ->values()
            ->toArray();
    }
}
