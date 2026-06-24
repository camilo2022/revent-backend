<?php

use App\Models\RiskManager;
use App\Models\CompensationFund;
use App\Models\HealthEntity;
use App\Models\PensionFund;
use App\Models\Person;
use App\Models\Position;
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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Person::class)->unique()->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Item');
            $table->foreignIdFor(Position::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Item');
            $table->foreignIdFor(RiskManager::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Item');
            $table->foreignIdFor(HealthEntity::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Item');
            $table->foreignIdFor(PensionFund::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Item');
            $table->foreignIdFor(CompensationFund::class)->constrained()->onUpdate('cascade')->onDelete('cascade')->comment('Item');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
