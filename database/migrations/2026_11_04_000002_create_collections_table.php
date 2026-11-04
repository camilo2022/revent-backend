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
        // Colección del año: su año y código forman parte del código de cada producto (RB + 26 + 5 + 001)
        Schema::create('collections', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60)->comment('Nombre');
            $table->string('description')->nullable()->comment('Descripcion');
            $table->char('year', 2)->comment('Año (2 dígitos)');
            $table->char('code', 1)->comment('Código de la colección (1 dígito)');
            $table->unsignedSmallInteger('last_number')->default(0)->comment('Último consecutivo de producto generado');
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['year', 'code'], 'collections_year_code_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collections');
    }
};
