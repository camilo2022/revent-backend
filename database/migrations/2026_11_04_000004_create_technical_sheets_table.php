<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Subcategory;
use App\Models\Trademark;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ficha técnica del producto (una por producto); los cambios quedan en la auditoría (audits)
        Schema::create('technical_sheets', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Product::class)->unique()->constrained()->cascadeOnUpdate()->restrictOnDelete()->comment('Producto');
            $table->foreignIdFor(Trademark::class)->constrained()->cascadeOnUpdate()->restrictOnDelete()->comment('Marca');
            $table->foreignIdFor(Category::class)->constrained()->cascadeOnUpdate()->restrictOnDelete()->comment('Categoría');
            $table->foreignIdFor(Subcategory::class)->constrained()->cascadeOnUpdate()->restrictOnDelete()->comment('Subcategoría (tipo de calzado)');
            $table->text('process')->nullable()->comment('Proceso que lleva (texto escrito)');
            $table->text('description')->nullable();
            $table->text('observation')->nullable();
            $table->json('specifications')->default(new Expression('(JSON_OBJECT())'))->comment('Características del calzado');
            $table->enum('status', ['Pendiente', 'En revision', 'Aprobado', 'Cancelado'])->default('Pendiente');
            $table->foreignIdFor(User::class, 'approved_by')->nullable()->constrained()->cascadeOnUpdate()->nullOnDelete()->comment('Aprobó');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technical_sheets');
    }
};
