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
        Schema::create('sale_returns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('return_number')->unique();
            $table->decimal('total_returned', 15, 2);
            $table->text('reason')->nullable();
            $table->enum('return_type', ['partial', 'full']);
            // Method used to compensate the customer
            $table->enum('refund_method', ['cash', 'bank', 'wallet', 'debt_reduction']);
            $table->unsignedBigInteger('cash_register_id')->nullable();
            $table->timestamps();
        });

        Schema::create('sale_return_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_return_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_detail_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('quantity_returned', 10, 2);
            $table->decimal('unit_price', 15, 2);
            $table->decimal('subtotal', 15, 2);
            // Action taken with the physical stock
            $table->enum('stock_action', ['returned_to_stock', 'damaged', 'discarded'])->default('returned_to_stock');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_return_details');
        Schema::dropIfExists('sale_returns');
    }
};
