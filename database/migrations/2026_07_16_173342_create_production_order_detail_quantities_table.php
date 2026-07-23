<?php

use App\Models\ProductDetail;
use App\Models\ProductionOrderDetail;
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
        Schema::create('production_order_detail_quantities', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ProductionOrderDetail::class)->constrained()->cascadeOnUpdate()->restrictOnDelete()->comment('Detalle de la orden de produccion');
            $table->foreignIdFor(ProductDetail::class)->constrained()->cascadeOnUpdate()->restrictOnDelete()->comment('Detalle del producto');
            $table->unsignedBigInteger('quantity')->default(0);
            $table->timestamps();
            $table->index(['production_order_detail_id', 'product_detail_id'])->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_order_detail_quantities');
    }
};
