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
        Schema::create('sale_payment_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id');
            $table->string('currency_code', 3); // Código de la moneda (USD, COP, etc.)
            $table->decimal('amount', 15, 2); // Monto pagado en esa moneda
            $table->decimal('exchange_rate', 15, 6); // Tasa de cambio usada
            $table->decimal('amount_in_primary_currency', 15, 2); // Equivalente en moneda principal
            $table->timestamps();
            
            $table->foreign('sale_id')->references('id')->on('sales')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_payment_details');
    }
};
