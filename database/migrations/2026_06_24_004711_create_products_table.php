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
            $table->foreignIdFor(Trademark::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Marca');
            $table->string('code')->unique();
            $table->foreignIdFor(Category::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Categoría');
            $table->foreignIdFor(Subcategory::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Subcategoría');
            $table->text('observation')->nullable();
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
