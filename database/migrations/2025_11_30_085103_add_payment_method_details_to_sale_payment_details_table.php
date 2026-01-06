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
        Schema::table('sale_payment_details', function (Blueprint $table) {
            $table->string('payment_method')->default('cash')->after('sale_id'); // cash, bank, nequi
            // $table->string('bank_name')->nullable()->after('amount_in_primary_currency'); // Ya existe
            $table->string('account_number')->nullable()->after('amount_in_primary_currency');
            $table->string('reference_number')->nullable()->after('account_number'); // Para número de depósito
            $table->string('phone_number')->nullable()->after('reference_number'); // Para Nequi
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_payment_details', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'account_number', 'reference_number', 'phone_number']);
        });
    }
};
