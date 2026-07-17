<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_transfers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('from_bank_id')->constrained('banks');
            $table->foreignId('to_bank_id')->constrained('banks');
            $table->decimal('amount_from', 15, 2);
            $table->decimal('amount_to', 15, 2);
            $table->decimal('exchange_rate', 15, 6)->default(1.0);
            $table->date('transfer_date');
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->constrained('users');
            $table->timestamps();

            $table->index('transfer_date');
            $table->index(['from_bank_id', 'transfer_date']);
            $table->index(['to_bank_id', 'transfer_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transfers');
    }
};
