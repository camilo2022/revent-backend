<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Catálogo de colores + utilidades para explorar el directorio
 * storage/app/public/products con la estructura:
 *
 *   products/{REFERENCIA}/*.webp          (fotos del producto)
 *   products/{REFERENCIA}/{COLOR}/*.webp  (fotos por color; COLOR = nombre del color, espacios como guion)
 */
class ProductPhotoService
{
    public const DISK = 'public';
    public const BASE_PATH = 'products';

    private const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'webp'];

    private ?array $coloresPorCarpeta = null;

    // ------------------------------------------------------------------
    // Colores
    // ------------------------------------------------------------------

    public function colores(): array
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

    /** Nombre de carpeta de un color: "ORO ROSA" => "ORO-ROSA". */
    public function carpetaDeColor(array $color): string
    {
        return $this->normalizarNombre($color['nombre']);
    }

    /** Colores indexados por nombre de carpeta, p. ej. ['NEGRO' => [...], 'ORO-ROSA' => [...]]. */
    public function coloresPorCarpeta(): array
    {
        return $this->coloresPorCarpeta ??= collect($this->colores())
            ->keyBy(fn ($color) => $this->carpetaDeColor($color))
            ->all();
    }

    public function colorPorCarpeta(string $carpeta): ?array
    {
        return $this->coloresPorCarpeta()[$carpeta] ?? null;
    }

    // ------------------------------------------------------------------
    // Nombres y rutas
    // ------------------------------------------------------------------

    public function normalizarNombre(string $valor): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', '-', trim($valor)), 'UTF-8');
    }

    public function nombreValido(string $valor): bool
    {
        return preg_match('/^[A-ZÑ0-9_-]+$/u', $valor) === 1;
    }

    /**
     * Convierte una ruta lógica ("", "VAMPELT", "VAMPELT/99") en datos seguros.
     * Devuelve null si la ruta no es válida (traversal, color inexistente, más de 2 niveles).
     *
     * level 0 = products | level 1 = producto | level 2 = color
     */
    public function resolverRuta(?string $path): ?array
    {
        $path = trim(str_replace('\\', '/', (string) $path), '/');
        $parts = $path === '' ? [] : explode('/', $path);

        if (count($parts) > 2) {
            return null;
        }

        $referencia = $parts[0] ?? null;
        $color = $parts[1] ?? null;

        if ($referencia !== null && !$this->nombreValido($referencia)) {
            return null;
        }

        if ($color !== null && $this->colorPorCarpeta($color) === null) {
            return null;
        }

        return [
            'level' => count($parts),
            'referencia' => $referencia,
            'color' => $color,
            'path' => implode('/', $parts),
            'relative' => implode('/', array_merge([self::BASE_PATH], $parts)),
        ];
    }

    public function esImagen(string $filename): bool
    {
        return in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), self::IMAGE_EXT, true);
    }

    // ------------------------------------------------------------------
    // Explorador
    // ------------------------------------------------------------------

    /** Lista carpetas e imágenes de una ruta resuelta. Null si la carpeta no existe. */
    public function listar(array $info): ?array
    {
        $disk = Storage::disk(self::DISK);
        $dir = $info['relative'];

        if (!$disk->exists($dir)) {
            if ($info['level'] !== 0) {
                return null;
            }
            $disk->makeDirectory($dir);
        }

        $level = $info['level'];
        $nombres = collect($disk->directories($dir))->map(fn ($d) => basename($d));
        $folders = collect();

        if ($level === 0) {
            $folders = $nombres
                ->filter(fn ($n) => $this->nombreValido($n))
                ->sort(SORT_NATURAL)
                ->values()
                ->map(fn ($n) => [
                    'type' => 'product',
                    'name' => $n,
                    'label' => $n,
                    'path' => $n,
                    'counts' => $this->contar("{$dir}/{$n}"),
                ]);
        } elseif ($level === 1) {
            $folders = $nombres
                ->filter(fn ($n) => $this->colorPorCarpeta($n) !== null)
                ->sortBy(fn ($n) => $n)
                ->values()
                ->map(function ($n) use ($dir, $info) {
                    $color = $this->colorPorCarpeta($n);

                    return [
                        'type' => 'color',
                        'name' => $n,
                        'label' => $color['nombre'],
                        'path' => "{$info['referencia']}/{$n}",
                        'hex' => $color['hex'],
                        'macro' => $color['macrocategoria'],
                        'counts' => $this->contar("{$dir}/{$n}"),
                    ];
                });
        }

        $files = collect();

        if ($level >= 1) {
            $files = collect($disk->files($dir))
                ->filter(fn ($f) => $this->esImagen($f))
                ->map(fn ($f) => [
                    'name' => basename($f),
                    'url' => $disk->url($f),
                    'size' => $disk->size($f),
                ])
                ->values();
        }

        return [
            'path' => $info['path'],
            'level' => $level,
            'breadcrumb' => $this->breadcrumb($info),
            'folders' => $folders->values(),
            'files' => $files,
            'available_colors' => $level === 1
                ? $this->coloresDisponibles($nombres->all())
                : [],
        ];
    }

    /**
     * Crea una carpeta: en products = producto nuevo; dentro de un producto = color.
     * Devuelve ['success' => bool, 'message' => string, 'path' => ?string].
     */
    public function crearCarpeta(array $info, string $nombre): array
    {
        $disk = Storage::disk(self::DISK);

        if ($info['level'] === 0) {
            $nombre = $this->normalizarNombre($nombre);

            if (!$this->nombreValido($nombre)) {
                return $this->fail('Nombre inválido. Usa solo MAYÚSCULAS (se permite Ñ), números, guion o guion bajo.');
            }
        } elseif ($info['level'] === 1) {
            $nombre = $this->normalizarNombre($nombre);

            if ($this->colorPorCarpeta($nombre) === null) {
                return $this->fail('El color seleccionado no existe en el catálogo.');
            }

            if (!$disk->exists($info['relative'])) {
                return $this->fail('El producto no existe.');
            }
        } else {
            return $this->fail('No se pueden crear carpetas dentro de un color.');
        }

        $target = "{$info['relative']}/{$nombre}";

        if ($disk->exists($target)) {
            return $this->fail('Esa carpeta ya existe.');
        }

        $disk->makeDirectory($target);

        return [
            'success' => true,
            'message' => 'Carpeta creada.',
            'path' => trim("{$info['path']}/{$nombre}", '/'),
        ];
    }

    // ------------------------------------------------------------------
    // Internos
    // ------------------------------------------------------------------

    private function fail(string $message): array
    {
        return ['success' => false, 'message' => $message, 'path' => null];
    }

    private function contar(string $dir): array
    {
        $disk = Storage::disk(self::DISK);

        return [
            'photos' => collect($disk->files($dir))->filter(fn ($f) => $this->esImagen($f))->count(),
            'folders' => count($disk->directories($dir)),
        ];
    }

    private function breadcrumb(array $info): array
    {
        $crumbs = [['label' => self::BASE_PATH, 'path' => '']];

        if ($info['referencia'] !== null) {
            $crumbs[] = ['label' => $info['referencia'], 'path' => $info['referencia']];
        }

        if ($info['color'] !== null) {
            $color = $this->colorPorCarpeta($info['color']);
            $crumbs[] = [
                'label' => $color['nombre'],
                'path' => "{$info['referencia']}/{$info['color']}",
            ];
        }

        return $crumbs;
    }

    /** Colores que todavía no tienen carpeta en el producto, agrupados por macrocategoría. */
    private function coloresDisponibles(array $existentes): array
    {
        return collect($this->colores())
            ->reject(fn ($c) => in_array($this->carpetaDeColor($c), $existentes, true))
            ->sortBy('nombre')
            ->groupBy('macrocategoria')
            ->map(fn ($items, $macro) => [
                'macro' => $macro,
                'items' => $items->map(fn ($c) => [
                    'carpeta' => $this->carpetaDeColor($c),
                    'nombre' => $c['nombre'],
                    'hex' => $c['hex'],
                ])->values(),
            ])
            ->values()
            ->all();
    }
}
