<?php

use App\Models\Store;
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
        Schema::create('model_has_stores', function (Blueprint $table) {
            $table->foreignIdFor(Store::class)->constrained()->cascadeOnUpdate()->cascadeOnDelete()->comment('Tienda');
            $table->morphs('model');
            $table->unique(['store_id', 'model_id', 'model_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_has_stores');
    }
};
