<?php

namespace App\Services\Integration;

use App\Models\Color;
use App\Models\User;
use App\Services\ProductPhotoService;
use App\Services\Siigo\SiigoClient;
use App\Services\Siigo\SiigoInventoryReportService;
use App\Services\Siigo\SiigoReportService;
use App\Services\SiigoProductsCache;
use App\Support\Integration\LegacyCacheKeys;
use Carbon\Carbon;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Inventario por bodega y en todas las bodegas, fotos de cada referencia y grupos de color del filtro de inventario.
 *
 * Copiado de Integration\InventoryFilterSiigoController: color_groups, map_products, obtener_color, images,
 * clean_text, normalizar_color, stock_all_warehouses, autocomplete_products, decode_autocomplete y report_body
 * (commit b8694f73). Si cambia alguno de esos métodos privados en el controlador legacy, hay que replicarlo aquí.
 *
 * El caché es el mismo que el de Integration (mismas claves y mismos tiempos, ver LegacyCacheKeys): el stock de una
 * referencia en todas las bodegas se guarda 60 s y las fotos 5 min. El payload guardado es el completo; el filtro por
 * las bodegas del usuario se aplica al entregarlo, nunca antes de guardarlo.
 */
class InventoryFilterService
{
    private const DISK = ProductPhotoService::DISK;
    private const BASE_PATH = ProductPhotoService::BASE_PATH;

    public function __construct(
        private ProductPhotoService $photos,
        private SiigoClient $client,
        private SiigoReportService $reports,
        private SiigoInventoryReportService $inventoryReport
    ) {
    }

    /** Productos con su stock de una bodega. */
    public function warehouseStock(int $warehouseId): array
    {
        $body = $this->inventoryReport->stockBody($warehouseId);

        $data = $this->reports->table($body, 'Error consultando inventario: ', $this->client->token());

        return $this->mapProducts($data);
    }

    /**
     * Stock de una referencia en todas las bodegas (con el caché de 60 s que comparte con Integration).
     *
     * @return array{referencia: string, productos: array, errores: array}
     */
    public function allWarehouses(string $reference): array
    {
        set_time_limit(180);

        $referencia = $this->normalizeReference($reference);

        $cacheKey = LegacyCacheKeys::inventoryAllWarehouses($referencia);

        $payload = Cache::get($cacheKey);

        if ($payload === null) {
            $payload = $this->stockAllWarehouses($referencia);

            if (empty($payload['errores']) && !empty($payload['productos'])) {
                Cache::put($cacheKey, $payload, now()->addSeconds(LegacyCacheKeys::INVENTORY_ALL_WAREHOUSES_SECONDS));
            }
        }

        return $payload;
    }

    /** Deja solo los productos de las bodegas a las que el usuario tiene acceso (todas si es supervisor). */
    public function onlyAccessible(array $payload, User $user): array
    {
        $ids = $user->accessibleWarehouseIds();

        if ($ids === ['*']) {
            return $payload;
        }

        $payload['productos'] = array_values(array_filter(
            $payload['productos'] ?? [],
            fn ($product) => in_array((int) ($product['bodega_id'] ?? 0), $ids, true)
        ));

        return $payload;
    }

    /** Referencia tal como la busca el legacy: sin tildes, sin espacios al borde y en mayúsculas. */
    public function normalizeReference(string $reference): string
    {
        return mb_strtoupper($this->cleanText(trim($reference)));
    }

    /** Página de fotos de una referencia. */
    public function imagesPage(string $reference, int $page, int $perPage): array
    {
        $images = $this->images($this->cleanText(strtoupper(trim($reference))));

        $total = $images->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min($page, $lastPage);

        return [
            'images' => $images->forPage($page, $perPage)->values()->all(),
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => $lastPage,
            'has_previous' => $page > 1,
            'has_next' => $page < $lastPage,
        ];
    }

    private ?array $colores = null;

    /**
     * Catálogo de colores del filtro. Manda el catálogo de colores del ERP (hex, código en Siigo y grupo en su configuración),
     * que se une al color de Siigo por el nombre; los colores que el ERP todavía no tiene salen de la lista fija de siempre.
     */
    private function colores(): array
    {
        if ($this->colores !== null) {
            return $this->colores;
        }

        $catalogo = [];

        foreach ($this->photos->colores() as $color) {
            $catalogo[$this->normalizarColor($color['nombre'])] = $color;
        }

        foreach (Color::query()->get() as $color) {
            $hex = $color->settings->hex ?? null;
            $base = $catalogo[$this->normalizarColor($color->name)] ?? null;

            // Sin hex propio ni color de la lista fija no hay con qué mostrarlo
            if (!$hex && !$base) {
                continue;
            }

            $catalogo[$this->normalizarColor($color->name)] = [
                'nombre'         => $color->name,
                'codigo'         => $color->settings->siigo_code ?? ($base['codigo'] ?? null),
                'hex'            => $hex ?: $base['hex'],
                'macrocategoria' => ($color->settings->macro_category ?? null) ?: ($base['macrocategoria'] ?? 'OTROS'),
            ];
        }

        return $this->colores = array_values($catalogo);
    }

    public function colorGroups(): array
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

    private function stockAllWarehouses(string $referencia): array
    {
        $token = $this->client->token();

        $autocomplete = $this->autocompleteProducts($token, $referencia);
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
                    ->post($this->reports->url(), $this->reportBody((int) $id, $descripcion));
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

                if (mb_strtoupper($this->cleanText(trim($partes[0] ?? ''))) !== $referencia) {
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
            foreach ($this->mapProducts($bodega['filas']) as $producto) {
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

    private function autocompleteProducts(string $token, string $referencia): array
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
                ->post($this->client->url('services', config('siigo.endpoints.services.autocomplete')), [
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

            $lista = $this->decodeAutocomplete($response->body());

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
                $ref = mb_strtoupper($this->cleanText(trim($partes[0] ?? '')));

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

    private function decodeAutocomplete(string $body): array
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

    private function reportBody(int $productId, string $descripcion): array
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

    public function mapProducts(array $filas): array
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

                $colorLimpio = $this->cleanText($color);

                $colorInfo = $this->obtenerColor($colorLimpio);

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
            $imagenes = $this->images($this->cleanText(strtoupper($referencia)));
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

    private function obtenerColor(string $nombre): array
    {
        $nombreBuscado = $this->normalizarColor($nombre);

        foreach ($this->colores() as $color) {
            $nombreColor = $this->normalizarColor($color['nombre']);

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

    private function images(string $referencia): Collection
    {
        $cacheKey = LegacyCacheKeys::inventoryImages($referencia);

        return Cache::remember($cacheKey, Carbon::now()->addSeconds(LegacyCacheKeys::INVENTORY_IMAGES_SECONDS), function () use ($referencia) {
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

    private function cleanText(string $texto): string
    {
        $texto = strtr($texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U',
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u',
        ]);

        return trim($texto);
    }

    private function normalizarColor(string $color): string
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

        return preg_replace('/[^A-Z0-9]/', '', $color);
    }
}
