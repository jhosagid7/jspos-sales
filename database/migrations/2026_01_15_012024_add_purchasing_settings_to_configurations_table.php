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
            $table->string('purchasing_calculation_mode')->default('recent')->after('check_stock_reservation'); // recent, seasonal
            $table->integer('purchasing_coverage_days')->default(15)->after('purchasing_calculation_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn(['purchasing_calculation_mode', 'purchasing_coverage_days']);
        });
    }
};
