<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\SiigoInventoryService;
use App\Mail\InventroyFilterAccessLink;
use App\Services\SiigoProductsCache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class InventoryFilterSiigoController extends Controller
{
    private const DISK = 'public';
    private const BASE_PATH = 'products';
    private string $siigo_base_url = 'https://api.siigo.com';

    public function inventory_filter_access()
    {
        return view('integration.inventory_filter_access');
    }

    public function inventory_filter_send_access_link(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
        ]);

        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $email = strtolower(trim($request->input('email')));

        $sellers = $this->sellers($token);

        if (!in_array($email, $sellers, true)) {
            return back()
                ->withErrors(['email' => 'Este correo no tiene autorización para acceder a filtro de inventarios.'])
                ->withInput();
        }

        try {
            $url = URL::temporarySignedRoute('siigo.inventory_filter', now()->addHours(24));

            Mail::to($email)->send(new InventroyFilterAccessLink($url));
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withErrors(['email' => 'No fue posible enviar el enlace de acceso. Intenta nuevamente en unos minutos.'])
                ->withInput();
        }

        return back()->with('status', 'Te enviamos el enlace de acceso a ' . $email . '. Revisa tu bandeja de entrada (y spam).');
    }

    public function inventory_filter(Request $request)
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();
        $warehouses = $this->warehouses($token);
        $color_groups = $this->color_groups();

        return view('integration.inventory_filter', compact('warehouses', 'color_groups'));
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

    /** Devuelve una página de fotos para el carrusel, no toda la galería. */
    public function inventory_filter_images(Request $request)
    {
        $validated = $request->validate([
            'referencia' => ['required', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:12'],
        ]);

        $referencia = $this->clean_text(strtoupper(trim($validated['referencia'])));

        $page = (int) ($validated['page'] ?? 1);
        $perPage = (int) ($validated['per_page'] ?? 10);
        $images = $this->images($referencia);

        $total = $images->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        return response()->json([
            'images' => $images->forPage($page, $perPage)->values(),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'has_previous' => $page > 1,
            'has_next' => $page < $lastPage,
        ])->header('Cache-Control', 'private, max-age=30');
    }

    private function map_products(array $filas): array
    {
        $productsByCode = app(SiigoProductsCache::class)->keyedByProductId();

        $agrupado = [];

        foreach ($filas as $fila) {
            $description = $fila['Description'] ?? '';
            $partes = explode('-', $description);

            if (count($partes) < 5 && count($partes) < 4) {
                continue;
            }

            $referencia = $partes[0];
            $color      = $partes[1];
            $talla      = $partes[count($partes) - 1];

            $cantidad = (int) ($fila['QuantityBalance'] ?? 0);

            if (!isset($agrupado[$referencia])) {
                $agrupado[$referencia] = [
                    'id'         => $referencia,
                    'referencia' => $referencia,
                    'nombre'     => ucfirst(strtolower($referencia)),
                    'categoria'  => '',
                    'genero'     => '',
                    'colores'    => [],
                ];
            }

            if ($agrupado[$referencia]['categoria'] === '') {
                $producto = $productsByCode->get($fila['productcode'] ?? null);

                if ($producto) {
                    $agrupado[$referencia]['categoria'] = $producto['model'] ?? '';
                }
            }

            if (!isset($agrupado[$referencia]['colores'][$color])) {

                $colorLimpio = $this->clean_text($color);

                $colorInfo = $this->obtener_color($colorLimpio);

                $agrupado[$referencia]['colores'][$color] = [
                    'nombre'         => ucfirst(strtolower($colorLimpio)),
                    'codigo'         => $colorInfo['codigo'],
                    'hex'            => $colorInfo['hex'],
                    'macrocategoria' => $colorInfo['macrocategoria'] ?? 'OTROS',
                    'tallas'         => [],
                ];
            }

            $agrupado[$referencia]['colores'][$color]['tallas'][$talla] =
                ($agrupado[$referencia]['colores'][$color]['tallas'][$talla] ?? 0)
                + $cantidad;
        }

        $productos = [];

        foreach ($agrupado as $referencia => $producto) {
            $imagenes = $this->images($this->clean_text(strtoupper($referencia)));
            $producto['imagen'] = $imagenes->first()['url'] ?? null;

            $producto['colores'] = collect($producto['colores'])
                ->map(function ($color) use ($imagenes) {
                    $fotoColor = $imagenes->first(fn ($img) => str_contains(
                        mb_strtolower($img['name']), mb_strtolower($color['nombre'])
                    ));
                    $previewUrl = ($fotoColor['url'] ?? null) ?: ($imagenes->first()['url'] ?? null);

                    // Solo se entrega una imagen de vista previa por color.
                    // El carrusel consulta el resto mediante el endpoint paginado.
                    $color['imagen'] = $previewUrl;
                    $color['fotos'] = $previewUrl ? [$previewUrl] : [];

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

        // Color no encontrado en el catálogo
        return [
            'nombre'         => $nombre,
            'codigo'         => null,
            'hex'            => '#000000',
            'macrocategoria' => 'OTROS',
        ];
    }

    private function colores(): array
    {
        return [
            // ANIMAL PRINT
            ['nombre' => 'ANIMAL CARAMELO', 'codigo' => 62, 'hex' => '#9C6B3E', 'macrocategoria' => 'ANIMAL PRINT'],
            ['nombre' => 'ANIMAL PRINT',    'codigo' => 65, 'hex' => '#8B6B4A', 'macrocategoria' => 'ANIMAL PRINT'],
            ['nombre' => 'VAQUITA',         'codigo' => 67, 'hex' => '#5A5250', 'macrocategoria' => 'ANIMAL PRINT'],
            ['nombre' => 'ANIMAL NEGRO',    'codigo' => 93, 'hex' => '#2B2523', 'macrocategoria' => 'ANIMAL PRINT'],

            // BEIGE / CREMA
            ['nombre' => 'BEIGE',        'codigo' => 17, 'hex' => '#EDE9E3', 'macrocategoria' => 'BEIGE / CREMA'],
            ['nombre' => 'CREMA',        'codigo' => 18, 'hex' => '#E8DCC3', 'macrocategoria' => 'BEIGE / CREMA'],
            ['nombre' => 'PERLA',        'codigo' => 19, 'hex' => '#EDE9E3', 'macrocategoria' => 'BEIGE / CREMA'],
            ['nombre' => 'CRUDO',        'codigo' => 24, 'hex' => '#E8DCC3', 'macrocategoria' => 'BEIGE / CREMA'],
            ['nombre' => 'BLANCO',       'codigo' => 10, 'hex' => '#FFFFFF', 'macrocategoria' => 'BEIGE / CREMA'],
            ['nombre' => 'TRANSPARENTE', 'codigo' => 13, 'hex' => '#F2F2F2', 'macrocategoria' => 'BEIGE / CREMA'],
            ['nombre' => 'TIZA',         'codigo' => 16, 'hex' => '#F5F5F0', 'macrocategoria' => 'BEIGE / CREMA'],
            ['nombre' => 'PLATA',        'codigo' => 36, 'hex' => '#C0C0C0', 'macrocategoria' => 'BEIGE / CREMA'],
            ['nombre' => 'GRIS',         'codigo' => 92, 'hex' => '#666666', 'macrocategoria' => 'BEIGE / CREMA'],

            // CAFÉ / MARRÓN
            ['nombre' => 'BROWN',  'codigo' => 76, 'hex' => '#6B4423', 'macrocategoria' => 'CAFÉ / MARRÓN'],
            ['nombre' => 'CAFE',   'codigo' => 79, 'hex' => '#4B3621', 'macrocategoria' => 'CAFÉ / MARRÓN'],
            ['nombre' => 'BISTRO', 'codigo' => 82, 'hex' => '#4A3B2A', 'macrocategoria' => 'CAFÉ / MARRÓN'],
            ['nombre' => 'MOKA',   'codigo' => 85, 'hex' => '#3B2A1E', 'macrocategoria' => 'CAFÉ / MARRÓN'],

            // CAMEL / CARAMELO
            ['nombre' => 'MIEL',     'codigo' => 44, 'hex' => '#C68E42', 'macrocategoria' => 'CAMEL / CARAMELO'],
            ['nombre' => 'AREQUIPE', 'codigo' => 45, 'hex' => '#B08D57', 'macrocategoria' => 'CAMEL / CARAMELO'],
            ['nombre' => 'CAMEL',    'codigo' => 47, 'hex' => '#C19A6B', 'macrocategoria' => 'CAMEL / CARAMELO'],
            ['nombre' => 'AMARETO',  'codigo' => 53, 'hex' => '#B4802F', 'macrocategoria' => 'CAMEL / CARAMELO'],
            ['nombre' => 'YUTE',     'codigo' => 56, 'hex' => '#B08D57', 'macrocategoria' => 'CAMEL / CARAMELO'],
            ['nombre' => 'CARAMELO', 'codigo' => 59, 'hex' => '#A9682B', 'macrocategoria' => 'CAMEL / CARAMELO'],
            ['nombre' => 'TAUPE',    'codigo' => 73, 'hex' => '#7A6A5D', 'macrocategoria' => 'CAMEL / CARAMELO'],
            ['nombre' => 'DORADO',   'codigo' => 42, 'hex' => '#D4AF37', 'macrocategoria' => 'CAMEL / CARAMELO'],
            ['nombre' => 'OCRE',     'codigo' => 70, 'hex' => '#9C7A26', 'macrocategoria' => 'CAMEL / CARAMELO'],

            // NEGRO / OSCUROS
            ['nombre' => 'OSCURO', 'codigo' => 96, 'hex' => '#2E2E2E', 'macrocategoria' => 'NEGRO / OSCUROS'],
            ['nombre' => 'NEGRO',  'codigo' => 99, 'hex' => '#000000', 'macrocategoria' => 'NEGRO / OSCUROS'],

            // NUDE / ARENA
            ['nombre' => 'CHAMPAÑA', 'codigo' => 21, 'hex' => '#F0DFC4', 'macrocategoria' => 'NUDE / ARENA'],
            ['nombre' => 'VAINILLA', 'codigo' => 27, 'hex' => '#EED9AE', 'macrocategoria' => 'NUDE / ARENA'],
            ['nombre' => 'NUDE',     'codigo' => 30, 'hex' => '#E3C9A6', 'macrocategoria' => 'NUDE / ARENA'],
            ['nombre' => 'ARENA',    'codigo' => 33, 'hex' => '#D9C199', 'macrocategoria' => 'NUDE / ARENA'],
            ['nombre' => 'KHAKI',    'codigo' => 50, 'hex' => '#C3B091', 'macrocategoria' => 'NUDE / ARENA'],
            ['nombre' => 'ORO ROSA', 'codigo' => 39, 'hex' => '#E0BFB8', 'macrocategoria' => 'NUDE / ARENA'],

            // ROJO / VINOTINTO
            ['nombre' => 'ROJO', 'codigo' => 88, 'hex' => '#B22222', 'macrocategoria' => 'ROJO / VINOTINTO'],
            ['nombre' => 'VINO', 'codigo' => 90, 'hex' => '#5B1A1A', 'macrocategoria' => 'ROJO / VINOTINTO'],
        ];
    }

    private function color_groups(): array
    {
        $grupos = [];

        foreach ($this->colores() as $color) {
            $macro = $color['macrocategoria'];

            if (!isset($grupos[$macro])) {
                $grupos[$macro] = [
                    'macrocategoria' => $macro,
                    'colores'        => [],
                ];
            }

            $grupos[$macro]['colores'][] = [
                'nombre' => ucfirst(strtolower($color['nombre'])),
                'codigo' => $color['codigo'],
                'hex'    => $color['hex'],
            ];
        }

        return array_values($grupos);
    }

    private function images(string $referencia)
    {
        $cacheKey = 'inventory_filter:images:' . hash('sha256', mb_strtoupper($referencia));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($referencia) {
            $path = self::BASE_PATH . "/{$referencia}";
            if (!Storage::disk(self::DISK)->exists($path)) {
                return collect();
            }

            return collect(Storage::disk(self::DISK)->files($path))
                ->map(fn ($file) => [
                    'name' => basename($file),
                    'url' => Storage::disk(self::DISK)->url($file),
                ])
                ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                ->values();
        });
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
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
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
        ]);

        $color = preg_replace('/[^A-Z0-9]/', '', $color);

        return $color;
    }

    private function sellers(string $token)
    {
        $page = 1;
        $pageSize = 100;
        $totalPages = null;
        $sellers = [];

        do {
            $response = Http::retry(5, 10000)->timeout(180)->withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => $token,
                'Partner-Id' => 'consultadeFacturas',
            ])->get("{$this->siigo_base_url}/v1/users", [
                'page' => $page,
                'page_size' => $pageSize,
            ]);

            if ($response->status() === 429) {
                sleep(1);
                continue;
            }

            if (! $response->successful()) {
                throw new \Exception($response->body());
            }

            $data = $response->json();

            if (!empty($data['results'])) {
                $sellers = array_merge($sellers, $data['results']);
            }

            if ($totalPages === null) {
                $pagination = $data['pagination'];
                $totalPages = (int) ceil($pagination['total_results'] / $pagination['page_size']);
            }

            $page++;

        } while ($page <= $totalPages);

        $sellers = collect($sellers)->pluck('email')->toArray();

        return [...$sellers, 'tecnologia@revent.com.co'];
    }
}
