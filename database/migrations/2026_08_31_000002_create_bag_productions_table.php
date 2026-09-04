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
        Schema::create('bag_productions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bag_shift_id')->constrained('bag_shifts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('weight', 12, 4);
            $table->dateTime('recorded_at');
            $table->enum('status', ['pending_review', 'approved', 'rejected'])->default('pending_review');
            $table->string('sync_id', 64)->nullable()->unique();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['bag_shift_id', 'status']);
            $table->index(['user_id', 'recorded_at']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bag_productions');
    }
};
