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
        Schema::table('seller_configs', function (Blueprint $table) {
            $table->string('current_batch')->default('1')->after('exchange_diff_percent');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->string('batch_name')->nullable()->after('commission_payment_notes');
            $table->integer('batch_sequence')->nullable()->after('batch_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('seller_configs', function (Blueprint $table) {
            $table->dropColumn('current_batch');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['batch_name', 'batch_sequence']);
        });
    }
};
