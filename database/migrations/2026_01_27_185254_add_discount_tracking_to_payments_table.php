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
        Schema::table('payments', function (Blueprint $table) {
            // Registro de descuentos/recargos aplicados
            $table->decimal('discount_applied', 10, 2)->default(0)->after('amount')->comment('Monto del descuento/recargo');
            $table->decimal('discount_percentage', 5, 2)->default(0)->after('discount_applied')->comment('% aplicado');
            $table->string('discount_reason')->nullable()->after('discount_percentage')->comment('Ej: "Pronto pago (5 días)" o "Mora (20 días)"');
            $table->integer('payment_days')->nullable()->after('discount_reason')->comment('Días transcurridos desde la factura');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'discount_applied',
                'discount_percentage',
                'discount_reason',
                'payment_days'
            ]);
        });
    }
};
