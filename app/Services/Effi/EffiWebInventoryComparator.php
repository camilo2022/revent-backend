<?php

namespace App\Services\Effi;

use App\Services\Siigo\SiigoInventoryReportService;
use App\Services\Siigo\SiigoReportService;

/**
 * Cuadre de la bodega WEB de Siigo contra la bodega de Effi, código por código. Solo consulta: no hace ajustes.
 *
 * El artículo de Effi se busca por referencia (= código de Siigo); si no aparece, por la descripción
 * (referencia, color y talla de Siigo dentro del nombre de Effi, que debe terminar en "Talla N").
 */
class EffiWebInventoryComparator
{
    /** Gestión de stock de Effi que lleva existencias por bodega (la única que se puede ajustar por bodega). */
    private const CONTROL_POR_BODEGA = 3;

    /** Artículos de Effi de la última consulta (para armar el plan de sincronización sin pedirlos otra vez). */
    private array $articles = [];

    public function __construct(
        private EffiClient $effi,
        private SiigoReportService $reports,
        private SiigoInventoryReportService $inventoryReport
    ) {
    }

    public function compare(): array
    {
        $sucursal = config('effi.sucursal');
        $bodega = config('effi.bodega');
        $warehouse = config('effi.siigo_warehouse');

        $siigo = $this->reports->table($this->inventoryReport->stockBody($warehouse), 'Error consultando el inventario de Siigo: ') ?? [];
        $articles = $this->articles = $this->effi->articles();

        // Sin permiso sobre "Artículos - Stock disponible" (función 156) Effi no devuelve stock_empresa ni el stock por bodega
        $stockReadable = collect($articles)->contains(fn ($a) => array_key_exists('stock_empresa', $a));

        $byReference = collect($articles)->groupBy(fn ($a) => mb_strtoupper(trim((string) ($a['referencia'] ?? ''))));
        $byDescription = collect($articles)->map(fn ($a) => ['texto' => $this->normalize($a['descripcion'] ?? ''), 'articulo' => $a]);

        $rows = [];
        $used = [];

        foreach ($siigo as $line) {
            $code = mb_strtoupper(trim((string) $line['Code']));
            [$article, $match] = $this->findArticle($code, (string) $line['Description'], $byReference, $byDescription);

            if ($article) {
                $used[$article['id']] = true;
            }

            // Referencia repetida en Effi: ninguno de los dos artículos es «lo que Effi tiene y Siigo no» (se resuelve con la decisión de la persona)
            if ($match === 'repetida') {
                foreach ($byReference->get($code) as $duplicate) {
                    $used[$duplicate['id']] = true;
                }
            }

            $rows[] = $this->row($code, (string) $line['Description'], (float) $line['QuantityBalance'], $article, $match, $stockReadable, $sucursal, $bodega);
        }

        // Lo que Effi tiene en la bodega y Siigo no (Siigo solo trae los códigos con saldo)
        if ($stockReadable) {
            foreach ($articles as $article) {
                $stock = $this->effiStock($article, $sucursal, $bodega);

                if (!isset($used[$article['id']]) && $stock) {
                    $rows[] = $this->row(mb_strtoupper((string) $article['referencia']), (string) $article['descripcion'], 0, $article, 'referencia', true, $sucursal, $bodega);
                }
            }
        }

        usort($rows, fn ($a, $b) => strcmp($a['codigo'], $b['codigo']));

        return [
            'fecha' => now()->format('d/m/Y h:i A'),
            'bodega_siigo' => $warehouse,
            'bodega_effi' => $this->effi->warehouseName($sucursal, $bodega) ?? "Sucursal {$sucursal} / Bodega {$bodega}",
            'stock_effi_disponible' => $stockReadable,
            'resumen' => $this->summary($rows, $stockReadable),
            'filas' => $rows,
        ];
    }

    public function articles(): array
    {
        return $this->articles;
    }

    private function findArticle(string $code, string $description, $byReference, $byDescription): array
    {
        $found = $byReference->get($code);

        if ($found && $found->count() === 1) {
            return [$found->first(), 'referencia'];
        }

        if ($found && $found->count() > 1) {
            return [null, 'repetida'];
        }

        // Descripción de Siigo: REFERENCIA-COLOR-...-TALLA
        $parts = explode('-', $description);

        if (count($parts) < 3) {
            return [null, null];
        }

        [$reference, $color, $size] = [$this->normalize($parts[0]), $this->normalize($parts[1]), $this->normalize(end($parts))];

        $candidates = $byDescription->filter(fn ($a) => str_contains($a['texto'], $reference)
            && str_contains($a['texto'], $color)
            && str_ends_with($a['texto'], "TALLA {$size}"));

        return $candidates->count() === 1 ? [$candidates->first()['articulo'], 'descripcion'] : [null, null];
    }

    private function row(string $code, string $description, float $siigo, ?array $article, ?string $match, bool $stockReadable, int $sucursal, int $bodega): array
    {
        $effi = ($article && $stockReadable) ? $this->effiStock($article, $sucursal, $bodega) : null;

        $status = match (true) {
            $match === 'repetida' => 'repetida',
            !$article => 'no_existe',
            (int) $article['gestion_stock'] !== self::CONTROL_POR_BODEGA => 'sin_control',
            !$stockReadable => 'sin_stock_effi',
            $effi == $siigo => 'igual',
            default => 'diferencia',
        };

        return [
            'codigo' => $code,
            'descripcion' => $description,
            'siigo' => $siigo,
            'effi' => $effi,
            'diferencia' => $effi === null ? null : $siigo - $effi,
            'effi_id' => $article['id'] ?? null,
            'effi_gestion' => $article ? (int) $article['gestion_stock'] : null,
            'effi_referencia' => $article['referencia'] ?? null,
            'effi_descripcion' => $article['descripcion'] ?? null,
            'cruce' => $match,
            'estado' => $status,
        ];
    }

    private function effiStock(array $article, int $sucursal, int $bodega): float
    {
        foreach ($article['stock_bodegas'] ?? [] as $stock) {
            if ((int) $stock['sucursal'] === $sucursal && (int) $stock['bodega'] === $bodega) {
                return (float) $stock['stock'];
            }
        }

        return 0;
    }

    private function summary(array $rows, bool $stockReadable): array
    {
        $rows = collect($rows);
        $count = fn (string $status) => $rows->where('estado', $status);

        return [
            'codigos_siigo' => $rows->where('siigo', '>', 0)->count(),
            'unidades_siigo' => $rows->sum('siigo'),
            'unidades_effi' => $stockReadable ? $rows->sum('effi') : null,
            'iguales' => $count('igual')->count(),
            'con_diferencia' => $count('diferencia')->count(),
            'no_existen' => $count('no_existe')->count(),
            'unidades_no_existen' => $count('no_existe')->sum('siigo'),
            'sin_control' => $count('sin_control')->count(),
            'repetidas' => $count('repetida')->count(),
            'por_descripcion' => $rows->where('cruce', 'descripcion')->count(),
        ];
    }

    /** Mayúsculas, sin tildes ni signos, para comparar nombres. */
    public function normalize(string $text): string
    {
        $text = mb_strtoupper(trim($text));
        $text = strtr($text, ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N']);

        return trim(preg_replace('/\s+/', ' ', preg_replace('/[^A-Z0-9 ]/', '', $text)));
    }
}
