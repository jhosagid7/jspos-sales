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
        Schema::table('customers', function (Blueprint $table) {
            // Control de Crédito
            $table->boolean('allow_credit')->default(false)->after('seller_id');
            $table->integer('credit_days')->nullable()->after('allow_credit');
            $table->decimal('credit_limit', 12, 2)->nullable()->after('credit_days');
            
            // Descuento por Divisa USD
            $table->decimal('usd_payment_discount', 5, 2)->nullable()->after('credit_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn([
                'allow_credit',
                'credit_days',
                'credit_limit',
                'usd_payment_discount'
            ]);
        });
    }
};
