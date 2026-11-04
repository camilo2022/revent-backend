<?php

namespace Database\Seeders;

use App\Models\Collection;
use App\Models\Item;
use Illuminate\Database\Seeder;

/**
 * Ítem "Colecciones": sus registros viven en subitems, como marcas, colores y tallas.
 * Se puede correr varias veces (o solo, en una base ya sembrada):
 *   php artisan db:seed --class=ErpItemsSeeder
 */
class ErpItemsSeeder extends Seeder
{
    public function run(): void
    {
        if (Item::where('id', Collection::ITEM_ID)->exists()) {
            return;
        }

        // El id es fijo porque cada modelo de catálogo lo usa como constante (ITEM_ID)
        $item = new Item();
        $item->forceFill([
            'id' => Collection::ITEM_ID,
            'name' => 'Colecciones',
            'description' => 'Listado de colecciones de producto del año.',
        ])->save();
    }
}
