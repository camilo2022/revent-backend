<?php

use App\Models\Collection;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // El producto queda con su identidad (código, nombre, colección); marca, categoría y subcategoría pasan a la ficha técnica
        Schema::table('products', function (Blueprint $table) {
            $table->string('name', 60)->nullable()->after('code')->comment('Nombre de la referencia');
            $table->foreignIdFor(Collection::class)->nullable()->after('name')->constrained()->cascadeOnUpdate()->restrictOnDelete()->comment('Colección');
            $table->unsignedBigInteger('trademark_id')->nullable()->change();
            $table->unsignedBigInteger('category_id')->nullable()->change();
            $table->unsignedBigInteger('subcategory_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(Collection::class);
            $table->dropColumn('name');
        });
    }
};
