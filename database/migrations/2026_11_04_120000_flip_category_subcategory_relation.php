<?php

use App\Models\Category;
use App\Models\Subcategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * La relación categoría - subcategoría se guardaba con la categoría como dueña (model_id = categoría).
 * Ahora la subcategoría pertenece a una sola categoría (model_id = subcategoría, subitem_id = categoría),
 * igual que tipos de documento con tipos de persona.
 */
return new class extends Migration
{
    public function up(): void
    {
        $rows = DB::table('model_has_subitems')->where('model_type', Category::class)->get();

        foreach ($rows as $row) {
            DB::table('model_has_subitems')->where('model_type', Category::class)
                ->where('model_id', $row->model_id)->where('subitem_id', $row->subitem_id)->delete();

            // Una subcategoría queda en una sola categoría: si estaba en varias, se conserva la primera
            DB::table('model_has_subitems')->updateOrInsert(
                ['model_type' => Subcategory::class, 'model_id' => $row->subitem_id],
                ['subitem_id' => $row->model_id]
            );
        }
    }

    public function down(): void
    {
        $rows = DB::table('model_has_subitems')->where('model_type', Subcategory::class)->get();

        foreach ($rows as $row) {
            DB::table('model_has_subitems')->where('model_type', Subcategory::class)
                ->where('model_id', $row->model_id)->delete();

            DB::table('model_has_subitems')->insertOrIgnore([
                'model_type' => Category::class, 'model_id' => $row->subitem_id, 'subitem_id' => $row->model_id,
            ]);
        }
    }
};
