<?php

namespace Database\Seeders;

use App\Models\Color;
use App\Services\ProductPhotoService;
use Illuminate\Database\Seeder;

/**
 * Completa en cada color del ERP su código en Siigo, su hex y su grupo de color (los que usa el filtro de inventario),
 * tomándolos de la lista de colores que el filtro usaba antes y uniendo por el nombre del color.
 * Solo llena lo que está vacío, así que se puede correr varias veces y no pisa lo que ya se editó a mano.
 *   php artisan db:seed --class=ColorSiigoSettingsSeeder
 */
class ColorSiigoSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $catalogo = [];

        foreach (app(ProductPhotoService::class)->colores() as $color) {
            $catalogo[$this->normalize($color['nombre'])] = $color;
        }

        $updated = 0;

        foreach (Color::query()->get() as $color) {
            $base = $catalogo[$this->normalize($color->name)] ?? null;

            if (!$base) {
                continue;
            }

            $settings = (array) $color->settings;
            $before = $settings;

            $settings['siigo_code'] = $settings['siigo_code'] ?? $base['codigo'];
            $settings['hex'] = $settings['hex'] ?? $base['hex'];
            $settings['macro_category'] = $settings['macro_category'] ?? $base['macrocategoria'];

            if ($settings !== $before) {
                $color->settings = $settings;
                $color->save();
                $updated++;
            }
        }

        $this->command?->info("Colores completados: {$updated}.");
    }

    private function normalize(string $name): string
    {
        $name = strtr(strtoupper(trim($name)), ['Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N']);

        return preg_replace('/[^A-Z0-9]/', '', $name);
    }
}
