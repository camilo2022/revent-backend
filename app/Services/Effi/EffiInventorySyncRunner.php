<?php

namespace App\Services\Effi;

use Illuminate\Support\Facades\Log;

/**
 * Ejecuta en Effi el plan de EffiInventorySyncPlanner. ESCRIBE en Effi: solo se llama desde la sincronización confirmada.
 * Fases: referencias, creación de artículos, vaciado, cambio de control y ajustes finales.
 * Si un paso de un código falla, ese código se salta lo que sigue y queda en la lista de errores; los demás continúan.
 */
class EffiInventorySyncRunner
{
    private const BATCH = 50;

    /** Effi acepta claves de 8 a 36 caracteres: «sync-» (5) + ejecución (8) + «-» + etiqueta (1) + contenido (20) = 35. */
    /** Identifica esta ejecución: las claves de los ajustes la incluyen para que repetir una sincronización más tarde no se confunda con un ajuste anterior igual. */
    private string $runId = '';

    public function __construct(private EffiClient $effi)
    {
    }

    /** @param callable(string,array):void|null $progress se llama con la fase y los contadores a medida que avanza */
    public function run(array $plan, ?callable $progress = null): array
    {
        $progress ??= fn () => null;
        $this->runId = bin2hex(random_bytes(4));
        $ids = [];
        $failed = [];
        $done = ['referencias' => 0, 'descripciones' => 0, 'creados' => 0, 'vaciados' => 0, 'controles' => 0, 'ingresos' => 0, 'egresos' => 0, 'anulados' => 0];

        foreach ($plan['items'] as $item) {
            $ids[$item['codigo']] = $item['effi_id'];
        }

        $each = function (string $type, callable $action, ?array $items = null) use ($plan, &$failed) {
            foreach ($items ?? $plan['items'] as $item) {
                foreach ($item['pasos'] as $step) {
                    if ($step['tipo'] === $type && !isset($failed[$item['codigo']])) {
                        try {
                            $action($item, $step);
                        } catch (\Throwable $e) {
                            $failed[$item['codigo']] = $type . ': ' . $e->getMessage();
                            Log::warning('Sincronización Effi: paso fallido', ['codigo' => $item['codigo'], 'paso' => $type, 'error' => $e->getMessage()]);
                        }
                    }
                }
            }
        };

        $progress('Cambiando referencias', $done);
        $each('referencia', function ($item, $step) use (&$done, &$ids) {
            $this->effi->updateArticle(['id' => (int) $ids[$item['codigo']], 'referencia' => $step['a']] + ($item['dropshipping'] ?? []));
            $done['referencias']++;
        });

        $progress('Cambiando nombres al de Siigo', $done);
        $each('descripcion', function ($item, $step) use (&$done, &$ids) {
            $this->effi->updateArticle(['id' => (int) $ids[$item['codigo']], 'descripcion' => $step['a']] + ($item['dropshipping'] ?? []));
            $done['descripciones']++;
        });

        $progress('Creando artículos', $done);
        $categories = [];
        $each('crear', function ($item, $step) use (&$done, &$ids, &$categories) {
            $data = $step['datos'];

            if (isset($data['categoria_nueva'])) {
                $data['categoria'] = $categories[$data['categoria_nueva']] ??= $this->ensureCategory($data['categoria_nueva']);
                unset($data['categoria_nueva']);
            }

            $response = $this->effi->createArticle($data);
            $ids[$item['codigo']] = $response['data']['id'] ?? throw new \RuntimeException('Effi no devolvió el id del artículo creado.');
            $done['creados']++;
        });

        // Los artículos «sin control» con unidades se hacen uno por uno (vaciar → cambiar control → reingresar): como casi todos
        // están vinculados a Shopify, así el stock de la tienda queda en 0 solo unos segundos por artículo.
        $oneByOne = array_values(array_filter($plan['items'], fn ($i) => collect($i['pasos'])->contains('tipo', 'vaciar')));
        $rest = array_values(array_filter($plan['items'], fn ($i) => !collect($i['pasos'])->contains('tipo', 'vaciar')));

        foreach ($oneByOne as $n => $item) {
            $progress('Vaciando, cambiando el control y reingresando, artículo por artículo (' . ($n + 1) . ' de ' . count($oneByOne) . ')', $done);
            $this->resync($item, $ids, $failed, $done);
        }

        $progress('Cambiando el control de stock', $done);
        $each('control', function ($item, $step) use (&$done, &$ids) {
            $this->effi->updateArticle(['id' => (int) $ids[$item['codigo']], 'gestion_stock' => $step['a']] + ($item['dropshipping'] ?? []));
            $done['controles']++;
        }, $rest);

        $progress('Ajustando inventario', $done);
        $this->adjust(['items' => $rest], $ids, $failed, 'ajuste', $done, $progress);

        $progress('Anulando artículos duplicados', $done);
        $each('anular', function ($item, $step) use (&$done, &$ids) {
            $this->effi->annulArticle((int) $ids[$item['codigo']]);
            $done['anulados']++;
        });

        return ['hechos' => $done, 'errores' => $failed];
    }

    /**
     * Un artículo sin control por bodega: vaciar, pasar a control por bodega y reingresar lo de Siigo, uno detrás de otro.
     * Si Effi rechaza el cambio de control cuando ya se vació, se devuelve lo vaciado para que el artículo no quede en cero.
     */
    private function resync(array $item, array $ids, array &$failed, array &$done): void
    {
        $code = $item['codigo'];
        $id = (int) ($ids[$code] ?? 0);

        if (isset($failed[$code]) || !$id) {
            return;
        }

        $emptied = 0.0;
        $cost = (float) (collect($item['pasos'])->firstWhere('tipo', 'ajuste')['costo'] ?? 0);

        try {
            foreach ($item['pasos'] as $step) {
                if ($step['tipo'] === 'vaciar') {
                    $this->send([['codigo' => $code, 'concepto' => ['articulo' => $id, 't_ajuste' => 0, 'cantidad' => $step['cantidad']]]], 'v');
                    $emptied = (float) $step['cantidad'];
                    $done['vaciados']++;
                } elseif ($step['tipo'] === 'control') {
                    try {
                        $this->effi->updateArticle(['id' => $id, 'gestion_stock' => $step['a']] + ($item['dropshipping'] ?? []));
                        $done['controles']++;
                    } catch (\Throwable $e) {
                        if ($emptied > 0) {
                            $this->send([['codigo' => $code, 'concepto' => ['articulo' => $id, 't_ajuste' => 1, 'cantidad' => $emptied, 'costo' => $cost]]], 'r');
                            $done['vaciados']--;
                        }

                        throw new \RuntimeException('control: ' . $e->getMessage() . ($emptied > 0 ? ' (se devolvieron las unidades vaciadas)' : ''));
                    }
                } elseif ($step['tipo'] === 'ajuste') {
                    $quantity = (float) $step['cantidad'];
                    $this->send([['codigo' => $code, 'concepto' => array_filter([
                        'articulo' => $id, 't_ajuste' => $quantity > 0 ? 1 : 0, 'cantidad' => abs($quantity), 'costo' => $quantity > 0 ? ($step['costo'] ?? 0) : null,
                    ], fn ($v) => $v !== null)]], 'i');
                    $done[$quantity > 0 ? 'ingresos' : 'egresos']++;
                }
            }
        } catch (\Throwable $e) {
            $failed[$code] = $e->getMessage();
            Log::warning('Sincronización Effi: artículo fallido', ['codigo' => $code, 'error' => $e->getMessage()]);
        }
    }

    /** Un ajuste por lotes de 50 conceptos; si un lote falla se reintenta concepto por concepto para aislar el que falla. */
    private function adjust(array $plan, array $ids, array &$failed, string $type, array &$done, callable $progress): void
    {
        $concepts = [];

        foreach ($plan['items'] as $item) {
            foreach ($item['pasos'] as $step) {
                if ($step['tipo'] !== $type || isset($failed[$item['codigo']]) || empty($ids[$item['codigo']])) {
                    continue;
                }

                $quantity = $type === 'vaciar' ? -$step['cantidad'] : $step['cantidad'];

                $concepts[] = ['codigo' => $item['codigo'], 'concepto' => array_filter([
                    'articulo' => (int) $ids[$item['codigo']],
                    't_ajuste' => $quantity > 0 ? 1 : 0,
                    'cantidad' => abs($quantity),
                    'costo' => $quantity > 0 ? ($step['costo'] ?? 0) : null,
                ], fn ($v) => $v !== null)];
            }
        }

        foreach (array_chunk($concepts, self::BATCH) as $batch) {
            try {
                $this->send($batch);
                $this->count($batch, $done, $type);
            } catch (\Throwable) {
                foreach ($batch as $one) {
                    try {
                        $this->send([$one]);
                        $this->count([$one], $done, $type);
                    } catch (\Throwable $e) {
                        $failed[$one['codigo']] = "ajuste: {$e->getMessage()}";
                    }
                }
            }

            $progress($type === 'vaciar' ? 'Vaciando artículos sin control por bodega' : 'Ajustando inventario', $done);
        }
    }

    /** Id de la categoría con ese nombre; solo se crea si no existe (Effi no controla duplicados por nombre). */
    private function ensureCategory(string $name): int
    {
        $normalize = fn (string $text) => mb_strtoupper(trim(\Illuminate\Support\Str::ascii($text)));
        $existing = array_search($normalize($name), array_map($normalize, $this->effi->catalog('categoria_articulo')), true);

        if ($existing !== false) {
            return (int) $existing;
        }

        return (int) ($this->effi->createCategory($name)['data']['id'] ?? throw new \RuntimeException('Effi no devolvió el id de la categoría creada.'));
    }

    private function count(array $batch, array &$done, string $type): void
    {
        foreach ($batch as $one) {
            $done[$type === 'vaciar' ? 'vaciados' : ($one['concepto']['t_ajuste'] ? 'ingresos' : 'egresos')]++;
        }
    }

    private function send(array $batch, string $tag = ''): void
    {
        $concepts = array_column($batch, 'concepto');

        $this->effi->createAdjustment([
            // Misma ejecución y mismo contenido = misma clave: si hay un reintento por un corte de red, no se duplica el ajuste
            'idempotency_key' => 'sync-' . $this->runId . '-' . $tag . substr(sha1(json_encode($concepts)), 0, 20),
            'sucursal' => (int) config('effi.sucursal'),
            'bodega' => (int) config('effi.bodega'),
            'observacion' => 'SINCRONIZACION CON SIIGO (BODEGA WEB)',
            'conceptos' => $concepts,
        ]);
    }
}
