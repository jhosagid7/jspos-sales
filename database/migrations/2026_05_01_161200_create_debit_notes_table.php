<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('debit_notes', function (Blueprint $table) {
            $table->id();
            $table->string('debit_number')->unique();
            $table->foreignId('customer_id')->constrained();
            $table->foreignId('user_id')->constrained(); // Creator
            $table->foreignId('sale_id')->nullable()->constrained(); // Optional link to sale
            $table->decimal('amount', 18, 4);
            $table->text('concept');
            $table->string('currency')->default('USD');
            $table->decimal('exchange_rate', 18, 4)->default(1);
            $table->enum('status', ['pending', 'paid', 'voided'])->default('pending');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('debit_notes');
    }
};
