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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('sale_id')->constrained('sales');
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['pay', 'settled'])->defauly('pay');
            $table->enum('pay_way', ['cash', 'deposit', 'nequi'])->defauly('cash');
            $table->string('bank', 99)->nullable();
            $table->string('account_number', 99)->nullable();
            $table->string('deposit_number', 99)->nullable();
            $table->string('phone_number', 99)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
