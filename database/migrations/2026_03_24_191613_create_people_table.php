<?php

use App\Models\BloodType;
use App\Models\Gender;
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
        Schema::create('people', function (Blueprint $table) {
            $table->id();
            $table->string('document')->unique();
            $table->string('names');
            $table->string('last_names');
            $table->foreignIdFor(Gender::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Item');
            $table->date('birth_date')->nullable();
            $table->foreignIdFor(BloodType::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Item');
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
