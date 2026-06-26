<?php

use App\Models\Color;
use App\Models\Product;
use App\Models\Size;
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
        Schema::create('product_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->foreignIdFor(Product::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Producto');
            $table->foreignIdFor(Color::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Color');
            $table->foreignIdFor(Size::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Talla');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index(['product_id', 'color_id', 'size_id'])->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_details');
    }
};
