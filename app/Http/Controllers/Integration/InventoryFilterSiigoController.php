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

            if (count($partes) < 5) {
                continue;
            }

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

                $colorInfo = $this->obtener_color($colorLimpio);

                $agrupado[$referencia]['colores'][$color] = [
                    'nombre' => ucfirst(strtolower($colorLimpio)),
                    'codigo' => $colorInfo['codigo'],
                    'hex'    => $colorInfo['hex'],
                    'tallas' => [],
                ];
            }

            $agrupado[$referencia]['colores'][$color]['tallas'][$talla] =
                ($agrupado[$referencia]['colores'][$color]['tallas'][$talla] ?? 0)
                + $cantidad;
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

    private function obtener_color(string $nombre): array
    {
        $nombreBuscado = $this->normalizar_color($nombre);

        foreach ($this->colores() as $color) {
            $nombreColor = $this->normalizar_color($color['nombre']);

            if ($nombreBuscado == $nombreColor) {
                return $color;
            }
        }

        // Color no encontrado
        return [
            'nombre' => $nombre,
            'codigo' => null,
            'hex'    => '#000000',
        ];
    }

    private function colores(): array
    {
        return [
            [
                'nombre' => 'BLANCO',
                'codigo' => 10,
                'hex' => '#FFFFFF',
            ],
            [
                'nombre' => 'TRANSPARENTE',
                'codigo' => 13,
                'hex' => '#F2F2F2',
            ],
            [
                'nombre' => 'TIZA',
                'codigo' => 16,
                'hex' => '#F5F5F0',
            ],
            [
                'nombre' => 'BEIGE',
                'codigo' => 17,
                'hex' => '#EDE9E3',
            ],
            [
                'nombre' => 'CREMA',
                'codigo' => 18,
                'hex' => '#E8DCC3',
            ],
            [
                'nombre' => 'PERLA',
                'codigo' => 19,
                'hex' => '#EDE9E3',
            ],
            [
                'nombre' => 'CHAMPAÑA',
                'codigo' => 21,
                'hex' => '#F0DFC4',
            ],
            [
                'nombre' => 'CRUDO',
                'codigo' => 24,
                'hex' => '#E8DCC3',
            ],
            [
                'nombre' => 'VAINILLA',
                'codigo' => 27,
                'hex' => '#EED9AE',
            ],
            [
                'nombre' => 'NUDE',
                'codigo' => 30,
                'hex' => '#E3C9A6',
            ],
            [
                'nombre' => 'ARENA',
                'codigo' => 33,
                'hex' => '#D9C199',
            ],
            [
                'nombre' => 'PLATA',
                'codigo' => 36,
                'hex' => '#C0C0C0',
            ],
            [
                'nombre' => 'ORO ROSA',
                'codigo' => 39,
                'hex' => '#E0BFB8',
            ],
            [
                'nombre' => 'DORADO',
                'codigo' => 42,
                'hex' => '#D4AF37',
            ],
            [
                'nombre' => 'MIEL',
                'codigo' => 44,
                'hex' => '#C68E42',
            ],
            [
                'nombre' => 'AREQUIPE',
                'codigo' => 45,
                'hex' => '#B08D57',
            ],
            [
                'nombre' => 'CAMEL',
                'codigo' => 47,
                'hex' => '#C19A6B',
            ],
            [
                'nombre' => 'KHAKI',
                'codigo' => 50,
                'hex' => '#C3B091',
            ],
            [
                'nombre' => 'AMARETO',
                'codigo' => 53,
                'hex' => '#B4802F',
            ],
            [
                'nombre' => 'YUTE',
                'codigo' => 56,
                'hex' => '#B08D57',
            ],
            [
                'nombre' => 'CARAMELO',
                'codigo' => 59,
                'hex' => '#A9682B',
            ],
            [
                'nombre' => 'ANIMAL CARAMELO',
                'codigo' => 62,
                'hex' => '#9C6B3E',
            ],
            [
                'nombre' => 'ANIMAL PRINT',
                'codigo' => 65,
                'hex' => '#8B6B4A',
            ],
            [
                'nombre' => 'VAQUITA',
                'codigo' => 67,
                'hex' => '#5A5250',
            ],
            [
                'nombre' => 'OCRE',
                'codigo' => 70,
                'hex' => '#9C7A26',
            ],
            [
                'nombre' => 'TAUPE',
                'codigo' => 73,
                'hex' => '#7A6A5D',
            ],
            [
                'nombre' => 'BROWN',
                'codigo' => 76,
                'hex' => '#6B4423',
            ],
            [
                'nombre' => 'CAFE',
                'codigo' => 79,
                'hex' => '#4B3621',
            ],
            [
                'nombre' => 'BISTRO',
                'codigo' => 82,
                'hex' => '#4A3B2A',
            ],
            [
                'nombre' => 'MOKA',
                'codigo' => 85,
                'hex' => '#3B2A1E',
            ],
            [
                'nombre' => 'ROJO',
                'codigo' => 88,
                'hex' => '#B22222',
            ],
            [
                'nombre' => 'VINO',
                'codigo' => 90,
                'hex' => '#5B1A1A',
            ],
            [
                'nombre' => 'GRIS',
                'codigo' => 92,
                'hex' => '#666666',
            ],
            [
                'nombre' => 'ANIMAL NEGRO',
                'codigo' => 93,
                'hex' => '#2B2523',
            ],
            [
                'nombre' => 'OSCURO',
                'codigo' => 96,
                'hex' => '#2E2E2E',
            ],
            [
                'nombre' => 'NEGRO',
                'codigo' => 99,
                'hex' => '#000000',
            ],
        ];
    }

    private function name_category(string $categoria): string
    {
        $mapa = [
            'PL' => 'PLANA',
            'BA' => 'BALETA',
            'TE' => 'TENIS',
            'BO' => 'BOLSO',
            'TC' => 'TACON',
            'NI' => 'NIÑA',
            'PT' => 'PLATAFORMA',
            'MO' => 'MOCASIN',
            'MC' => 'MOCASIN CHAROL',
            'ST' => 'STILETTO',
            'KH' => 'KITTEN',
            'CH' => 'CHUNKY',
            'CA' => 'CANOA',
            'CF' => 'CONFORT',
            'BB' => 'BABUCHA',
            'BT' => 'BOTA',
            'BN' => 'BOTIN',
            'ES' => 'ESPADRILA',
            'SE' => 'SENA',
        ];

        $categoria = strtoupper(trim($categoria));

        return $mapa[$categoria] ?? $categoria;
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

    private function normalizar_color(string $color): string
    {
        $color = strtoupper(trim($color));

        $color = strtr($color, [
            'Á' => 'A',
            'É' => 'E',
            'Í' => 'I',
            'Ó' => 'O',
            'Ú' => 'U',
            'Ü' => 'U',
            'Ñ' => 'N',
        ]);

        $color = preg_replace('/[^A-Z0-9]/', '', $color);

        return $color;
    }
}
