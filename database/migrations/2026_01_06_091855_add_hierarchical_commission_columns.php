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
        // Global Configuration
        Schema::table('configurations', function (Blueprint $table) {
            $table->integer('global_commission_1_threshold')->nullable()->after('leyend');
            $table->decimal('global_commission_1_percentage', 5, 2)->nullable()->after('global_commission_1_threshold');
            $table->integer('global_commission_2_threshold')->nullable()->after('global_commission_1_percentage');
            $table->decimal('global_commission_2_percentage', 5, 2)->nullable()->after('global_commission_2_threshold');
        });

        // Seller Override
        Schema::table('users', function (Blueprint $table) {
            $table->integer('seller_commission_1_threshold')->nullable()->after('status');
            $table->decimal('seller_commission_1_percentage', 5, 2)->nullable()->after('seller_commission_1_threshold');
            $table->integer('seller_commission_2_threshold')->nullable()->after('seller_commission_1_percentage');
            $table->decimal('seller_commission_2_percentage', 5, 2)->nullable()->after('seller_commission_2_threshold');
        });

        // Customer Override
        Schema::table('customers', function (Blueprint $table) {
            $table->integer('customer_commission_1_threshold')->nullable()->after('type');
            $table->decimal('customer_commission_1_percentage', 5, 2)->nullable()->after('customer_commission_1_threshold');
            $table->integer('customer_commission_2_threshold')->nullable()->after('customer_commission_1_percentage');
            $table->decimal('customer_commission_2_percentage', 5, 2)->nullable()->after('customer_commission_2_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn([
                'global_commission_1_threshold',
                'global_commission_1_percentage',
                'global_commission_2_threshold',
                'global_commission_2_percentage'
            ]);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'seller_commission_1_threshold',
                'seller_commission_1_percentage',
                'seller_commission_2_threshold',
                'seller_commission_2_percentage'
            ]);
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'customer_commission_1_threshold',
                'customer_commission_1_percentage',
                'customer_commission_2_threshold',
                'customer_commission_2_percentage'
            ]);
        });
    }
};
