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
            $table->decimal('binance_markup_points', 20, 10)->default(0)->after('binance_rate');
        });

        Schema::table('exchange_rate_histories', function (Blueprint $table) {
            $table->string('period', 10)->nullable()->after('rate'); // 'AM' or 'PM'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn('binance_markup_points');
        });

        Schema::table('exchange_rate_histories', function (Blueprint $table) {
            $table->dropColumn('period');
        });
    }
};
