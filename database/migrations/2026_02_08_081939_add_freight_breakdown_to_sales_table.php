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
            if (!Schema::hasColumn('sales', 'is_freight_broken_down')) {
                $table->boolean('is_freight_broken_down')->default(false)->after('total');
            }
            if (!Schema::hasColumn('sales', 'total_freight')) {
                $table->decimal('total_freight', 10, 2)->default(0)->after('is_freight_broken_down');
            }
        });

        Schema::table('sale_details', function (Blueprint $table) {
             if (!Schema::hasColumn('sale_details', 'freight_amount')) {
                 $table->decimal('freight_amount', 10, 2)->default(0)->after('sale_price');
             }
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
