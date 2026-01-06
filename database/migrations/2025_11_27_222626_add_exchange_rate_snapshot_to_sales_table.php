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
            $table->string('primary_currency_code', 3)->nullable()->after('total');
            $table->decimal('primary_exchange_rate', 15, 6)->nullable()->after('primary_currency_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['primary_currency_code', 'primary_exchange_rate']);
        });
    }
};
