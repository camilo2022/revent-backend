<?php

use App\Models\ProductDetail;
use App\Models\ProductionOrder;
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
        // Un detalle es un color de la ficha dentro de la orden; las tiendas y tallas van en las cantidades
        Schema::create('production_order_details', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ProductionOrder::class)->constrained()->cascadeOnUpdate()->restrictOnDelete()->comment('Orden de produccion');
            $table->foreignIdFor(ProductDetail::class)->constrained()->cascadeOnUpdate()->restrictOnDelete()->comment('Color del producto');
            $table->decimal('cost', 12, 2)->default(0)->comment('Costo');
            $table->decimal('price', 12, 2)->default(0)->comment('Precio de venta');
            $table->text('observation')->nullable();
            $table->timestamps();
            $table->unique(['production_order_id', 'product_detail_id'], 'pod_order_color_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_order_details');
    }
};
