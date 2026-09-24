<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sale_layer_consumptions', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_layer_consumptions', 'unit_cost')) {
                $table->decimal('unit_cost', 10, 2)->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('sale_layer_consumptions', 'total_cost')) {
                $table->decimal('total_cost', 10, 2)->default(0)->after('unit_price');
            }
        });

        // Backfill existing rows with cost from transfer_stock_layers or products table
        DB::statement("
            UPDATE sale_layer_consumptions slc
            LEFT JOIN transfer_stock_layers tsl ON slc.transfer_stock_layer_id = tsl.id
            LEFT JOIN products p ON slc.product_id = p.id
            SET slc.unit_cost = COALESCE(tsl.cost_price, p.cost, 0),
                slc.total_cost = slc.quantity * COALESCE(tsl.cost_price, p.cost, 0)
            WHERE slc.unit_cost = 0
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_layer_consumptions', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'total_cost']);
        });
    }
};
