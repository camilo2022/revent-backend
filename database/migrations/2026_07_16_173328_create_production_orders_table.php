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

        // Si la migración se corrió a medias antes, el procedimiento viejo se reemplaza
        DB::unprepared('DROP PROCEDURE IF EXISTS production_order_consecutive');

        // Siguiente consecutivo del día: PO + yymmdd + 3 dígitos. Las variables llevan el collation de la tabla para poder compararlas;
        // current_date no se usa como nombre porque es palabra reservada de MySQL
        DB::unprepared("
            CREATE PROCEDURE production_order_consecutive()
            BEGIN
                DECLARE date_consecutive CHAR(6) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
                DECLARE last_consecutive VARCHAR(11) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
                DECLARE next_consecutive INT;

                SET date_consecutive = DATE_FORMAT(CURDATE(), '%y%m%d');

                SELECT consecutive INTO last_consecutive FROM production_orders
                WHERE consecutive LIKE CONCAT('PO', date_consecutive, '%') ORDER BY consecutive DESC LIMIT 1 FOR UPDATE;

                IF last_consecutive IS NULL THEN
                    SELECT CONCAT('PO', date_consecutive, '001') AS consecutive;
                ELSE
                    SET next_consecutive = CAST(RIGHT(last_consecutive, 3) AS UNSIGNED) + 1;

                    SELECT CONCAT('PO', date_consecutive, LPAD(next_consecutive, 3, '0')) AS consecutive;
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
