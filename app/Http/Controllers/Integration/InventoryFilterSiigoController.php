<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\SiigoInventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class InventoryFilterSiigoController extends Controller
{
    private const DISK = 'public';
    private const BASE_PATH = 'products';
    private string $siigo_base_url = 'https://api.siigo.com';

    public function inventory_filter(Request $request)
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();
        $warehouses = $this->warehouses($token);

        $productos = [
            [
                'id' => 1,
                'referencia' => 'RUN-001',
                'nombre' => 'Tenis Running Air',
                'categoria' => 'Tenis',
                'genero' => 'Hombre',
                'imagen' => 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=500&q=80',

                'colores' => [
                    [
                        'nombre' => 'Negro',
                        'hex' => '#171717',
                        'tallas' => [
                            '36' => 2,
                            '37' => 5,
                            '38' => 8,
                            '39' => 6,
                            '40' => 4,
                            '41' => 1,
                            '42' => 0,
                        ],
                    ],
                    [
                        'nombre' => 'Blanco',
                        'hex' => '#f5f5f5',
                        'tallas' => [
                            '36' => 0,
                            '37' => 4,
                            '38' => 3,
                            '39' => 7,
                            '40' => 5,
                            '41' => 2,
                            '42' => 1,
                        ],
                    ],
                    [
                        'nombre' => 'Azul',
                        'hex' => '#315a85',
                        'tallas' => [
                            '36' => 1,
                            '37' => 0,
                            '38' => 4,
                            '39' => 0,
                            '40' => 3,
                            '41' => 2,
                            '42' => 1,
                        ],
                    ],
                ],

                'tiendas' => [
                    'Centro' => 12,
                    'Unicentro' => 8,
                    'Ventura' => 5,
                    'Norte' => 2,
                ],
            ],

            [
                'id' => 2,
                'referencia' => 'RUN-002',
                'nombre' => 'Tenis Runner Pro',
                'categoria' => 'Tenis',
                'genero' => 'Mujer',
                'imagen' => 'https://images.unsplash.com/photo-1525966222134-fcfa99b8ae77?auto=format&fit=crop&w=500&q=80',

                'colores' => [
                    [
                        'nombre' => 'Negro',
                        'hex' => '#171717',
                        'tallas' => [
                            '36' => 3,
                            '37' => 5,
                            '38' => 2,
                            '39' => 0,
                            '40' => 4,
                            '41' => 1,
                            '42' => 0,
                        ],
                    ],
                    [
                        'nombre' => 'Rosa',
                        'hex' => '#dca7b5',
                        'tallas' => [
                            '36' => 4,
                            '37' => 6,
                            '38' => 5,
                            '39' => 3,
                            '40' => 1,
                            '41' => 0,
                            '42' => 0,
                        ],
                    ],
                ],

                'tiendas' => [
                    'Centro' => 4,
                    'Unicentro' => 10,
                    'Ventura' => 3,
                    'Norte' => 0,
                ],
            ],

            [
                'id' => 3,
                'referencia' => 'CAS-001',
                'nombre' => 'Zapato Casual Classic',
                'categoria' => 'Casual',
                'genero' => 'Hombre',
                'imagen' => 'https://images.unsplash.com/photo-1614252235316-8c857d1a9c5c?auto=format&fit=crop&w=500&q=80',

                'colores' => [
                    [
                        'nombre' => 'Negro',
                        'hex' => '#151515',
                        'tallas' => [
                            '36' => 0,
                            '37' => 2,
                            '38' => 5,
                            '39' => 8,
                            '40' => 7,
                            '41' => 3,
                            '42' => 1,
                        ],
                    ],
                    [
                        'nombre' => 'Café',
                        'hex' => '#76523c',
                        'tallas' => [
                            '36' => 1,
                            '37' => 2,
                            '38' => 3,
                            '39' => 0,
                            '40' => 2,
                            '41' => 1,
                            '42' => 0,
                        ],
                    ],
                ],

                'tiendas' => [
                    'Centro' => 14,
                    'Unicentro' => 4,
                    'Ventura' => 2,
                    'Norte' => 6,
                ],
            ],

            [
                'id' => 4,
                'referencia' => 'CAS-002',
                'nombre' => 'Zapato Oxford Premium',
                'categoria' => 'Casual',
                'genero' => 'Hombre',
                'imagen' => 'https://images.unsplash.com/photo-1533867617858-e7b97e060509?auto=format&fit=crop&w=500&q=80',

                'colores' => [
                    [
                        'nombre' => 'Negro',
                        'hex' => '#111111',
                        'tallas' => [
                            '36' => 0,
                            '37' => 0,
                            '38' => 1,
                            '39' => 0,
                            '40' => 2,
                            '41' => 0,
                            '42' => 0,
                        ],
                    ],
                ],

                'tiendas' => [
                    'Centro' => 0,
                    'Unicentro' => 1,
                    'Ventura' => 0,
                    'Norte' => 0,
                ],
            ],

            [
                'id' => 5,
                'referencia' => 'SAN-001',
                'nombre' => 'Sandalia Comfort',
                'categoria' => 'Sandalias',
                'genero' => 'Mujer',
                'imagen' => 'https://images.unsplash.com/photo-1603487742131-4160ec999306?auto=format&fit=crop&w=500&q=80',

                'colores' => [
                    [
                        'nombre' => 'Beige',
                        'hex' => '#d8c4a8',
                        'tallas' => [
                            '35' => 4,
                            '36' => 7,
                            '37' => 8,
                            '38' => 5,
                            '39' => 3,
                            '40' => 1,
                            '41' => 0,
                        ],
                    ],
                    [
                        'nombre' => 'Negro',
                        'hex' => '#161616',
                        'tallas' => [
                            '35' => 2,
                            '36' => 5,
                            '37' => 3,
                            '38' => 0,
                            '39' => 4,
                            '40' => 2,
                            '41' => 1,
                        ],
                    ],
                ],

                'tiendas' => [
                    'Centro' => 8,
                    'Unicentro' => 7,
                    'Ventura' => 4,
                    'Norte' => 3,
                ],
            ],

            [
                'id' => 6,
                'referencia' => 'SAN-002',
                'nombre' => 'Sandalia Urban',
                'categoria' => 'Sandalias',
                'genero' => 'Mujer',
                'imagen' => 'https://images.unsplash.com/photo-1562273138-f46be4ebdf33?auto=format&fit=crop&w=500&q=80',

                'colores' => [
                    [
                        'nombre' => 'Negro',
                        'hex' => '#121212',
                        'tallas' => [
                            '35' => 0,
                            '36' => 0,
                            '37' => 2,
                            '38' => 4,
                            '39' => 0,
                            '40' => 3,
                            '41' => 1,
                        ],
                    ],
                    [
                        'nombre' => 'Rojo',
                        'hex' => '#a63b32',
                        'tallas' => [
                            '35' => 1,
                            '36' => 2,
                            '37' => 3,
                            '38' => 5,
                            '39' => 2,
                            '40' => 1,
                            '41' => 0,
                        ],
                    ],
                ],

                'tiendas' => [
                    'Centro' => 3,
                    'Unicentro' => 4,
                    'Ventura' => 5,
                    'Norte' => 0,
                ],
            ],

            [
                'id' => 7,
                'referencia' => 'BOT-001',
                'nombre' => 'Bota Classic Leather',
                'categoria' => 'Botas',
                'genero' => 'Mujer',
                'imagen' => 'https://images.unsplash.com/photo-1608256246200-53e635b5b65f?auto=format&fit=crop&w=500&q=80',

                'colores' => [
                    [
                        'nombre' => 'Café',
                        'hex' => '#70452e',
                        'tallas' => [
                            '35' => 1,
                            '36' => 3,
                            '37' => 5,
                            '38' => 4,
                            '39' => 2,
                            '40' => 0,
                            '41' => 0,
                        ],
                    ],
                    [
                        'nombre' => 'Negro',
                        'hex' => '#171717',
                        'tallas' => [
                            '35' => 0,
                            '36' => 2,
                            '37' => 4,
                            '38' => 5,
                            '39' => 0,
                            '40' => 1,
                            '41' => 0,
                        ],
                    ],
                ],

                'tiendas' => [
                    'Centro' => 4,
                    'Unicentro' => 6,
                    'Ventura' => 2,
                    'Norte' => 1,
                ],
            ],

            [
                'id' => 8,
                'referencia' => 'DEP-001',
                'nombre' => 'Tenis Deportivo Max',
                'categoria' => 'Deportivo',
                'genero' => 'Unisex',
                'imagen' => 'https://images.unsplash.com/photo-1552346154-21d32810aba3?auto=format&fit=crop&w=500&q=80',

                'colores' => [
                    [
                        'nombre' => 'Negro',
                        'hex' => '#151515',
                        'tallas' => [
                            '36' => 5,
                            '37' => 7,
                            '38' => 9,
                            '39' => 6,
                            '40' => 8,
                            '41' => 4,
                            '42' => 2,
                        ],
                    ],
                    [
                        'nombre' => 'Azul',
                        'hex' => '#345c88',
                        'tallas' => [
                            '36' => 3,
                            '37' => 4,
                            '38' => 5,
                            '39' => 2,
                            '40' => 3,
                            '41' => 1,
                            '42' => 0,
                        ],
                    ],
                ],

                'tiendas' => [
                    'Centro' => 15,
                    'Unicentro' => 12,
                    'Ventura' => 8,
                    'Norte' => 5,
                ],
            ],
        ];

        return view('integration.inventory', compact('warehouses'));
    }

    public function inventory_filter_search(Request $request)
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $filters = [
            [
                'Field'        => '_vProduct',
                'FilterType'   => 6,
                'OperatorType' => 0,
                'Value'        => [],
                'ValueUI'      => '',
                'Source'       => '2',
            ],
            [
                'Field'        => 'WarehouseFilter',
                'FilterType'   => 2,
                'OperatorType' => 0,
                'Value'        => [$request->input('warehouse_id')],
                'ValueUI'      => '',
                'Source'       => '',
            ],
            [
                'Field'        => '_vCutoffDate',
                'FilterType'   => 5,
                'OperatorType' => 0,
                'Value'        => [now()->toISOString()],
                'ValueUI'      => now()->format('n/j/Y'),
                'Source'       => 'Account',
            ],
            [
                'Field'        => 'ProductBalanceFilter',
                'FilterType'   => 7,
                'OperatorType' => 0,
                'Value'        => ['0'],
                'ValueUI'      => 'Con saldo',
                'Source'       => 'ProductBalancesEnum',
            ],
            [
                'Field'        => 'product',
                'FilterType'   => 2,
                'OperatorType' => 0,
                'Value'        => [-1],
                'ValueUI'      => '',
                'Source'       => '',
            ],
        ];
        $body = [
            'FilterCriterias'    => json_encode($filters, JSON_UNESCAPED_UNICODE),
            'GetTotalCount'      => false,
            'GridOrderCriteria'  => null,
            'Id'                 => 5443,
            'Params'             => json_encode(['TabID' => '1617', 'pTabID' => '1445', 'rReport' => '1']),
            'Skip'               => 0,
            'Sort'               => ' ',
            'Take'               => 0,
        ];

        $response = Http::withToken($token)
            ->acceptJson()
            ->post('https://services.siigo.com/document/api/v1/reports/getreport', $body);

        if (!$response->successful()) {
            throw new \Exception(
                'Error consultando inventario: ' . $response->body()
            );
        }

        $data = $response->json('data.Value.Table');

        return response()->json([
            'productos' => $this->map_products($data),
        ]);
    }

    private function map_products(array $filas): array
    {
        $agrupado = [];

        foreach ($filas as $fila) {
            $description = $fila['Description'] ?? '';
            $partes = explode('-', $description);
            if (count($partes) < 5) continue;

            $referencia = $partes[0];
            $color      = $partes[1];
            $categoria  = $partes[3];
            $talla      = end($partes);

            $cantidad = (int) ($fila['QuantityBalance'] ?? 0);

            if (!isset($agrupado[$referencia])) {
                $agrupado[$referencia] = [
                    'id'         => $referencia,
                    'referencia' => $referencia,
                    'nombre'     => ucfirst(strtolower($referencia)),
                    'categoria'  => ucfirst(strtolower($this->name_category($categoria))),
                    'genero'     => '',
                    'colores'    => [],
                ];
            }

            if (!isset($agrupado[$referencia]['colores'][$color])) {
                $colorLimpio = $this->clean_text($color);
                $agrupado[$referencia]['colores'][$color] = [
                    'nombre' => ucfirst(strtolower($colorLimpio)),
                    'hex'    => $this->hex_color($colorLimpio),
                    'tallas' => [],
                ];
            }

            $agrupado[$referencia]['colores'][$color]['tallas'][$talla] =
                ($agrupado[$referencia]['colores'][$color]['tallas'][$talla] ?? 0) + $cantidad;
        }

        $productos = [];

        foreach ($agrupado as $referencia => $producto) {
            $imagenes = $this->images($referencia);

            $producto['imagen'] = $imagenes->first()['url'] ?? null;

            $producto['colores'] = collect($producto['colores'])
                ->map(function ($color) use ($imagenes) {
                    $fotosColor = $imagenes
                        ->filter(fn ($img) => str_contains(strtolower($img['name']), strtolower($color['nombre'])))
                        ->pluck('url')
                        ->values();

                    $color['fotos'] = $fotosColor->isNotEmpty()
                        ? $fotosColor->toArray()
                        : $imagenes->pluck('url')->toArray();

                    ksort($color['tallas'], SORT_NATURAL);

                    return $color;
                })
                ->values()
                ->toArray();

            $productos[] = $producto;
        }

        return array_values($productos);
    }

    private function hex_color(string $color): string
    {
        $mapa = [
            'NEGRO'    => '#171717',
            'BLANCO'   => '#f5f5f5',
            'AZUL'     => '#315a85',
            'ROJO'     => '#a63b32',
            'ROSA'     => '#dca7b5',
            'CAFE'     => '#76523c',
            'BEIGE'    => '#d8c4a8',
            'YUTE'     => '#c9a876',
            'VERDE'    => '#2f6b3f',
            'GRIS'     => '#9ca3af',
            'AMARILLO' => '#e0b93c',
            'VINO'     => '#5c1a2b',
            'PERLA'    => '#e8e4de',
            'PLATA'    => '#c0c0c0',
            'ORO'      => '#d4af37',
            'TALCO'    => '#f1ede4',
        ];

        return $mapa[strtoupper($color)] ?? '#9ca3af';
    }

    private function name_category(string $categoria): string
    {
        $mapa = [
            'PL' => 'PLANA',
            'BA' => 'BALETAS',
            'BO' => 'BOLSO'
        ];

        return $mapa[strtoupper($categoria)] ?? $categoria;
    }

    private function images(string $referencia)
    {
        $path = self::BASE_PATH . "/{$referencia}";

        if (!Storage::disk(self::DISK)->exists($path)) {
            return collect();
        }

        return collect(Storage::disk(self::DISK)->files($path))
            ->map(fn ($file) => [
                'name' => basename($file),
                'url'  => Storage::disk(self::DISK)->url($file),
            ])
            ->values();
    }

    private function warehouses(string $token)
    {
        $response = Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Authorization' => $token,
            'Partner-Id' => 'consultadeFacturas',
        ])->get("{$this->siigo_base_url}/v1/warehouses");

        if (! $response->successful()) {
            throw new \Exception($response->body());
        }

        $data = $response->json();

        return $this->processWarehouses($data);
    }

    private function processWarehouses(array $data): array
    {
        $items = collect($data)->map(function ($item) {
            return (array) $item;
        });

        $nombresConTransito = $items
            ->filter(fn ($item) => str_starts_with(trim($item['name']), 'TRANSITO '))
            ->map(fn ($item) => trim(str_replace('TRANSITO ', '', $item['name'])))
            ->map(fn ($nombre) => mb_strtoupper(trim($nombre)))
            ->unique()
            ->values();

        $result = $items
            ->filter(fn ($item) => ! str_starts_with(trim($item['name']), 'TRANSITO '))
            ->map(function ($item) use ($nombresConTransito) {
                $nombreNormalizado = mb_strtoupper(trim($item['name']));

                $item['transito'] = $nombresConTransito->contains($nombreNormalizado);

                return $item;
            })
            ->values()
            ->toArray();

        array_unshift($result, [
            'id' => -1,
            'name' => 'SIN ASIGNAR',
            'transito' => false,
        ]);

        return $result;
    }

    private function clean_text(string $texto): string
    {
        $texto = strtr($texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);

        // Cualquier otro caracter que no sea letra/número/espacio se elimina.
        $texto = preg_replace('/[^A-Za-z0-9 ]/', '', $texto);

        return trim($texto);
    }
}
