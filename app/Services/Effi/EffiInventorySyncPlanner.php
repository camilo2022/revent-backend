<?php

namespace App\Services\Effi;

use App\Services\SiigoProductsCache;

/**
 * Arma el plan para dejar la bodega de Effi exactamente igual a la bodega WEB de Siigo. NO escribe nada: solo calcula los pasos.
 *
 * Pasos por código de Siigo, en este orden de ejecución:
 *  - referencia: el artículo de Effi cruzado por descripción pasa a tener el código de Siigo.
 *  - descripcion: el nombre del artículo de Effi pasa a ser exactamente la descripción de Siigo.
 *  - crear:      el código no existe en Effi: se crea con los datos que Siigo tiene en el caché de productos
 *                (código, descripción tal cual, modelo → categoría, marca, precio y código de barras).
 *  - vaciar:     un artículo sin control por bodega con unidades se vacía, porque Effi solo cambia el control con stock en cero.
 *  - control:    el artículo pasa a «Trazabilidad por cantidad - Control por bodega» (3).
 *  - ajuste:     ingreso o egreso por la diferencia contra Siigo.
 *
 * Todo lo que no se pudo resolver con certeza queda en «avisos» (por código) o en «manuales» (no se toca) con su motivo.
 */
class EffiInventorySyncPlanner
{
    public const CONTROL_POR_BODEGA = 3;
    private const TIPO_PRODUCTO_TERMINADO = 3;

    /** El precio «Tarifa normal» de Effi es el de Siigo por este factor (comprobado en los artículos que ya existen en los dos lados). */
    private const FACTOR_TARIFA_NORMAL = 0.81;

    /** Parte mínima de los artículos cruzados de un modelo de Siigo que debe caer en la misma categoría de Effi para fiarse de ella. */
    private const CONFIANZA_CATEGORIA = 0.8;

    /** Parecido mínimo (0 a 1) entre el modelo de Siigo y una categoría de Effi para recomendarla. */
    private const UMBRAL_PARECIDO = 0.75;

    public function __construct(private EffiWebInventoryComparator $comparator)
    {
    }

    /** Consulta Siigo y Effi y arma el plan. */
    public function plan(EffiClient $effi, ?array $siigoProducts = null): array
    {
        $comparison = $this->comparator->compare();

        return $this->fromComparison(
            $comparison,
            $this->comparator->articles(),
            [
                'tarifas' => $effi->catalog('tarifa_precio'),
                'categorias' => $effi->catalog('categoria_articulo'),
                'marcas' => $effi->catalog('marca_articulo'),
            ],
            $siigoProducts ?? collect(app(SiigoProductsCache::class)->all())->keyBy('code')->all()
        );
    }

    /**
     * Parte pura (sin red).
     *
     * @param array $catalogs        tarifas, categorias y marcas de Effi (id => nombre)
     * @param array $siigoProducts   productos del caché de Siigo por código (description, model, brand, price, codeBars)
     */
    public function fromComparison(array $comparison, array $articles, array $catalogs, array $siigoProducts): array
    {
        if (!$comparison['stock_effi_disponible']) {
            return ['ok' => false, 'message' => 'Effi no entrega el stock (falta el permiso de la función 156): no se puede armar el plan.'];
        }

        $byId = collect($articles)->keyBy('id');
        $references = collect($articles)->groupBy(fn ($a) => mb_strtoupper(trim((string) ($a['referencia'] ?? ''))));
        $categoryByModel = $this->categoriesByModel($comparison['filas'], $byId, $siigoProducts);
        $items = [];
        $manual = [];

        foreach ($comparison['filas'] as $row) {
            if ($row['estado'] === 'repetida') {
                $manual[] = $this->manual($row, 'La referencia está repetida en Effi (más de un artículo): elige abajo cuál queda y qué se hace con el otro.') + [
                    'tipo' => 'duplicado',
                    'articulos' => $references->get($row['codigo'], collect())->map(fn ($a) => [
                        'id' => (string) $a['id'], 'referencia' => $a['referencia'], 'descripcion' => $a['descripcion'],
                        'gestion_stock' => (int) $a['gestion_stock'], 'stock' => $this->stockOf($a), 'costo' => (float) ($a['p_costo_promedio'] ?? $a['p_costo'] ?? 0), 'dropshipping' => (string) ($a['dropshipping'] ?? '0'),
                    ])->values()->all(),
                ];
                continue;
            }

            $article = $row['effi_id'] ? $byId->get($row['effi_id']) : null;
            $item = $article
                ? $this->forExisting($row, $article, $references)
                : $this->forMissing($row, $siigoProducts[$row['codigo']] ?? null, $catalogs, $categoryByModel);

            if (isset($item['manual'])) {
                $manual[] = $this->manual($row, $item['manual']);
            } elseif ($item['pasos']) {
                $items[] = $item;
            } elseif ($article && $row['estado'] !== 'igual') {
                // Nada puede quedar fuera del plan en silencio: si el cuadre muestra una diferencia, aquí aparece con su motivo
                $manual[] = $this->manual($row, 'Hay una diferencia con Siigo pero no hay un cambio automático definido para este caso: revísalo a mano en Effi.');
            }
        }

        [$items, $manual] = $this->uniqueCodes($items, $manual);

        return [
            'ok' => true,
            'fecha' => $comparison['fecha'],
            'bodega_siigo' => $comparison['bodega_siigo'],
            'bodega_effi' => $comparison['bodega_effi'],
            'resumen' => $this->summary($items, $manual, ['unidades_siigo' => $comparison['resumen']['unidades_siigo'], 'unidades_effi' => $comparison['resumen']['unidades_effi']]),
            'categorias' => $catalogs['categorias'] ?? [],
            'decisiones' => $this->pendingDecisions($items, $manual, $catalogs['categorias'] ?? []),
            'totales' => ['unidades_siigo' => $comparison['resumen']['unidades_siigo'], 'unidades_effi' => $comparison['resumen']['unidades_effi']],
            'notas' => ['Dropshipping: los artículos nuevos se crean SIN Dropshipping (no se comparte nada). Los artículos que se modifican y ya están compartidos llevan su misma categoría y subcategoría de Dropshipping; si no las tienen, Effi las exige y se envían «Moda Unisex → Calzado» («Bolsos, Mochilas y Carteras» para bolsos; se cambian con EFFI_DROPSHIPPING_CATEGORY y EFFI_DROPSHIPPING_SUBCATEGORY). A los artículos sin Dropshipping no se les envía nada.', 'El precio de «Tarifa normal» de los artículos nuevos es el de Siigo × ' . self::FACTOR_TARIFA_NORMAL . ' (así están los artículos que ya existen en los dos sistemas); «Tarifa Shopify» lleva el precio de Siigo.'],
            'items' => $items,
            'manuales' => $manual,
        ];
    }

    private function forExisting(array $row, array $article, $references): array
    {
        $code = $row['codigo'];
        $siigo = (float) $row['siigo'];
        $effi = (float) $row['effi'];
        $steps = [];

        if (mb_strtoupper(trim((string) $article['referencia'])) !== $code) {
            if (mb_strlen($code) > 40) {
                return ['manual' => 'El código de Siigo tiene más de 40 caracteres (límite de la referencia en Effi).'];
            }

            if ($references->has($code)) {
                return ['manual' => 'El código de Siigo ya es la referencia de otro artículo de Effi (#' . $references->get($code)->first()['id'] . ').'];
            }

            $steps[] = ['tipo' => 'referencia', 'de' => $article['referencia'], 'a' => $code];
        }

        // El nombre del artículo en Effi debe ser exactamente la descripción de Siigo
        $warnings = [];
        $siigoName = trim((string) $row['descripcion']);

        if ($siigoName !== '' && trim((string) ($article['descripcion'] ?? $siigoName)) !== $siigoName) {
            if (mb_strlen($siigoName) > 120) {
                $warnings[] = 'La descripción de Siigo tiene más de 120 caracteres (el límite de Effi): no se cambió el nombre.';
            } else {
                $steps[] = ['tipo' => 'descripcion', 'de' => $article['descripcion'], 'a' => $siigoName];
            }
        }

        if ((int) $article['gestion_stock'] !== self::CONTROL_POR_BODEGA) {
            // Sin control por bodega: Effi solo deja cambiarlo con el stock en cero
            if ($effi > 0) {
                $steps[] = ['tipo' => 'vaciar', 'cantidad' => $effi];
            }

            // Lo que queda en Effi después de vaciar (unidades negativas no se vacían: se compensan con el ingreso)
            $left = $effi > 0 ? 0.0 : $effi;

            if ($siigo > 0) {
                $steps[] = ['tipo' => 'control', 'de' => (int) $article['gestion_stock'], 'a' => self::CONTROL_POR_BODEGA];
                $steps[] = $this->adjustment($siigo - $left, $article);
            } elseif ($left < 0) {
                // Siigo no tiene unidades y Effi las tiene en negativo: un ingreso lo deja en cero
                $steps[] = $this->adjustment(-$left, $article);
            }
        } elseif ($siigo != $effi) {
            $steps[] = $this->adjustment($siigo - $effi, $article);
        }

        return $this->item($row, $article, $steps, $warnings);
    }

    private function forMissing(array $row, ?array $product, array $catalogs, array $categoryByModel): array
    {
        $code = $row['codigo'];

        if ((float) $row['siigo'] <= 0) {
            return ['pasos' => []];
        }

        if (mb_strlen($code) > 40) {
            return ['manual' => 'El código de Siigo tiene más de 40 caracteres (límite de la referencia en Effi).'];
        }

        if (!$product) {
            return ['manual' => 'El código no está en el caché de productos de Siigo (la sincronización de la noche): no hay datos para crearlo.'];
        }

        $warnings = [];
        $model = trim((string) ($product['model'] ?? ''));
        // Solo la categoría que ya usan los artículos cruzados de ese modelo; si no la hay, la decide la persona en la vista
        $category = $categoryByModel[$this->comparator->normalize($model)] ?? null;
        $brand = $this->brand((string) ($product['brand'] ?? ''), $catalogs['marcas'] ?? [], $warnings);
        $prices = $this->prices($product['price'] ?? null, $catalogs['tarifas'] ?? [], $warnings);
        $barcode = trim((string) ($product['codeBars'] ?? ''));

        $data = array_filter([
            // La descripción y el código son los de Siigo, tal cual
            'descripcion' => mb_substr(trim((string) $row['descripcion']), 0, 120),
            'referencia' => $code,
            't_articulo' => self::TIPO_PRODUCTO_TERMINADO,
            'sucursal' => (int) config('effi.sucursal'),
            'categoria' => $category,
            'marca' => $brand,
            'gestion_stock' => self::CONTROL_POR_BODEGA,
            // Los artículos nuevos se crean sin Dropshipping: no se comparte ni se crea nada de eso
            'dropshipping' => false,
            'compras' => true,
            'ventas' => true,
            'descuento' => true,
            'c_barras' => ($barcode !== '' && mb_strlen($barcode) <= 40) ? [$barcode] : null,
            'precios' => $prices,
        ], fn ($v) => $v !== null && $v !== []);

        $item = $this->item($row, null, [
            ['tipo' => 'crear', 'datos' => $data],
            ['tipo' => 'ajuste', 'cantidad' => (float) $row['siigo'], 'costo' => 0],
        ], $warnings, $model !== '' ? $model : null);

        // Con modelo pero sin categoría resuelta con datos: la decide la persona. Sin modelo en Siigo: se crea sin categoría, sin preguntar.
        $item['categoria_pendiente'] = !$category && $model !== '';

        return $item;
    }

    private function brand(string $brand, array $brands, array &$warnings): ?int
    {
        $brand = $this->comparator->normalize($brand);

        if ($brand === '') {
            return null;
        }

        foreach ($brands as $id => $name) {
            if (str_contains($this->comparator->normalize($name), $brand)) {
                return (int) $id;
            }
        }

        $warnings[] = 'Sin marca: la marca de Siigo no existe en Effi.';

        return null;
    }

    /** Siigo tiene un solo precio: «Tarifa Shopify» lleva ese precio y «Tarifa normal» el precio por el factor observado. */
    private function prices(mixed $price, array $tariffs, array &$warnings): array
    {
        if (!is_numeric($price) || $price <= 0) {
            $warnings[] = 'Sin precio: Siigo no tiene precio para este producto.';

            return [];
        }

        $ids = array_flip($tariffs);
        $prices = [];

        isset($ids['Tarifa Shopify']) && $prices[] = ['tarifa_precio' => (int) $ids['Tarifa Shopify'], 'p_venta' => (float) $price];
        isset($ids['Tarifa normal']) && $prices[] = ['tarifa_precio' => (int) $ids['Tarifa normal'], 'p_venta' => (float) round($price * self::FACTOR_TARIFA_NORMAL)];

        return $prices;
    }

    /** modelo de Siigo (normalizado) => id de la categoría de Effi, según los artículos que ya existen en los dos lados. */
    private function categoriesByModel(array $rows, $byId, array $siigoProducts): array
    {
        $counts = [];

        foreach ($rows as $row) {
            $article = $row['effi_id'] ? $byId->get($row['effi_id']) : null;
            $model = $this->comparator->normalize((string) ($siigoProducts[$row['codigo']]['model'] ?? ''));

            if ($article && $model !== '' && !empty($article['categoria'])) {
                $counts[$model][(int) $article['categoria']] = ($counts[$model][(int) $article['categoria']] ?? 0) + 1;
            }
        }

        $result = [];

        foreach ($counts as $model => $byCategory) {
            arsort($byCategory);

            if (reset($byCategory) / array_sum($byCategory) >= self::CONFIANZA_CATEGORIA) {
                $result[$model] = (int) key($byCategory);
            }
        }

        return $result;
    }

    /**
     * Categoría de Effi que más se parece al modelo de Siigo, solo como recomendación (la persona decide).
     * Se compara cada palabra del modelo con el nombre de la categoría, sin mayúsculas, tildes ni signos, contando
     * cuántas letras habría que cambiar (distancia de Levenshtein): «CHUNKI» y «Chunky» difieren en 1 de 6 letras = 83 %.
     * Se recomienda solo si la mejor llega al umbral y es mejor que todas las demás (si hay empate, no se recomienda).
     */
    private function suggestCategory(string $model, array $categories): ?array
    {
        $words = array_filter(preg_split('/[\s\-_\/.]+/', $model));
        $scores = [];

        foreach ($categories as $id => $name) {
            $best = 0.0;

            foreach ($words as $word) {
                $a = $this->comparator->normalize($word);
                $b = $this->comparator->normalize($name);
                $max = max(strlen($a), strlen($b));
                $max > 0 && $best = max($best, 1 - levenshtein($a, $b) / $max);
            }

            $scores[$id] = $best;
        }

        arsort($scores);
        $top = array_key_first($scores);
        $second = array_values($scores)[1] ?? 0;

        if ($top === null || $scores[$top] < self::UMBRAL_PARECIDO || $scores[$top] <= $second) {
            return null;
        }

        $percent = (int) round($scores[$top] * 100);

        return [
            'id' => (int) $top,
            'nombre' => $categories[$top],
            'similitud' => $percent,
            'motivo' => $percent === 100
                ? "«{$model}» tiene el mismo nombre que «{$categories[$top]}»."
                : "«{$model}» se parece a «{$categories[$top]}» ({$percent} % de letras iguales): probablemente es el mismo nombre mal escrito.",
        ];
    }

    private function item(array $row, ?array $article, array $steps, array $warnings = [], ?string $model = null): array
    {
        return [
            'codigo' => $row['codigo'],
            'descripcion' => $row['descripcion'],
            'siigo' => (float) $row['siigo'],
            'effi' => $row['effi'] === null ? null : (float) $row['effi'],
            'effi_id' => $article['id'] ?? null,
            'effi_referencia' => $article['referencia'] ?? null,
            'effi_descripcion' => $article['descripcion'] ?? null,
            'modelo' => $model,
            // Effi no deja modificar un artículo con el Dropshipping activo y sin categoría: al modificarlo se envía compartido con categoría
            'dropshipping' => $this->dropshippingParams($article['nombre_categoria'] ?? null, $article),
            'pasos' => $steps,
            'avisos' => $warnings,
        ];
    }

    private function adjustment(float $quantity, array $article): array
    {
        return [
            'tipo' => 'ajuste',
            'cantidad' => $quantity,
            // El ingreso exige costo unitario: el que ya tiene el artículo en Effi (0 si no lo entrega)
            'costo' => (float) ($article['p_costo_promedio'] ?? $article['p_costo'] ?? 0),
        ];
    }

    private function manual(array $row, string $reason): array
    {
        return ['codigo' => $row['codigo'], 'descripcion' => $row['descripcion'], 'siigo' => $row['siigo'], 'effi' => $row['effi'], 'motivo' => $reason];
    }

    private function summary(array $items, array $manual, array $totals): array
    {
        $steps = collect($items)->pluck('pasos')->flatten(1);
        $adjust = $steps->where('tipo', 'ajuste');

        return [
            'codigos_con_cambios' => count($items),
            'referencias_a_cambiar' => $steps->where('tipo', 'referencia')->count(),
            'descripciones_a_cambiar' => $steps->where('tipo', 'descripcion')->count(),
            'articulos_a_crear' => $steps->where('tipo', 'crear')->count(),
            'articulos_a_vaciar' => $steps->where('tipo', 'vaciar')->count(),
            'controles_a_cambiar' => $steps->where('tipo', 'control')->count(),
            'ingresos' => $adjust->where('cantidad', '>', 0)->count(),
            'unidades_ingreso' => $adjust->where('cantidad', '>', 0)->sum('cantidad'),
            'egresos' => $adjust->where('cantidad', '<', 0)->count() + $steps->where('tipo', 'vaciar')->count(),
            'unidades_egreso' => -$adjust->where('cantidad', '<', 0)->sum('cantidad') + $steps->where('tipo', 'vaciar')->sum('cantidad'),
            'con_avisos' => collect($items)->filter(fn ($i) => $i['avisos'])->count(),
            'manuales' => count($manual),
            'unidades_siigo' => $totals['unidades_siigo'],
            'unidades_effi' => $totals['unidades_effi'],
        ];
    }

    /**
     * Datos de Dropshipping que se envían al crear o modificar un artículo: compartido, con categoría y subcategoría de Effi
     * (por defecto «Moda Unisex → Calzado»; «Bolsos, Mochilas y Carteras» para los artículos de la categoría Bolso).
     * Solo para artículos que ya están compartidos; los que ya tienen categoría y subcategoría conservan las suyas.
     */
    private function dropshippingParams(?string $categoryName, ?array $article = null): array
    {
        // Solo los artículos que ya están compartidos (Dropshipping = sí) llevan estos datos al modificarlos; a los demás no se les envía nada
        if ($article === null || (string) ($article['dropshipping'] ?? '0') !== '1') {
            return [];
        }

        // Si el artículo ya tiene categoría y subcategoría de Dropshipping, se envían las mismas (no se cambian)
        if (!empty($article['categoria_dropshipping']) && !empty($article['subcategoria_dropshipping'])) {
            return [
                'dropshipping' => true,
                'categoria_dropshipping' => (int) $article['categoria_dropshipping'],
                'subcategoria_dropshipping' => (int) $article['subcategoria_dropshipping'],
            ];
        }

        $config = config('effi.dropshipping');

        if (empty($config['categoria']) || empty($config['subcategoria'])) {
            return [];
        }

        $bag = str_contains($this->comparator->normalize((string) $categoryName), 'BOLSO') && !empty($config['subcategoria_bolso']);

        return [
            'dropshipping' => true,
            'categoria_dropshipping' => (int) $config['categoria'],
            'subcategoria_dropshipping' => (int) ($bag ? $config['subcategoria_bolso'] : $config['subcategoria']),
        ];
    }
    /**
     * El ejecutor identifica cada paso por el código: si dos items comparten código, uno se aplicaría al artículo del otro.
     * El primero se queda; los demás pasan a «no se tocan» con su motivo.
     */
    private function uniqueCodes(array $items, array $manual): array
    {
        $seen = [];
        $kept = [];

        foreach ($items as $item) {
            if (isset($seen[$item['codigo']])) {
                $manual[] = $this->manual($item, 'El código aparece más de una vez en el plan (dos artículos de Effi con la misma referencia): no se toca para no ajustar el artículo equivocado.');
                continue;
            }

            $seen[$item['codigo']] = true;
            $kept[] = $item;
        }

        return [$kept, $manual];
    }

    private function stockOf(array $article): float
    {
        foreach ($article['stock_bodegas'] ?? [] as $stock) {
            if ((int) $stock['sucursal'] === (int) config('effi.sucursal') && (int) $stock['bodega'] === (int) config('effi.bodega')) {
                return (float) $stock['stock'];
            }
        }

        return 0;
    }

    /**
     * Decisiones que le tocan a la persona (el sistema no interpreta nada):
     *  - categorias: por modelo de Siigo sin categoría en Effi; con la categoría de nombre exactamente igual como sugerencia, si existe.
     *  - duplicados: por referencia repetida en Effi; con la recomendación de inhabilitar la que no controla stock por bodega.
     */
    private function pendingDecisions(array $items, array $manual, array $categories): array
    {
        $byModel = [];

        foreach ($items as $item) {
            if (!empty($item['categoria_pendiente'])) {
                $byModel[(string) $item['modelo']][] = $item['codigo'];
            }
        }

        $categoryDecisions = [];

        foreach ($byModel as $model => $codes) {
            $categoryDecisions[] = ['modelo' => $model, 'cantidad' => count($codes), 'ejemplos' => array_slice($codes, 0, 3), 'sugerida' => $this->suggestCategory($model, $categories)];
        }

        $duplicates = [];

        foreach ($manual as $row) {
            if (($row['tipo'] ?? null) !== 'duplicado' || count($row['articulos']) !== 2) {
                continue;
            }

            $withoutControl = collect($row['articulos'])->where('gestion_stock', '!=', self::CONTROL_POR_BODEGA);
            $duplicates[] = [
                'codigo' => $row['codigo'], 'descripcion' => $row['descripcion'], 'siigo' => $row['siigo'], 'articulos' => $row['articulos'],
                'recomendada' => $withoutControl->count() === 1 ? $withoutControl->first()['id'] : null,
            ];
        }

        return ['categorias' => $categoryDecisions, 'duplicados' => $duplicates];
    }

    /**
     * Aplica lo que la persona eligió en la vista. Lo que no tenga decisión (o no coincida con el plan actual) NO se toca:
     *  - modelos: [modelo => ['accion' => 'existente', 'categoria' => id] | ['accion' => 'crear'] | ['accion' => 'ninguna']]
     *    para los artículos nuevos sin categoría; «crear» crea la categoría en Effi solo si no existe ya.
     *  - duplicados: [código => ['inhabilitar' => id]] (el otro sigue con las unidades de Siigo) o ['ninguno' => true].
     */
    public function applyDecisions(array $plan, array $decisions): array
    {
        $kept = [];

        foreach ($plan['items'] as $item) {
            if (empty($item['categoria_pendiente'])) {
                $kept[] = $item;
                continue;
            }

            $decision = $decisions['modelos'][(string) $item['modelo']] ?? null;
            $action = $decision['accion'] ?? null;

            if ($action === 'existente' && isset($plan['categorias'][$decision['categoria'] ?? null])) {
                $item['pasos'][0]['datos']['categoria'] = (int) $decision['categoria'];
            } elseif ($action === 'crear' && (string) $item['modelo'] !== '') {
                $item['pasos'][0]['datos']['categoria_nueva'] = (string) $item['modelo'];
            } elseif ($action !== 'ninguna') {
                $plan['manuales'][] = $this->manual($item, 'Falta tu decisión sobre la categoría del modelo «' . $item['modelo'] . '».');
                continue;
            }

            $item['categoria_pendiente'] = false;
            $kept[] = $item;
        }

        $plan['items'] = $kept;
        $remaining = [];

        foreach ($plan['manuales'] as $manual) {
            $articles = collect($manual['articulos'] ?? []);
            $disable = $articles->firstWhere('id', (string) ($decisions['duplicados'][$manual['codigo']]['inhabilitar'] ?? ''));

            if (($manual['tipo'] ?? null) !== 'duplicado' || !$disable || $articles->count() !== 2) {
                $remaining[] = $manual;
                continue;
            }

            $keeper = $articles->firstWhere('id', '!=', $disable['id']);
            $row = ['codigo' => $manual['codigo'], 'descripcion' => $manual['descripcion'], 'siigo' => $manual['siigo'], 'effi' => $keeper['stock']];
            $kept = $this->forExisting($row, ['id' => $keeper['id'], 'referencia' => $keeper['referencia'], 'gestion_stock' => $keeper['gestion_stock'], 'p_costo' => $keeper['costo'], 'dropshipping' => $keeper['dropshipping'] ?? '0'], collect());

            if (!empty($kept['pasos'])) {
                $plan['items'][] = $kept;
            }

            // El inhabilitado se vacía (sus unidades pasan a contarse en el otro) y se anula: en Effi se puede reactivar
            $plan['items'][] = $this->item(
                ['codigo' => "{$manual['codigo']} (inhabilitar #{$disable['id']})", 'descripcion' => $disable['descripcion'], 'siigo' => 0, 'effi' => $disable['stock']],
                ['id' => $disable['id']],
                array_values(array_filter([$disable['stock'] > 0 ? ['tipo' => 'vaciar', 'cantidad' => $disable['stock']] : null, ['tipo' => 'anular']]))
            );
        }

        $plan['manuales'] = $remaining;
        $plan['resumen'] = $this->summary($plan['items'], $remaining, $plan['totales']);

        return $plan;
    }
}
