<?php

use App\Models\TechnicalSheet;
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
        // La orden guarda la versión de la ficha con la que se creó
        Schema::table('production_orders', function (Blueprint $table) {
            $table->foreignIdFor(TechnicalSheet::class)->nullable()->after('id')->constrained()->cascadeOnUpdate()->restrictOnDelete()->comment('Ficha técnica usada');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropConstrainedForeignIdFor(TechnicalSheet::class);
        });
    }
};
