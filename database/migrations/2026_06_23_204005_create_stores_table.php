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
        Schema::create('stores', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name')->unique();
            $table->integer('siigo_warehouse_id')->nullable()->unique()->comment('Bodega de Siigo');
            $table->integer('siigo_transit_warehouse_id')->nullable()->unique()->comment('Bodega de tránsito de Siigo; null si no tiene');
            $table->morphs('location');
            $table->string('address');
            $table->string('neighborhood');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
