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
            $table->decimal('commission_payment_rate', 10, 4)->nullable()->after('commission_payment_currency');
            $table->decimal('commission_payment_amount', 10, 2)->nullable()->after('commission_payment_rate');
            $table->text('commission_payment_notes')->nullable()->after('commission_payment_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn([
                'commission_payment_rate',
                'commission_payment_amount',
                'commission_payment_notes'
            ]);
        });
    }
};
