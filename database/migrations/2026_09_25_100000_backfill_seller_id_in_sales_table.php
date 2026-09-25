<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('sales') && Schema::hasColumn('sales', 'seller_id')) {
            // 1. Backfill from customer's assigned seller
            if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'seller_id')) {
                DB::table('sales')
                    ->join('customers', 'sales.customer_id', '=', 'customers.id')
                    ->whereNull('sales.seller_id')
                    ->whereNotNull('customers.seller_id')
                    ->update([
                        'sales.seller_id' => DB::raw('customers.seller_id')
                    ]);
            }

            // 2. Fallback to OFICINA for any remaining sales with NULL seller_id
            $oficinaId = DB::table('users')->where('name', 'OFICINA')->value('id');
            if (!$oficinaId) {
                $oficinaId = DB::table('users')->orderBy('id', 'asc')->value('id');
            }

            if ($oficinaId) {
                DB::table('sales')
                    ->whereNull('seller_id')
                    ->update(['seller_id' => $oficinaId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reverting backfilled data
    }
};
