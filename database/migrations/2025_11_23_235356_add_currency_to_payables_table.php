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
        Schema::table('payables', function (Blueprint $table) {
            $table->string('currency_code')->nullable()->after('amount');
            $table->decimal('exchange_rate', 10, 4)->default(1)->after('currency_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payables', function (Blueprint $table) {
            $table->dropColumn(['currency_code', 'exchange_rate']);
        });
    }
};
