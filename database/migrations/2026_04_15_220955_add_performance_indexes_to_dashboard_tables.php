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
        // Check and add indexes for 'sales' table
        $salesIndexes = collect(DB::select("SHOW INDEX FROM sales"))->pluck('Key_name')->unique();

        Schema::table('sales', function (Blueprint $table) use ($salesIndexes) {
            if (!$salesIndexes->contains('sales_status_index')) {
                $table->index('status');
            }
            if (!$salesIndexes->contains('sales_type_index')) {
                $table->index('type');
            }
            if (!$salesIndexes->contains('sales_is_foreign_sale_index')) {
                $table->index('is_foreign_sale');
            }
            if (!$salesIndexes->contains('sales_commission_status_index')) {
                $table->index('commission_status');
            }
        });

        // Check and add indexes for 'payments' table
        $paymentIndexes = collect(DB::select("SHOW INDEX FROM payments"))->pluck('Key_name')->unique();

        Schema::table('payments', function (Blueprint $table) use ($paymentIndexes) {
            if (!$paymentIndexes->contains('payments_status_index')) {
                $table->index('status');
            }
            if (!$paymentIndexes->contains('payments_payment_date_index')) {
                $table->index('payment_date');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $salesIndexes = collect(DB::select("SHOW INDEX FROM sales"))->pluck('Key_name')->unique();
            
            if ($salesIndexes->contains('sales_status_index')) $table->dropIndex(['status']);
            if ($salesIndexes->contains('sales_type_index')) $table->dropIndex(['type']);
            if ($salesIndexes->contains('sales_is_foreign_sale_index')) $table->dropIndex(['is_foreign_sale']);
            if ($salesIndexes->contains('sales_commission_status_index')) $table->dropIndex(['commission_status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $paymentIndexes = collect(DB::select("SHOW INDEX FROM payments"))->pluck('Key_name')->unique();

            if ($paymentIndexes->contains('payments_status_index')) $table->dropIndex(['status']);
            if ($paymentIndexes->contains('payments_payment_date_index')) $table->dropIndex(['payment_date']);
        });
    }
};
