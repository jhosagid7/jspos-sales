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
        Schema::create('cash_register_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_register_id')->constrained()->onDelete('cascade');
            $table->string('currency_code', 10);
            $table->enum('type', ['opening', 'closing']);
            $table->decimal('amount', 15, 2);
            $table->decimal('amount_in_primary_currency', 15, 2);
            $table->decimal('exchange_rate', 15, 6);
            $table->timestamps();
            
            $table->index('cash_register_id');
            $table->index(['cash_register_id', 'currency_code', 'type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_register_details');
    }
};
