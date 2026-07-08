<?php

use App\Models\Region;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Expression;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Region::class)->nullable()->constrained()->onUpdate('cascade')->onDelete('cascade');
            $table->string('name', 70);
            $table->string('iso3', 3);
            $table->string('iso2', 2);
            $table->string('numeric_code', 3);
            $table->string('phone_code', 5);
            $table->string('currency', 10);
            $table->string('currency_name', 50);
            $table->string('currency_symbol', 10);
            $table->string('tld', 5);
            $table->string('native', 70)->nullable();
            $table->string('nationality', 70);
            $table->string('latitude', 20)->nullable();
            $table->string('longitude', 20)->nullable();
            $table->string('emoji', 10);
            $table->string('emojiU', 30);
            $table->json('translations')->default(new Expression('(JSON_OBJECT())'));
            $table->json('settings')->default(new Expression('(JSON_OBJECT())'));
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
