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
        // Dos niveles en una tabla: color (model = producto, assignable = color) y talla (model = el color, assignable = talla)
        Schema::create('product_details', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable()->unique();
            $table->morphs('model');
            $table->morphs('assignable');
            $table->text('description')->nullable();
            $table->timestamps();
            // Un color o talla no se repite dentro de su padre (nombre corto: MySQL permite 64 caracteres)
            $table->unique(['model_type', 'model_id', 'assignable_type', 'assignable_id'], 'pd_model_assignable_unique');
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
