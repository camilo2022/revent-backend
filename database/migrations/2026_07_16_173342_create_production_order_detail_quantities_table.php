<?php

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
        // Curva de una tienda para un color: una fila por talla. Las llaves llevan nombre corto (MySQL permite 64 caracteres)
        Schema::create('production_order_detail_quantities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_detail_id')->comment('Color de la orden de produccion');
            $table->unsignedBigInteger('store_id')->comment('Tienda');
            $table->unsignedBigInteger('product_detail_id')->comment('Talla del producto');
            $table->unsignedBigInteger('quantity')->default(0);
            $table->timestamps();
            $table->unique(['production_order_detail_id', 'store_id', 'product_detail_id'], 'podq_detail_store_size_unique');

            // Al borrar el color de la orden se borran sus cantidades
            $table->foreign('production_order_detail_id', 'podq_detail_fk')->references('id')->on('production_order_details')->cascadeOnUpdate()->cascadeOnDelete();
            $table->foreign('store_id', 'podq_store_fk')->references('id')->on('stores')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('product_detail_id', 'podq_size_fk')->references('id')->on('product_details')->cascadeOnUpdate()->restrictOnDelete();
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
