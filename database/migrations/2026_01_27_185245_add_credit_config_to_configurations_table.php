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
        Schema::table('configurations', function (Blueprint $table) {
            // Control de Crédito Global
            $table->boolean('global_allow_credit')->default(true)->after('invoice_sequence');
            $table->integer('global_credit_days')->default(30)->after('global_allow_credit');
            $table->decimal('global_credit_limit', 12, 2)->nullable()->after('global_credit_days');
            
            // Descuento por Divisa USD
            $table->decimal('global_usd_payment_discount', 5, 2)->nullable()->after('global_credit_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn([
                'global_allow_credit',
                'global_credit_days',
                'global_credit_limit',
                'global_usd_payment_discount'
            ]);
        });
    }
};
