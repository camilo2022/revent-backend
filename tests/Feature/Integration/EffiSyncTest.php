<?php

namespace Tests\Feature\Integration;

use App\Services\Effi\EffiClient;
use App\Services\Effi\EffiInventorySyncPlanner;
use App\Services\Effi\EffiInventorySyncRunner;
use App\Services\Effi\EffiWebInventoryComparator;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Plan y ejecución de la sincronización Siigo → Effi, con respuestas simuladas: no toca ni Siigo ni Effi. */
class EffiSyncTest extends TestCase
{
    private function article(int $id, string $reference, string $name, int $control, float $stock, array $extra = []): array
    {
        return $extra + [
            'id' => (string) $id, 'referencia' => $reference, 'descripcion' => $name, 'gestion_stock' => (string) $control,
            'categoria' => '1', 'precios_venta' => 'Tarifa normal: 50000.0000; Tarifa Shopify: 60000.0000',
            'stock_bodegas' => [['sucursal' => '1', 'bodega' => '1', 'stock' => (string) $stock]],
        ];
    }

    private function row(string $code, string $description, float $siigo, ?float $effi, ?array $article, string $status, ?string $match): array
    {
        return [
            'codigo' => $code, 'descripcion' => $description, 'siigo' => $siigo, 'effi' => $effi,
            'effi_id' => $article['id'] ?? null, 'effi_gestion' => $article ? (int) $article['gestion_stock'] : null,
            'cruce' => $match, 'estado' => $status,
        ];
    }

    private function plan(array $rows, array $articles, array $products = []): array
    {
        $planner = new EffiInventorySyncPlanner(app(EffiWebInventoryComparator::class));

        return $planner->fromComparison(
            ['stock_effi_disponible' => true, 'fecha' => 'hoy', 'bodega_siigo' => 31, 'bodega_effi' => 'B', 'resumen' => ['unidades_siigo' => 0, 'unidades_effi' => 0], 'filas' => $rows],
            $articles,
            ['tarifas' => [1 => 'Tarifa normal', 2 => 'Tarifa Shopify'], 'categorias' => [1 => 'Baleta', 3 => 'Chunky', 4 => 'Tacon', 8 => 'Tenis', 9 => 'Botas', 10 => 'Mocasin'], 'marcas' => [1 => 'Revent Calzado']],
            $products
        );
    }

    public function test_cambia_la_referencia_al_codigo_de_siigo_y_ajusta_la_diferencia(): void
    {
        $a = $this->article(10, 'BACABLAN35', 'BACARDI-BLANCO-SEBA-BA-35', 3, 1);
        $plan = $this->plan([$this->row('BA.10.35.1012', 'BACARDI-BLANCO-SEBA-BA-35', 3, 1, $a, 'diferencia', 'descripcion')], [$a]);

        $this->assertSame([['tipo' => 'referencia', 'de' => 'BACABLAN35', 'a' => 'BA.10.35.1012'], ['tipo' => 'ajuste', 'cantidad' => 2.0, 'costo' => 0.0]], $plan['items'][0]['pasos']);
    }

    public function test_un_articulo_sin_control_con_unidades_se_vacia_cambia_de_control_y_se_vuelve_a_ingresar(): void
    {
        $a = $this->article(11, 'AVERBLAN36', 'AVERY-BLANCO-BERT-BA-36', 1, 2);
        $plan = $this->plan([$this->row('AVERBLAN36', 'AVERY-BLANCO-BERT-BA-36', 3, 2, $a, 'sin_control', 'referencia')], [$a]);

        $this->assertSame(['vaciar', 'control', 'ajuste'], array_column($plan['items'][0]['pasos'], 'tipo'));
        $this->assertSame(3.0, $plan['items'][0]['pasos'][2]['cantidad']);
    }

    public function test_un_codigo_que_no_existe_en_effi_se_crea_con_los_datos_de_siigo_del_cache_de_productos(): void
    {
        // Artículo ya cruzado del mismo modelo (BALETA → categoría 1) para aprender la categoría
        $known = $this->article(12, 'LAZOMIEL36', 'LAZO-MIEL-MANI-BA-36', 3, 0);
        $products = [
            'LAZOMIEL36' => ['model' => 'BALETA', 'brand' => 'REVENT', 'price' => 100000, 'codeBars' => 'LAZOMIEL36'],
            'BN.44.35.1000' => ['model' => 'BALETA', 'brand' => 'REVENT', 'price' => 64990, 'codeBars' => 'BN.44.35.1000'],
        ];
        $rows = [
            $this->row('LAZOMIEL36', 'LAZO-MIEL-MANI-BA-36', 0, 0, $known, 'igual', 'referencia'),
            $this->row('BN.44.35.1000', 'LAZO-MIEL-MANI-BA-35', 2, null, null, 'no_existe', null),
        ];

        $item = $this->plan($rows, [$known], $products)['items'][0];
        [$create, $adjust] = $item['pasos'];

        $this->assertSame('crear', $create['tipo']);
        $this->assertSame('LAZO-MIEL-MANI-BA-35', $create['datos']['descripcion']);
        $this->assertSame('BN.44.35.1000', $create['datos']['referencia']);
        $this->assertSame(3, $create['datos']['gestion_stock']);
        $this->assertSame(1, $create['datos']['categoria']);
        $this->assertSame(1, $create['datos']['marca']);
        $this->assertSame(['BN.44.35.1000'], $create['datos']['c_barras']);
        $this->assertSame([['tarifa_precio' => 2, 'p_venta' => 64990.0], ['tarifa_precio' => 1, 'p_venta' => 52642.0]], $create['datos']['precios']);
        $this->assertSame(2.0, $adjust['cantidad']);
        $this->assertSame([], $item['avisos']);
    }

    public function test_una_categoria_que_no_existe_es_una_decision_de_la_persona_y_sin_decision_no_se_crea_el_articulo(): void
    {
        $products = [
            'BO.1' => ['model' => 'BOTIN', 'brand' => 'REVENT', 'price' => 90000, 'codeBars' => ''],
            'XX.2' => ['model' => 'BABUCHA', 'brand' => 'OTRA', 'price' => 0, 'codeBars' => ''],
            'BO.3' => ['model' => 'BOTIN', 'brand' => 'REVENT', 'price' => 90000, 'codeBars' => ''],
            'SM.5' => ['model' => '', 'brand' => 'REVENT', 'price' => 5000, 'codeBars' => ''],
        ];
        $plan = $this->plan([
            $this->row('BO.1', 'BOT-NEGRO-X-BO-38', 1, null, null, 'no_existe', null),
            $this->row('XX.2', 'BAB-ARENA-X-BB-38', 1, null, null, 'no_existe', null),
            $this->row('BO.3', 'BOT-NEGRO-X-BO-39', 1, null, null, 'no_existe', null),
            $this->row('ZZ.4', 'NO-ESTA-EN-CACHE-X-39', 1, null, null, 'no_existe', null),
            $this->row('SM.5', 'SIN-MODELO-X-39', 1, null, null, 'no_existe', null),
        ], [], $products);

        // El sistema no deduce la categoría: la propone y la persona decide (BOTIN → «Botas» solo como recomendación)
        $categories = collect($plan['decisiones']['categorias'])->keyBy('modelo');
        $this->assertSame(2, $categories['BOTIN']['cantidad']);
        $this->assertNull($categories['BOTIN']['sugerida']); // «parecido» no basta: solo se recomienda un nombre exactamente igual
        $this->assertNull($categories['BABUCHA']['sugerida']);
        $this->assertFalse(isset($categories['']), 'sin modelo no es una decisión');
        $noModel = collect($plan['items'])->firstWhere('codigo', 'SM.5');
        $this->assertFalse($noModel['categoria_pendiente']);
        $this->assertArrayNotHasKey('categoria', $noModel['pasos'][0]['datos']);
        $this->assertArrayNotHasKey('categoria', $plan['items'][0]['pasos'][0]['datos']);
        $this->assertStringContainsString('no está en el caché de productos', $plan['manuales'][0]['motivo']);

        $planner = new EffiInventorySyncPlanner(app(EffiWebInventoryComparator::class));

        // Sin decisión: esos artículos no se crean
        $none = $planner->applyDecisions($plan, []);
        $this->assertSame(['SM.5'], array_column($none['items'], 'codigo')); // sin modelo se crea sin categoría, sin preguntar
        $this->assertStringContainsString('Falta tu decisión', $none['manuales'][1]['motivo']);

        // Con decisión: usar existente / crear la categoría (una sola vez por modelo) / sin categoría
        $decided = $planner->applyDecisions($plan, ['modelos' => ['BOTIN' => ['accion' => 'existente', 'categoria' => 9], 'BABUCHA' => ['accion' => 'crear']]]);
        $this->assertSame(9, $decided['items'][0]['pasos'][0]['datos']['categoria']);
        $this->assertSame(9, $decided['items'][2]['pasos'][0]['datos']['categoria']);
        $this->assertSame('BABUCHA', $decided['items'][1]['pasos'][0]['datos']['categoria_nueva']);

        $review = \App\Jobs\EffiInventorySyncJob::review($decided, ['BO.1' => 'ajuste: Effi respondió 400']);
        $this->assertSame(['BO.1', 'XX.2', 'ZZ.4'], array_column($review, 'codigo')); // XX.2 se crea pero sin marca ni precio
        $this->assertStringContainsString('No se pudo completar', $review[0]['motivos'][0]);
    }

    public function test_la_recomendacion_de_categoria_es_por_parecido_del_nombre_y_explica_por_que(): void
    {
        $products = [
            'C.1' => ['model' => 'CHUNKI', 'brand' => '', 'price' => 1000, 'codeBars' => ''],
            'M.1' => ['model' => 'MOCASIN BO', 'brand' => '', 'price' => 1000, 'codeBars' => ''],
            'T.1' => ['model' => 'TACON-FIES', 'brand' => '', 'price' => 1000, 'codeBars' => ''],
            'B.1' => ['model' => 'BOTIN', 'brand' => '', 'price' => 1000, 'codeBars' => ''],
        ];
        $plan = $this->plan(array_map(fn ($c) => $this->row($c, "X-Y-Z-W-{$c}", 1, null, null, 'no_existe', null), array_keys($products)), [], $products);

        $suggested = collect($plan['decisiones']['categorias'])->keyBy('modelo')->map->sugerida;

        $this->assertSame(3, $suggested['CHUNKI']['id']);   // 1 letra de 6 = 83 %
        $this->assertSame(83, $suggested['CHUNKI']['similitud']);
        $this->assertStringContainsString('mal escrito', $suggested['CHUNKI']['motivo']);
        $this->assertSame(10, $suggested['MOCASIN BO']['id']); // la palabra MOCASIN
        $this->assertSame(4, $suggested['TACON-FIES']['id']);  // la palabra TACON
        $this->assertNull($suggested['BOTIN']);                // BOTIN / Botas = 60 %: no llega al umbral, lo decide la persona
    }

    public function test_un_duplicado_se_resuelve_inhabilitando_el_que_elige_la_persona(): void
    {
        $withControl = $this->article(7544, 'HALIBLAN35', 'Sandalia Halifax Blanco Talla 35', 3, 1);
        $without = $this->article(8138, 'HALIBLAN35', 'Sandalia Halifax Blanco Talla 35', 1, 2);
        $plan = $this->plan([$this->row('HALIBLAN35', 'HALIFAX-BLANCO-BERT-PL-35', 1, null, null, 'repetida', 'repetida')], [$withControl, $without]);

        $decision = $plan['decisiones']['duplicados'][0];
        $this->assertSame('8138', $decision['recomendada']); // la que no controla stock por bodega

        $planner = new EffiInventorySyncPlanner(app(EffiWebInventoryComparator::class));

        $this->assertCount(1, $planner->applyDecisions($plan, [])['manuales']);
        $this->assertCount(1, $planner->applyDecisions($plan, ['duplicados' => ['HALIBLAN35' => ['ninguno' => true]]])['manuales']);

        $done = $planner->applyDecisions($plan, ['duplicados' => ['HALIBLAN35' => ['inhabilitar' => '8138']]]);
        $this->assertSame([], $done['manuales']);
        // La otra ya tiene lo de Siigo (1 = 1: nada que ajustar): solo se vacía y se anula la elegida
        $this->assertCount(1, $done['items']);
        $this->assertSame('8138', $done['items'][0]['effi_id']);
        $this->assertSame(['vaciar', 'anular'], array_column($done['items'][0]['pasos'], 'tipo'));
    }

    public function test_el_dropshipping_solo_se_envia_a_los_articulos_que_ya_estan_compartidos(): void
    {
        // La categoría y subcategoría configuradas existen de verdad en el catálogo de Dropshipping de Effi
        $catalog = collect(json_decode(file_get_contents(resource_path('data/effi_dropshipping_categorias.json')), true))->keyBy('id_categoria');
        $this->assertSame('Moda Unisex', $catalog[config('effi.dropshipping.categoria')]['categoria']);
        $subs = collect($catalog[config('effi.dropshipping.categoria')]['subcategorias'])->pluck('subcategoria', 'id_subcategoria');
        $this->assertSame('Calzado', $subs[config('effi.dropshipping.subcategoria')]);
        $this->assertStringStartsWith('Bolsos', $subs[config('effi.dropshipping.subcategoria_bolso')]);

        $shoe = $this->article(20, 'ZEUSNEGR40', 'Mocasin Zeus Negro Talla 40', 1, 1, ['dropshipping' => '1', 'nombre_categoria' => 'Mocasin']);
        $bag = $this->article(21, 'BULGONEGR', 'Bolso Bulgobi Negro Talla U', 1, 1, ['dropshipping' => '1', 'nombre_categoria' => 'Bolso']);
        $own = $this->article(22, 'PROPIO1', 'Baleta Propia Talla 36', 1, 1, ['dropshipping' => '1', 'categoria_dropshipping' => '15', 'subcategoria_dropshipping' => '3']);
        $off = $this->article(23, 'NOCOMP1', 'Baleta No Compartida Talla 36', 1, 1, ['dropshipping' => '0']);
        $plan = $this->plan([
            $this->row('ZE.10.40.1', 'ZEUS-NEGRO-X-MO-40', 1, 1, $shoe, 'sin_control', 'descripcion'),
            $this->row('BU.1', 'BULGO-NEGRO-X-BO-U', 1, 1, $bag, 'sin_control', 'descripcion'),
            $this->row('PROPIO1', 'PROPIA-X-Y-BA-36', 1, 1, $own, 'sin_control', 'referencia'),
            $this->row('NOCOMP1', 'NOCOMP-X-Y-BA-36', 1, 1, $off, 'sin_control', 'referencia'),
        ], [$shoe, $bag, $own, $off]);

        $this->assertArrayNotHasKey('dropshipping', $plan['decisiones']); // nada que decidir
        // Compartido y sin categorías: las que se eligieron solas
        $this->assertSame(['dropshipping' => true, 'categoria_dropshipping' => 22, 'subcategoria_dropshipping' => 3], $plan['items'][0]['dropshipping']);
        $this->assertSame(2, $plan['items'][1]['dropshipping']['subcategoria_dropshipping']); // el bolso va a «Bolsos, Mochilas y Carteras»
        // Compartido y con categorías: las mismas
        $this->assertSame(['dropshipping' => true, 'categoria_dropshipping' => 15, 'subcategoria_dropshipping' => 3], $plan['items'][2]['dropshipping']);
        // No compartido: no se le envía nada
        $this->assertSame([], $plan['items'][3]['dropshipping']);
    }

    public function test_los_articulos_nuevos_se_crean_sin_dropshipping(): void
    {
        $known = $this->article(12, 'LAZOMIEL36', 'LAZO-MIEL-MANI-BA-36', 3, 0);
        $products = [
            'LAZOMIEL36' => ['model' => 'BALETA', 'brand' => '', 'price' => 1000, 'codeBars' => ''],
            'BN.44.35.1000' => ['model' => 'BALETA', 'brand' => '', 'price' => 64990, 'codeBars' => ''],
        ];
        $plan = $this->plan([
            $this->row('LAZOMIEL36', 'LAZO-MIEL-MANI-BA-36', 0, 0, $known, 'igual', 'referencia'),
            $this->row('BN.44.35.1000', 'LAZO-MIEL-MANI-BA-35', 2, null, null, 'no_existe', null),
        ], [$known], $products);

        $data = $plan['items'][0]['pasos'][0]['datos'];
        $this->assertFalse($data['dropshipping']);
        $this->assertArrayNotHasKey('categoria_dropshipping', $data);
        $this->assertArrayNotHasKey('subcategoria_dropshipping', $data);
    }
    public function test_el_ejecutor_manda_el_dropshipping_en_cada_modificacion_de_referencia_o_control(): void
    {
        config(['effi.api_key' => 'k', 'effi.url' => 'https://effi.test/api']);
        Http::fake(['*/articulo' => Http::response(['ok' => true, 'data' => []], 200), '*/ajuste_inventario' => Http::response(['ok' => true], 201)]);
        $ds = ['dropshipping' => true, 'categoria_dropshipping' => 22, 'subcategoria_dropshipping' => 3];

        $plan = ['items' => [
            ['codigo' => 'A', 'effi_id' => '10', 'dropshipping' => $ds, 'pasos' => [['tipo' => 'referencia', 'de' => 'X', 'a' => 'A'], ['tipo' => 'control', 'de' => 1, 'a' => 3]]],
        ]];

        (new EffiInventorySyncRunner(new EffiClient()))->run($plan);

        $puts = collect(Http::recorded())->filter(fn ($p) => $p[0]->method() === 'PUT')->map(fn ($p) => $p[0]->data())->values();
        $this->assertSame(['id' => 10, 'referencia' => 'A'] + $ds, $puts[0]);
        $this->assertSame(['id' => 10, 'gestion_stock' => 3] + $ds, $puts[1]);
    }
    public function test_si_effi_rechaza_el_cambio_de_control_se_devuelven_las_unidades_vaciadas(): void
    {
        config(['effi.api_key' => 'k', 'effi.url' => 'https://effi.test/api']);
        Http::fake([
            '*/articulo' => Http::response(['ok' => false, 'mensaje' => 'no se puede'], 400),
            '*/ajuste_inventario' => Http::response(['ok' => true], 201),
        ]);

        $plan = ['items' => [['codigo' => 'A', 'effi_id' => '10', 'pasos' => [['tipo' => 'vaciar', 'cantidad' => 2.0], ['tipo' => 'control', 'de' => 1, 'a' => 3], ['tipo' => 'ajuste', 'cantidad' => 5.0, 'costo' => 7.0]]]]];

        $result = (new EffiInventorySyncRunner(new EffiClient()))->run($plan);

        $adjustments = collect(Http::recorded())->filter(fn ($p) => basename($p[0]->url()) === 'ajuste_inventario')->map(fn ($p) => $p[0]->data()['conceptos'][0])->values();
        $this->assertSame(['articulo' => 10, 't_ajuste' => 0, 'cantidad' => 2.0], $adjustments[0]);            // se vació
        $this->assertSame(['articulo' => 10, 't_ajuste' => 1, 'cantidad' => 2.0, 'costo' => 7.0], $adjustments[1]); // y se devolvió
        $this->assertCount(2, $adjustments);                                                                       // no se reingresó lo de Siigo
        $this->assertStringContainsString('se devolvieron las unidades vaciadas', $result['errores']['A']);
    }

    public function test_repetir_una_sincronizacion_no_se_confunde_con_el_ajuste_de_la_anterior(): void
    {
        config(['effi.api_key' => 'k', 'effi.url' => 'https://effi.test/api']);
        Http::fake(['*/ajuste_inventario' => Http::response(['ok' => true], 201)]);
        $plan = ['items' => [['codigo' => 'A', 'effi_id' => '10', 'pasos' => [['tipo' => 'ajuste', 'cantidad' => 1.0, 'costo' => 0.0]]]]];

        (new EffiInventorySyncRunner(new EffiClient()))->run($plan);
        (new EffiInventorySyncRunner(new EffiClient()))->run($plan);

        $keys = collect(Http::recorded())->map(fn ($p) => $p[0]->data()['idempotency_key']);
        $this->assertCount(2, $keys->unique()); // mismo contenido en dos ejecuciones distintas = claves distintas
    }

    public function test_una_prueba_con_un_solo_codigo_sincroniza_solo_ese_codigo(): void
    {
        \Illuminate\Support\Facades\Mail::fake();
        \Illuminate\Support\Facades\Cache::flush();
        config(['effi.siigo_warehouse' => 31]);

        $plan = ['ok' => true, 'bodega_effi' => 'B', 'totales' => ['unidades_siigo' => 3, 'unidades_effi' => 1], 'decisiones' => ['categorias' => [], 'duplicados' => []], 'manuales' => [['codigo' => 'X']], 'items' => [
            ['codigo' => 'A', 'descripcion' => 'A', 'siigo' => 2.0, 'effi' => 1.0, 'avisos' => [], 'pasos' => [['tipo' => 'ajuste', 'cantidad' => 1.0, 'costo' => 0.0]]],
            ['codigo' => 'B', 'descripcion' => 'B', 'siigo' => 1.0, 'effi' => 0.0, 'avisos' => [], 'pasos' => [['tipo' => 'ajuste', 'cantidad' => 1.0, 'costo' => 0.0]]],
        ]];
        $planner = \Mockery::mock(EffiInventorySyncPlanner::class);
        $planner->shouldReceive('plan')->andReturn($plan);
        $planner->shouldReceive('applyDecisions')->andReturnArg(0);
        $runner = \Mockery::mock(EffiInventorySyncRunner::class);
        $runner->shouldReceive('run')->once()->withArgs(fn ($p) => array_column($p['items'], 'codigo') === ['B'] && $p['manuales'] === [])
            ->andReturn(['hechos' => ['referencias' => 0, 'creados' => 0, 'controles' => 0, 'vaciados' => 0, 'ingresos' => 1, 'egresos' => 0, 'anulados' => 0], 'errores' => []]);

        (new \App\Jobs\EffiInventorySyncJob([], 'B'))->handle($planner, $runner, app(EffiClient::class), app(\App\Services\Effi\EffiAutoSync::class));

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\EffiSyncResultMail::class, fn ($m) => str_contains($m->render(), 'Prueba con un solo código: B'));
    }

    public function test_el_nombre_del_articulo_en_effi_pasa_a_ser_exactamente_la_descripcion_de_siigo(): void
    {
        $same = $this->article(30, 'IGUAL1', 'AGUILA-BLANCO-SEBA-BA-37', 3, 1);
        $other = $this->article(31, 'OTRO1', 'Baleta Aguila Café Talla 36', 3, 1, ['dropshipping' => '1']);
        $plan = $this->plan([
            $this->row('IGUAL1', 'AGUILA-BLANCO-SEBA-BA-37', 1, 1, $same, 'igual', 'referencia'),
            $this->row('OTRO1', 'AGUILA-CAFE-SEBA-BA-36', 1, 1, $other, 'igual', 'referencia'),
        ], [$same, $other]);

        $this->assertSame(['OTRO1'], array_column($plan['items'], 'codigo')); // el que ya es igual no se toca
        $this->assertSame([['tipo' => 'descripcion', 'de' => 'Baleta Aguila Café Talla 36', 'a' => 'AGUILA-CAFE-SEBA-BA-36']], $plan['items'][0]['pasos']);
        $this->assertSame(1, $plan['resumen']['descripciones_a_cambiar']);

        // El ejecutor lo manda con el Dropshipping si el artículo está compartido
        config(['effi.api_key' => 'k', 'effi.url' => 'https://effi.test/api']);
        Http::fake(['*/articulo' => Http::response(['ok' => true, 'data' => []], 200)]);
        $result = (new EffiInventorySyncRunner(new EffiClient()))->run($plan);

        $put = collect(Http::recorded())->first()[0]->data();
        $this->assertSame(['id' => 31, 'descripcion' => 'AGUILA-CAFE-SEBA-BA-36'] + $plan['items'][0]['dropshipping'], $put);
        $this->assertSame(1, $result['hechos']['descripciones']);
    }

    public function test_las_unidades_negativas_en_effi_se_compensan_y_nada_queda_fuera_del_plan(): void
    {
        $costs = ['p_costo_promedio' => '0'];
        // Sin control, Siigo 0 y Effi -1 (SUSUVINO38): un ingreso de 1 lo deja en cero
        $a = $this->article(40, 'SUSU38', 'SUSU-VINO-X-BA-38', 1, -1, $costs);
        // Sin control, Siigo 3 y Effi -2: control por bodega e ingreso de 5 (3 − (−2))
        $b = $this->article(41, 'NEG36', 'NEG-X-Y-BA-36', 1, -2, $costs);
        $plan = $this->plan([
            $this->row('SUSU38', 'SUSU-VINO-X-BA-38', 0, -1, $a, 'sin_control', 'referencia'),
            $this->row('NEG36', 'NEG-X-Y-BA-36', 3, -2, $b, 'sin_control', 'referencia'),
        ], [$a, $b]);

        $this->assertSame([['tipo' => 'ajuste', 'cantidad' => 1.0, 'costo' => 0.0]], $plan['items'][0]['pasos']);
        $this->assertSame(['control', 'ajuste'], array_column($plan['items'][1]['pasos'], 'tipo'));
        $this->assertSame(5.0, $plan['items'][1]['pasos'][1]['cantidad']);
        $this->assertSame([], $plan['manuales']);

        // Un caso con diferencia que el plan no sabe resolver aparece en «no se tocan», no desaparece
        $c = $this->article(42, 'RARO1', 'RARO-X-Y-BA-36', 1, 0, $costs);
        $unknown = $this->plan([$this->row('RARO1', 'RARO-X-Y-BA-36', 0, 0, $c, 'sin_control', 'referencia')], [$c]);
        $this->assertSame([], $unknown['items']);
        $this->assertStringContainsString('no hay un cambio automático definido', $unknown['manuales'][0]['motivo']);
    }

    public function test_una_referencia_repetida_no_genera_egresos_por_ser_lo_que_effi_tiene_y_siigo_no(): void
    {
        $a = $this->article(7547, 'HALIBLAN38', 'Sandalia Halifax Blanco Talla 38', 3, 2, ['stock_empresa' => '2']);
        $b = $this->article(8141, 'HALIBLAN38', 'Sandalia Halifax Blanco Talla 38', 1, 3, ['stock_empresa' => '3']);

        $effi = \Mockery::mock(EffiClient::class);
        $effi->shouldReceive('articles')->andReturn([$a, $b]);
        $effi->shouldReceive('warehouseName')->andReturn('Bodega / Principal');
        $reports = \Mockery::mock(\App\Services\Siigo\SiigoReportService::class);
        $reports->shouldReceive('table')->andReturn([['Code' => 'HALIBLAN38', 'Description' => 'HALIFAX-BLANCO-BERT-PL-38', 'QuantityBalance' => 2]]);
        $inventory = \Mockery::mock(\App\Services\Siigo\SiigoInventoryReportService::class);
        $inventory->shouldReceive('stockBody')->andReturn([]);

        $comparison = (new EffiWebInventoryComparator($effi, $reports, $inventory))->compare();

        // Una sola fila (repetida): los dos artículos no se cuentan además como «Effi tiene y Siigo no»
        $this->assertSame(['repetida'], array_column($comparison['filas'], 'estado'));
    }

    public function test_dos_items_con_el_mismo_codigo_no_van_juntos_en_el_plan(): void
    {
        $x = $this->article(1, 'DUP1', 'A', 3, 1);
        $y = $this->article(2, 'DUP1', 'A', 3, 2);
        $plan = $this->plan([
            $this->row('DUP1', 'A-B-C-D-36', 0, 1, $x, 'diferencia', 'referencia'),
            $this->row('DUP1', 'A-B-C-D-36', 0, 2, $y, 'diferencia', 'referencia'),
        ], [$x, $y]);

        $this->assertSame(['DUP1'], array_column($plan['items'], 'codigo'));
        $this->assertStringContainsString('más de una vez', $plan['manuales'][0]['motivo']);
    }

    public function test_el_ejecutor_crea_la_categoria_solo_si_no_existe_y_anula_despues_de_vaciar(): void
    {
        config(['effi.api_key' => 'k', 'effi.url' => 'https://effi.test/api', 'effi.sucursal' => 1, 'effi.bodega' => 1]);
        Http::fake([
            '*/categoria_articulo*' => Http::sequence()
                ->push(['ok' => true, 'data' => [['id' => 9, 'nombre' => 'Botas']]], 200)   // BOTAS ya existe: no se crea
                ->push(['ok' => true, 'data' => [['id' => 9, 'nombre' => 'Botas']]], 200)   // BABUCHA no existe
                ->push(['ok' => true, 'data' => ['id' => 11]], 201),                          // se crea una vez
            '*/articulo' => Http::response(['ok' => true, 'data' => ['id' => '900']], 201),
            '*/ajuste_inventario' => Http::response(['ok' => true], 201),
        ]);

        $plan = ['items' => [
            ['codigo' => 'A', 'effi_id' => null, 'pasos' => [['tipo' => 'crear', 'datos' => ['descripcion' => 'A', 'referencia' => 'A', 'categoria_nueva' => 'Botas']]]],
            ['codigo' => 'B', 'effi_id' => null, 'pasos' => [['tipo' => 'crear', 'datos' => ['descripcion' => 'B', 'referencia' => 'B', 'categoria_nueva' => 'BABUCHA']]]],
            ['codigo' => 'C', 'effi_id' => null, 'pasos' => [['tipo' => 'crear', 'datos' => ['descripcion' => 'C', 'referencia' => 'C', 'categoria_nueva' => 'BABUCHA']]]],
            ['codigo' => 'D (inhabilitar #8138)', 'effi_id' => '8138', 'pasos' => [['tipo' => 'vaciar', 'cantidad' => 2.0], ['tipo' => 'anular']]],
        ]];

        $result = (new EffiInventorySyncRunner(new EffiClient()))->run($plan);

        $this->assertSame([], $result['errores']);
        $this->assertSame(3, $result['hechos']['creados']);
        $this->assertSame(1, $result['hechos']['anulados']);

        $recorded = collect(Http::recorded())->map(fn ($p) => $p[0]->method() . ' ' . basename($p[0]->url()))->all();
        $this->assertSame(1, count(array_filter($recorded, fn ($c) => $c === 'POST categoria_articulo')), 'la categoría BABUCHA se crea una sola vez');
        $this->assertSame('DELETE articulo', end($recorded));
        $this->assertLessThan(array_search('DELETE articulo', $recorded), array_search('POST ajuste_inventario', $recorded));

        $articles = collect(Http::recorded())->filter(fn ($p) => $p[0]->method() === 'POST' && basename($p[0]->url()) === 'articulo')->map(fn ($p) => $p[0]->data());
        $this->assertSame([9, 11, 11], $articles->pluck('categoria')->all());
        $this->assertArrayNotHasKey('categoria_nueva', $articles->first());
    }
    public function test_lo_que_effi_tiene_y_siigo_no_se_saca_y_lo_repetido_queda_para_revision(): void
    {
        $a = $this->article(13, 'SOLOEFFI38', 'Baleta Solo Effi Talla 38', 3, 4);
        $plan = $this->plan([
            $this->row('SOLOEFFI38', 'Baleta Solo Effi Talla 38', 0, 4, $a, 'diferencia', 'referencia'),
            $this->row('HALIBLAN35', 'HALIFAX-BLANCO-BERT-PL-35', 1, null, null, 'repetida', 'repetida'),
        ], [$a]);

        $this->assertSame(-4.0, $plan['items'][0]['pasos'][0]['cantidad']);
        $this->assertCount(1, $plan['manuales']);
    }

    public function test_no_cambia_una_referencia_si_el_codigo_de_siigo_ya_es_de_otro_articulo(): void
    {
        $a = $this->article(14, 'VIEJA35', 'Baleta X Blanco Talla 35', 3, 1);
        $other = $this->article(15, 'BA.10.35.1012', 'Otro', 3, 0);
        $plan = $this->plan([$this->row('BA.10.35.1012', 'X-BLANCO-SEBA-BA-35', 1, 1, $a, 'igual', 'descripcion')], [$a, $other]);

        $this->assertSame([], $plan['items']);
        $this->assertStringContainsString('ya es la referencia de otro artículo', $plan['manuales'][0]['motivo']);
    }

    public function test_el_ejecutor_sigue_el_orden_y_manda_ingresos_con_costo_y_egresos_sin_costo(): void
    {
        config(['effi.api_key' => 'k', 'effi.url' => 'https://effi.test/api', 'effi.sucursal' => 1, 'effi.bodega' => 1]);
        Http::fake([
            '*/articulo' => Http::response(['ok' => true, 'data' => ['id' => '900']], 201),
            '*/ajuste_inventario' => Http::response(['ok' => true, 'data' => []], 201),
        ]);

        $plan = ['items' => [
            ['codigo' => 'A', 'effi_id' => '10', 'pasos' => [['tipo' => 'referencia', 'de' => 'X', 'a' => 'A'], ['tipo' => 'vaciar', 'cantidad' => 2.0], ['tipo' => 'control', 'de' => 1, 'a' => 3], ['tipo' => 'ajuste', 'cantidad' => 3.0, 'costo' => 100.0]]],
            ['codigo' => 'B', 'effi_id' => null, 'pasos' => [['tipo' => 'crear', 'datos' => ['descripcion' => 'N', 'referencia' => 'B']], ['tipo' => 'ajuste', 'cantidad' => 1.0, 'costo' => 0.0]]],
            ['codigo' => 'C', 'effi_id' => '30', 'pasos' => [['tipo' => 'ajuste', 'cantidad' => -4.0, 'costo' => 0.0]]],
        ]];

        $result = (new EffiInventorySyncRunner(new EffiClient()))->run($plan);

        $this->assertSame([], $result['errores']);
        $this->assertSame(['referencias' => 1, 'descripciones' => 0, 'creados' => 1, 'vaciados' => 1, 'controles' => 1, 'ingresos' => 2, 'egresos' => 1, 'anulados' => 0], $result['hechos']);

        $calls = collect(Http::recorded())->map(fn ($pair) => $pair[0]->method() . ' ' . basename($pair[0]->url()));
        // referencia (A), crear (B), A artículo por artículo (vaciar, control, reingresar), y el resto en lote
        $this->assertSame(['PUT articulo', 'POST articulo', 'POST ajuste_inventario', 'PUT articulo', 'POST ajuste_inventario', 'POST ajuste_inventario'], $calls->all());

        $bodies = collect(Http::recorded())->filter(fn ($p) => basename($p[0]->url()) === 'ajuste_inventario')->map(fn ($p) => $p[0]->data())->values();
        $this->assertSame(['articulo' => 10, 't_ajuste' => 0, 'cantidad' => 2.0], $bodies[0]['conceptos'][0]);                    // vaciar
        $this->assertSame(['articulo' => 10, 't_ajuste' => 1, 'cantidad' => 3.0, 'costo' => 100.0], $bodies[1]['conceptos'][0]);  // reingresar lo de Siigo
        $this->assertSame(['articulo' => 900, 't_ajuste' => 1, 'cantidad' => 1.0, 'costo' => 0.0], $bodies[2]['conceptos'][0]);   // artículo nuevo
        $this->assertSame(['articulo' => 30, 't_ajuste' => 0, 'cantidad' => 4.0], $bodies[2]['conceptos'][1]);                    // egreso
        $this->assertSame(1, $bodies[2]['sucursal']);
        $this->assertMatchesRegularExpression('/^sync-[0-9a-f]{8}-[a-z]?[0-9a-f]{20}$/', $bodies[2]['idempotency_key']);
        // Effi rechaza (400) las claves de menos de 8 o más de 36 caracteres
        foreach ($bodies as $body) {
            $this->assertGreaterThanOrEqual(8, strlen($body['idempotency_key']));
            $this->assertLessThanOrEqual(36, strlen($body['idempotency_key']));
        }
        $this->assertNotSame($bodies[0]['idempotency_key'], $bodies[1]['idempotency_key']);
    }

    public function test_si_falla_un_paso_ese_codigo_se_salta_lo_que_sigue_y_los_demas_continuan(): void
    {
        config(['effi.api_key' => 'k', 'effi.url' => 'https://effi.test/api']);
        Http::fake([
            '*/articulo' => Http::sequence()->push(['ok' => false, 'mensaje' => 'referencia duplicada'], 400)->push(['ok' => true, 'data' => ['id' => '1']], 200),
            '*/ajuste_inventario' => Http::response(['ok' => true], 201),
        ]);

        $plan = ['items' => [
            ['codigo' => 'A', 'effi_id' => '10', 'pasos' => [['tipo' => 'referencia', 'de' => 'X', 'a' => 'A'], ['tipo' => 'ajuste', 'cantidad' => 1.0, 'costo' => 0.0]]],
            ['codigo' => 'B', 'effi_id' => '20', 'pasos' => [['tipo' => 'referencia', 'de' => 'Y', 'a' => 'B'], ['tipo' => 'ajuste', 'cantidad' => 1.0, 'costo' => 0.0]]],
        ]];

        $result = (new EffiInventorySyncRunner(new EffiClient()))->run($plan);

        $this->assertSame(['A'], array_keys($result['errores']));
        $this->assertStringContainsString('referencia duplicada', $result['errores']['A']);
        $this->assertSame(1, $result['hechos']['ingresos']);
    }

    public function test_la_sincronizacion_confirmada_va_a_su_cola_con_las_decisiones_de_la_persona(): void
    {
        \Illuminate\Support\Facades\Queue::fake();
        $this->withoutMiddleware();
        \Illuminate\Support\Facades\Cache::forget(\App\Jobs\EffiInventorySyncJob::STATUS_KEY);
        config(['effi.sync_enabled' => true, 'effi.queue' => 'sync-siigo-effi']);

        $this->postJson(route('siigo.effi_sync_sync'), [
            'confirmacion' => 'SINCRONIZAR',
            'decisiones' => ['modelos' => ['CHUNKI' => ['accion' => 'existente', 'categoria' => 3], 'MALO' => ['accion' => 'inventada']], 'duplicados' => ['HALIBLAN35' => ['inhabilitar' => '8138']]],
        ])->assertOk();

        \Illuminate\Support\Facades\Queue::assertPushedOn('sync-siigo-effi', \App\Jobs\EffiInventorySyncJob::class);
    }

    public function test_la_sincronizacion_esta_apagada_por_defecto_y_pide_confirmacion(): void
    {
        $this->withoutMiddleware();

        config(['effi.sync_enabled' => false]);
        $this->postJson(route('siigo.effi_sync_sync'), ['confirmacion' => 'SINCRONIZAR'])->assertStatus(403);

        config(['effi.sync_enabled' => true]);
        $this->postJson(route('siigo.effi_sync_sync'), ['confirmacion' => 'si'])->assertStatus(422);
    }
}
