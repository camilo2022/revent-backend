<?php

namespace App\Http\Controllers\Integration;

use App\Http\Controllers\Controller;
use App\Services\SiigoInventoryService;
use App\Mail\InventroyFilterAccessLink;
use App\Services\ProductPhotoService;
use App\Services\SiigoProductsCache;
use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class InventoryFilterSiigoController extends Controller
{
    private const DISK = ProductPhotoService::DISK;
    private const BASE_PATH = ProductPhotoService::BASE_PATH;
    private const REPORT_URL = 'https://services.siigo.com/document/api/v1/reports/getreport';
    private const AUTOCOMPLETE_URL = 'https://services.siigo.com/catalog/api/v1/Autocomplete/GetData';

    private string $siigo_base_url = 'https://api.siigo.com';

    public function __construct(private ProductPhotoService $photos)
    {
    }

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
            ->post(self::REPORT_URL, $body);

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

    public function inventory_filter_all_warehouses(Request $request)
    {
        $validated = $request->validate([
            'referencia' => ['required', 'string', 'max:100'],
        ]);

        set_time_limit(180);

        $referencia = mb_strtoupper($this->clean_text(trim($validated['referencia'])));

        $cacheKey = 'INVENTORY_FILTER:ALL_WAREHOUSES:v3:' . $referencia;

        $payload = Cache::get($cacheKey);

        if ($payload === null) {
            $payload = $this->stock_all_warehouses($referencia);

            if (empty($payload['errores']) && !empty($payload['productos'])) {
                Cache::put($cacheKey, $payload, now()->addSeconds(60));
            }
        }

        return response()->json($payload);
    }

    private function stock_all_warehouses(string $referencia): array
    {
        $siigo = new SiigoInventoryService();
        $token = $siigo->auth();

        $autocomplete = $this->autocomplete_products($token, $referencia);
        $items = $autocomplete['items'] ?? [];

        if (empty($items)) {
            return [
                'referencia' => $referencia,
                'productos'  => [],
                'errores'    => [],
            ];
        }

        $errores = [];

        $responses = Http::pool(function (Pool $pool) use ($items, $token) {
            $requests = [];

            foreach ($items as $id => $descripcion) {
                $requests[] = $pool->as('p' . $id)
                    ->withToken($token)
                    ->acceptJson()
                    ->timeout(90)
                    ->post(self::REPORT_URL, $this->report_body((int) $id, $descripcion));
            }

            return $requests;
        });

        $porBodega = [];

        foreach ($items as $id => $descripcion) {
            $r = $responses['p' . $id] ?? null;

            if (!$r instanceof Response || !$r->successful()) {
                $errores[] = $descripcion;
                continue;
            }

            $filas = $r->json('data.Value.Table');
            $filas = is_array($filas) ? $filas : [];

            foreach ($filas as $fila) {
                $partes = preg_split('/[-*]/', (string) ($fila['Description'] ?? ''));

                if (mb_strtoupper($this->clean_text(trim($partes[0] ?? ''))) !== $referencia) {
                    continue;
                }

                $bodegaId = $fila['productwarehousecode'] ?? null;

                if ($bodegaId === null) {
                    continue;
                }

                $nombre = trim((string) ($fila['pwhDescription'] ?? ''));

                if ($nombre === '') {
                    $cod = (string) ($fila['CodDesWH'] ?? '');
                    $nombre = trim(str_contains($cod, ' - ') ? explode(' - ', $cod, 2)[1] : $cod);
                }

                if (str_starts_with(mb_strtoupper($nombre), 'TRANSITO ')) {
                    continue;
                }

                $porBodega[$bodegaId]['nombre'] = $nombre;
                $porBodega[$bodegaId]['filas'][] = $fila;
            }
        }

        $resultado = [];

        foreach ($porBodega as $bodegaId => $bodega) {
            foreach ($this->map_products($bodega['filas']) as $producto) {
                $producto['id'] = $producto['referencia'] . '@' . $bodegaId;
                $producto['bodega'] = $bodega['nombre'];
                $producto['bodega_id'] = $bodegaId;

                $resultado[] = $producto;
            }
        }

        usort($resultado, fn ($a, $b) => strcmp($a['bodega'], $b['bodega']));

        return [
            'referencia' => $referencia,
            'productos'  => $resultado,
            'errores'    => $errores,
        ];
    }

    private function autocomplete_products(string $token, string $referencia)
    {
        $ids = [];
        $items = [];
        $descripciones = [];
        $vistos = [];
        $numRecordView = 0;
        $maxIntentos = 10;

        for ($intento = 0; $intento < $maxIntentos; $intento++) {
            $response = Http::retry(3, 3000, null, false)
                ->withToken($token)
                ->timeout(120)
                ->asJson()
                ->post(self::AUTOCOMPLETE_URL, [
                    'type'          => 1,
                    'browserID'     => '33',
                    'query'         => $referencia,
                    'filter'        => 'IsInventoryControl=1',
                    'numRecordView' => $numRecordView,
                    'tags'          => (object) [],
                ]);

            if (!$response->successful()) {
                throw new \Exception('Error consultando productos: ' . $response->body());
            }

            $lista = $this->decode_autocomplete($response->body());

            if (empty($lista)) break;

            $nuevos = 0;

            foreach ($lista as $item) {
                if (!is_array($item)) continue;

                $description = $item['Description'];
                $id = $item['ProductID'];

                $clave = ($id ?? '') . '|' . $description;

                if (isset($vistos[$clave])) continue;

                $vistos[$clave] = true;
                $nuevos++;

                $partes = preg_split('/[-*]/', $description);
                $ref = mb_strtoupper($this->clean_text(trim($partes[0] ?? '')));

                if ($ref !== $referencia || $id === null) continue;

                $ids[] = is_numeric($id) ? (int) $id : $id;
                $items[(int) $id] = $description;
            }

            if ($nuevos === 0) break;

            $numRecordView += 10;
        }

        return [
            'ids' => array_values(array_unique($ids)),
            'descripcion' => $descripciones[0] ?? '',
            'items' => $items
        ];
    }

    private function decode_autocomplete(string $body): array
    {
        $data = trim($body);

        for ($i = 0; $i < 5 && is_string($data); $i++) {
            $data = trim($data);

            if ($data === '') {
                return [];
            }

            $decoded = json_decode($data, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return [];
            }

            $data = $decoded;
        }

        if (!is_array($data)) {
            return [];
        }

        foreach (['Data', 'data', 'Items', 'items', 'Results', 'results', 'Value', 'value'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $data = $data[$key];
                break;
            }
        }

        return array_is_list($data) ? $data : [];
    }

    private function report_body(int $productId, string $descripcion): array
    {
        $filters = [
            [
                'Field'        => '_vProduct',
                'FilterType'   => 6,
                'OperatorType' => 0,
                'Value'        => [$productId],
                'ValueUI'      => $descripcion,
                'Source'       => '2',
            ],
            [
                'Field'        => 'WarehouseFilter',
                'FilterType'   => 2,
                'OperatorType' => 0,
                'Value'        => [-1],
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

        return [
            'FilterCriterias'    => json_encode($filters, JSON_UNESCAPED_UNICODE),
            'GetTotalCount'      => false,
            'GridOrderCriteria'  => null,
            'Id'                 => 5443,
            'Params'             => json_encode(['TabID' => '1617', 'pTabID' => '1445', 'rReport' => '1']),
            'Skip'               => 0,
            'Sort'               => ' ',
            'Take'               => 0,
        ];
    }

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

            if (empty($agrupado[$referencia]['categoria'])) {
                $producto = $productsByCode->get($fila['productcode'] ?? null);

                if ($producto) {
                    $agrupado[$referencia]['categoria'] = $producto['model'] ?? '';
                }
            }

            if (empty($agrupado[$referencia]['precio'])) {
                $producto = $productsByCode->get($fila['productcode'] ?? null);

                if ($producto) {
                    $agrupado[$referencia]['precio'] = $producto['price'] ?? '';
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

        foreach ($this->photos->colores() as $color) {
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

    private function color_groups(): array
    {
        $grupos = [];

        foreach ($this->photos->colores() as $color) {
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
        $cacheKey = 'INVENTORY_FILTER:IMAGES:' . mb_strtoupper($referencia);

        return Cache::remember($cacheKey, Carbon::now()->addMinutes(5), function () use ($referencia) {
            $path = self::BASE_PATH . "/{$referencia}";
            if (!Storage::disk(self::DISK)->exists($path)) {
                return collect();
            }

            return collect(Storage::disk(self::DISK)->allFiles($path))
                ->sortBy(fn ($file) => [
                    substr_count($file, '/'), // primero las de la raíz, luego las de subcarpetas
                    $file,
                ])
                ->map(fn ($file) => [
                    'name' => basename($file),
                    'url' => Storage::disk(self::DISK)->url($file),
                ])
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
