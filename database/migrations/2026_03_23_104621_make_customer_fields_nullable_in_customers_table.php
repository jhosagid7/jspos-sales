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
            // Make Taxpayer ID optional at database level
            $table->string('taxpayer_id', 45)->nullable()->change();
            
            // Make USD Tag optional at database level
            $table->string('usd_payment_discount_tag', 10)->nullable()->change();
            
            // Ensure Wallet Balance handles null correctly
            $table->decimal('wallet_balance', 15, 2)->nullable()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('taxpayer_id', 45)->nullable(false)->change();
            $table->string('usd_payment_discount_tag', 10)->nullable(false)->change();
            $table->decimal('wallet_balance', 15, 2)->nullable(false)->default(0)->change();
        });
    }
};
