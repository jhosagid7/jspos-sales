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
        Schema::create('cash_registers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamp('opening_date');
            $table->timestamp('closing_date')->nullable();
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('opening_notes')->nullable();
            $table->text('closing_notes')->nullable();
            $table->decimal('total_opening_amount', 15, 2)->default(0);
            $table->decimal('total_expected_amount', 15, 2)->nullable();
            $table->decimal('total_counted_amount', 15, 2)->nullable();
            $table->decimal('difference_amount', 15, 2)->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
            $table->index('opening_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_registers');
    }
};
