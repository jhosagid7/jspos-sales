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
        Schema::table('configurations', function (Blueprint $table) {
            $table->boolean('sales_show_commissions')->default(false)->after('sales_show_rate_badge');
            $table->boolean('sales_show_freight')->default(false)->after('sales_show_commissions');
            $table->boolean('sales_show_breakdown_freight')->default(false)->after('sales_show_freight');
            $table->boolean('sales_show_warehouse')->default(false)->after('sales_show_breakdown_freight');
            $table->boolean('sales_show_driver')->default(false)->after('sales_show_warehouse');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn([
                'sales_show_commissions',
                'sales_show_freight',
                'sales_show_breakdown_freight',
                'sales_show_warehouse',
                'sales_show_driver'
            ]);
        });
    }
};
