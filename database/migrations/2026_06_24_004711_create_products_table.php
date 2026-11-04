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
        // El producto solo lleva su identidad; marca, categoría y subcategoría van en la ficha técnica
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique()->comment('Código generado: prefijo + año + colección + consecutivo (RB265001)');
            $table->string('name', 60)->comment('Nombre de la referencia (ACTION)');
            // La colección es un subítem (ítem Colecciones), como marcas, colores y tallas
            $table->foreignId('collection_id')->constrained('subitems')->cascadeOnUpdate()->restrictOnDelete()->comment('Colección');
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
