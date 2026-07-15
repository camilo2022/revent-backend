<?php

use App\Models\BloodType;
use App\Models\Country;
use App\Models\Gender;
use App\Models\DocumentType;
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
            $table->string('names');
            $table->string('last_names');
            $table->foreignIdFor(DocumentType::class)->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('document')->unique();
            $table->foreignIdFor(Gender::class)->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->date('birth_date');
            $table->foreignIdFor(BloodType::class)->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->morphs('location');
            $table->string('address');
            $table->string('neighborhood');
            $table->foreignIdFor(Country::class, 'phone_country_id')->nullable()->constrained()->cascadeOnUpdate()->restrictOnDelete();
            $table->string('phone')->nullable()->unique();
            $table->string('email')->unique();
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
