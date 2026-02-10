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
        Schema::table('products', function (Blueprint $table) {
            // Freight Type: 'none', 'percentage', 'fixed'
            if (!Schema::hasColumn('products', 'freight_type')) {
                $table->string('freight_type')->default('none')->after('stock_qty'); 
            }
            // Freight Value: e.g. 10.00 (represents % or amount)
            if (!Schema::hasColumn('products', 'freight_value')) {
                $table->decimal('freight_value', 10, 2)->default(0)->after('freight_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            //
        });
    }
};
