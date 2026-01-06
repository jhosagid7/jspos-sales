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
            $table->string('commission_status')->default('pending_calculation')->after('status'); // pending_calculation, pending_payment, paid, cancelled
            $table->decimal('final_commission_amount', 10, 2)->nullable()->after('commission_status');
            $table->timestamp('commission_paid_at')->nullable()->after('final_commission_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            //
        });
    }
};
