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
        Schema::table('sales', function (Blueprint $table) {
            $table->integer('seller_tier_1_days')->nullable()->after('applied_exchange_diff_percent');
            $table->decimal('seller_tier_1_percent', 8, 2)->nullable()->after('seller_tier_1_days');
            $table->integer('seller_tier_2_days')->nullable()->after('seller_tier_1_percent');
            $table->decimal('seller_tier_2_percent', 8, 2)->nullable()->after('seller_tier_2_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'seller_tier_1_days',
                'seller_tier_1_percent',
                'seller_tier_2_days',
                'seller_tier_2_percent'
            ]);
        });
    }
};
