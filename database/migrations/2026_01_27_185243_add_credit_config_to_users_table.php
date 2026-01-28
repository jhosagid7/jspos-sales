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
        Schema::table('users', function (Blueprint $table) {
            // Control de Crédito para Vendedores (aplica a todos sus clientes)
            $table->boolean('seller_allow_credit')->default(false)->after('status');
            $table->integer('seller_credit_days')->nullable()->after('seller_allow_credit');
            $table->decimal('seller_credit_limit', 12, 2)->nullable()->after('seller_credit_days');
            
            // Descuento por Divisa USD
            $table->decimal('seller_usd_payment_discount', 5, 2)->nullable()->after('seller_credit_limit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'seller_allow_credit',
                'seller_credit_days',
                'seller_credit_limit',
                'seller_usd_payment_discount'
            ]);
        });
    }
};
