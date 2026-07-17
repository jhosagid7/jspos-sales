<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained('banks');
            $table->foreignId('category_id')->constrained('bank_expense_categories');
            $table->decimal('amount', 15, 2);
            $table->date('expense_date');
            $table->text('description')->nullable();
            $table->string('reference')->nullable();
            $table->string('beneficiary')->nullable();
            $table->string('receipt_path')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->boolean('is_recurring')->default(false);
            $table->timestamps();

            $table->index('expense_date');
            $table->index(['bank_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_expenses');
    }
};
