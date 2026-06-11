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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('payment_agreement', 10)->default('USD')->after('status');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_agreement', 10)->default('USD')->after('status');
        });

        // Backfill historical data
        DB::table('sales')->where('applied_exchange_diff_percent', '>', 0)->update(['payment_agreement' => 'BCV']);
        DB::table('orders')->where('exchange_diff_amount', '>', 0)->update(['payment_agreement' => 'BCV']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('payment_agreement');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('payment_agreement');
        });
    }
};
