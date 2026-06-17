<?php

use App\Models\Subitem;
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
        Schema::create('model_has_subitems', function (Blueprint $table) {
            $table->foreignIdFor(Subitem::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Subitem');
            $table->morphs('model');
            $table->unique(['subitem_id', 'model_id', 'model_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_has_subitems');
    }
};
