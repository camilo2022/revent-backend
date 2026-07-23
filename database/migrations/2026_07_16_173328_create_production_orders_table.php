<?php

use App\Models\Supplier;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('consecutive', 11)->unique();
            $table->date('due_date')->comment('Fecha de vencimiento');
            $table->foreignIdFor(Supplier::class)->constrained()->cascadeOnUpdate()->restrictOnDelete()->comment('Proveedor');
            $table->unsignedTinyInteger('vat_percentage')->default(100)->comment('Porcentaje que se factura con IVA');
            $table->unsignedTinyInteger('delivery_note_percentage')->default(0)->comment('Porcentaje que se maneja por remisión');
            $table->enum('status', ['Pendiente', 'Aprobado', 'Cancelado'])->default('Pendiente');
            $table->timestamps();
        });

        DB::unprepared("
            CREATE PROCEDURE production_order_consecutive()
            BEGIN
                DECLARE current_date CHAR(6);
                DECLARE last_consecutive VARCHAR(11);
                DECLARE next_consecutive INT;

                SET current_date = DATE_FORMAT(CURDATE(), '%y%m%d');

                SELECT consecutive INTO last_consecutive FROM production_orders
                WHERE consecutive LIKE CONCAT('PO', current_date, '%') ORDER BY consecutive DESC LIMIT 1;

                IF last_consecutive IS NULL THEN
                    SELECT CONCAT('PO', current_date, '001') AS consecutive;
                ELSE
                    SET next_consecutive = CAST(RIGHT(last_consecutive, 3) AS UNSIGNED) + 1;

                    SELECT CONCAT('PO', current_date, LPAD(next_consecutive, 3, '0')) AS consecutive;
                END IF;
            END
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_orders');

        DB::unprepared('DROP PROCEDURE IF EXISTS production_order_consecutive');
    }
};
