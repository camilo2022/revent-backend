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
        // El índice original se creó sin ser único: se cambia por uno único real
        if (Schema::hasIndex('product_details', 'product_details_model_assignable_unique')) {
            Schema::table('product_details', function (Blueprint $table) {
                $table->dropIndex('product_details_model_assignable_unique');
            });
        }

        // Un mismo color o talla no puede repetirse dentro de su padre
        Schema::table('product_details', function (Blueprint $table) {
            $table->unique(['model_type', 'model_id', 'assignable_type', 'assignable_id'], 'pd_model_assignable_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_details', function (Blueprint $table) {
            $table->dropUnique('pd_model_assignable_unique');
        });
    }
};
