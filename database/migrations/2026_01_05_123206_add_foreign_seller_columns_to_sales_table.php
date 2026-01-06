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
            $table->foreignId('seller_config_id')->nullable()->constrained('seller_configs');
            $table->decimal('applied_commission_percent', 5, 2)->nullable();
            $table->decimal('applied_freight_percent', 5, 2)->nullable();
            $table->decimal('applied_exchange_diff_percent', 5, 2)->nullable();
            $table->boolean('is_foreign_sale')->default(false);
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
