<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_daily_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained('banks');
            $table->date('closure_date');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->decimal('total_income', 15, 2)->default(0);
            $table->unsignedInteger('total_income_count')->default(0);
            $table->decimal('total_expenses', 15, 2)->default(0);
            $table->unsignedInteger('total_expenses_count')->default(0);
            $table->decimal('closing_balance', 15, 2)->default(0);
            $table->string('status')->default('open'); // open, closed
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['bank_id', 'closure_date']);
            $table->index('closure_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_daily_closures');
    }
};
