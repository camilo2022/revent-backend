<?php

use App\Models\Module;
use App\Models\Permission;
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
        Schema::create('submodules', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('url')->unique();
            $table->string('icon');
            $table->foreignIdFor(Module::class)->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->foreignIdFor(Permission::class)->unique()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('submodules');
    }
};
