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
            $table->string('commission_payment_method')->nullable()->after('commission_paid_at');
            $table->string('commission_payment_reference')->nullable()->after('commission_payment_method');
            $table->string('commission_payment_bank_name')->nullable()->after('commission_payment_reference');
            $table->string('commission_payment_currency')->nullable()->after('commission_payment_bank_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'commission_payment_method',
                'commission_payment_reference',
                'commission_payment_bank_name',
                'commission_payment_currency'
            ]);
        });
    }
};
