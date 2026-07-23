<?php

use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Trademark;
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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignIdFor(Trademark::class)->constrained()->cascadeOnUpdate()->restrictOnDelete()->comment('Marca');
            $table->foreignIdFor(Category::class)->constrained()->cascadeOnUpdate()->restrictOnDelete()->comment('Categoría');
            $table->foreignIdFor(Subcategory::class)->constrained()->cascadeOnUpdate()->restrictOnDelete()->comment('Subcategoría');
            $table->text('description')->nullable();
            $table->decimal('cost', 12, 2)->default(0)->comment('Costo');
            $table->decimal('price', 12, 2)->default(0)->comment('Precio de venta');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
