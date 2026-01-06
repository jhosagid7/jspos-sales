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
        Schema::create('cash_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_id')->constrained()->onDelete('cascade');
            $table->foreignId('sale_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['opening', 'sale_payment', 'sale_change', 'adjustment', 'closing']);
            $table->string('currency_code', 10);
            $table->decimal('amount', 15, 2); // Positivo = entrada, Negativo = salida
            $table->decimal('amount_in_primary_currency', 15, 2);
            $table->decimal('balance_after', 15, 2); // Saldo después del movimiento
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index('cash_register_id');
            $table->index('sale_id');
            $table->index(['cash_register_id', 'currency_code']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_movements');
    }
};
