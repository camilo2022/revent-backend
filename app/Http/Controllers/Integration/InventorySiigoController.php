<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Exports\InventorySiigoExport;
use Maatwebsite\Excel\Facades\Excel;

class InventorySiigoController extends Controller
{
    private string $siigo_base_url = 'https://api.siigo.com';
    private string $siigo_username = 'reventgestion@gmail.com';
    private string $siigo_access_key = 'NWIwZTQ3ZmUtZjg0ZS00YzU0LWJlZjYtNzliMGIyOWIxMzk2Oj0/aTw2UDlxWFo=';
    private array $purchases = [];

    public function export_inventory(Request $request)
    {
        try {
            ini_set('memory_limit', '-1');
            set_time_limit(0);

            $token = $this->auth_siigo();

            $filters = [
                'created_start' => $request->input('created_start'),
                'created_end' => $request->input('created_end'),
                'updated_start' => $request->input('updated_start'),
                'updated_end' => $request->input('updated_end'),
                'page_size' => $request->input('page_size', 100),
                'type' => $request->input('type', 'Product'),
                'positive' => $request->boolean('positive', true)
            ];
            $positive = $request->boolean('positive', true);
            $name = $positive ? "inventarios_con_ingreso" : "inventarios_por_ingreso";

            $this->purchases_siigo($token, $filters);

            return Excel::download(
                new InventorySiigoExport(
                    $name,
                    $token,
                    $this->purchases,
                    $this->stores(),
                    $filters,
                    $this->siigo_base_url
                ),
                "$name.xlsx"
            );
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    private function auth_siigo(): string
    {
        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Partner-Id'   => 'consultadeFacturas',
        ])->post("{$this->siigo_base_url}/auth", [
            'username'   => $this->siigo_username,
            'access_key' => $this->siigo_access_key,
        ]);

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        return $response->json('access_token');
    }

    private function purchases_siigo(string $token, array $filters = [])
    {
        $allowedFilters = [
            'page',
            'page_size',
            'created_start'
        ];

        $queryParams = array_filter(
            array_intersect_key($filters, array_flip($allowedFilters)),
            fn ($value) => $value !== null && $value !== ''
        );

        $url = "{$this->siigo_base_url}/v1/purchases?" . http_build_query($queryParams);

        do {
            $response = Http::retry(
                5,
                10000
            )->withHeaders([
                'Content-Type'  => 'application/json',
                'Authorization' => $token,
                'Partner-Id'    => 'consultadeFacturas',
            ])->get($url);

            if ($response->status() === 429) {
                sleep(1);

                continue;
            }

            if (! $response->successful()) {
                throw new \Exception($response->body());
            }

            $data = $response->json();

            // Acumular inventario
            if (!empty($data['results'])) {
                $this->process_purchase($data['results']);
            }

            // Obtener siguiente página
            $url = $data['_links']['next']['href'] ?? null;

            if ($url) {
                usleep(500000); // 0.5 segundos
            }

            unset($data, $response);

        } while ($url);
    }

    private function process_purchase(array $results): void
    {
        foreach ($results as $invoice) {
            foreach ($invoice['items'] ?? [] as $item) {
                if (!isset($item['warehouse'])) continue;

                $code = $item['code'];
                $wid  = $item['warehouse']['id'];
                $date = $invoice['date'];

                if (!isset($this->purchases[$code][$wid])) {
                    $this->purchases[$code][$wid] = [
                        'warehouse_name' => $item['warehouse']['name'],
                        'first_date'     => $date,
                        'second_date'    => null,
                    ];
                    continue;
                }

                $entry = &$this->purchases[$code][$wid];

                if ($date < $entry['first_date']) {
                    // Nueva fecha más antigua: la first pasa a second y esta ocupa first
                    $entry['second_date'] = $entry['first_date'];
                    $entry['first_date']  = $date;
                }elseif (
                    $date !== $entry['first_date'] && // ✅ que no sea igual a first
                    (
                        $entry['second_date'] === null ||
                        ($date > $entry['first_date'] && $date < $entry['second_date'])
                    )
                ) {
                    $entry['second_date'] = $date;
                }
            }
        }
    }

    private function stores(): array
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
