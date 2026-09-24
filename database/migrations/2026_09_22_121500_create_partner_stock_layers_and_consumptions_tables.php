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
        Schema::create('transfer_stock_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_id')->nullable()->constrained('transfers')->onDelete('cascade');
            $table->foreignId('transfer_detail_id')->nullable()->constrained('transfer_details')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('origin_warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('destination_warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->decimal('initial_quantity', 10, 2);
            $table->decimal('remaining_quantity', 10, 2)->default(0);
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['product_id', 'destination_warehouse_id', 'remaining_quantity'], 'tsl_prod_dest_rem_idx');
            $table->index(['origin_warehouse_id'], 'tsl_origin_wh_idx');
        });

        Schema::create('sale_layer_consumptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales')->onDelete('cascade');
            $table->foreignId('sale_detail_id')->nullable()->constrained('sale_details')->onDelete('cascade');
            $table->foreignId('transfer_stock_layer_id')->nullable()->constrained('transfer_stock_layers')->onDelete('set null');
            $table->foreignId('origin_warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_price', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->timestamps();

            $table->index(['sale_id', 'origin_warehouse_id'], 'slc_sale_origin_idx');
            $table->index(['origin_warehouse_id', 'created_at'], 'slc_origin_created_idx');
            $table->index(['product_id', 'origin_warehouse_id'], 'slc_prod_origin_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_layer_consumptions');
        Schema::dropIfExists('transfer_stock_layers');
    }
};
