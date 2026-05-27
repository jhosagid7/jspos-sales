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
        Schema::create('exchange_rate_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id'); // requester
            $table->unsignedBigInteger('approver_id')->nullable(); // supervisor/approver
            $table->unsignedBigInteger('sale_id')->nullable(); // associated sale (credit)
            $table->decimal('custom_rate', 20, 10);
            $table->text('reason');
            $table->string('status', 20)->default('pending'); // 'pending', 'approved', 'rejected', 'used'
            $table->string('token', 100)->unique()->nullable(); // single-use token
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('approver_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('sale_id')->references('id')->on('sales')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_approvals');
    }
};
