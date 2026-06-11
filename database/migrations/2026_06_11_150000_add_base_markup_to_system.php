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
        // 1. Add base_markup_percent to customer_configs
        Schema::table('customer_configs', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_configs', 'base_markup_percent')) {
                $table->decimal('base_markup_percent', 8, 2)->default(0.00)->after('exchange_diff_percent');
            }
        });

        // 2. Add columns to sales
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'applied_base_markup_percent')) {
                $table->decimal('applied_base_markup_percent', 8, 2)->default(0.00)->after('applied_exchange_diff_percent');
            }
            if (!Schema::hasColumn('sales', 'base_markup_amount')) {
                $table->decimal('base_markup_amount', 12, 4)->default(0.0000)->after('exchange_diff_amount');
            }
        });

        // 3. Add columns to orders
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'applied_base_markup_percent')) {
                $table->decimal('applied_base_markup_percent', 8, 2)->default(0.00)->after('exchange_diff_amount');
            }
            if (!Schema::hasColumn('orders', 'base_markup_amount')) {
                $table->decimal('base_markup_amount', 12, 4)->default(0.0000)->after('applied_base_markup_percent');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['applied_base_markup_percent', 'base_markup_amount']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['applied_base_markup_percent', 'base_markup_amount']);
        });

        Schema::table('customer_configs', function (Blueprint $table) {
            $table->dropColumn(['base_markup_percent']);
        });
    }
};
