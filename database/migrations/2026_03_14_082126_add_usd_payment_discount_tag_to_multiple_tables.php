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
            $table->string('global_usd_payment_discount_tag', 10)->default('PD')->after('global_usd_payment_discount');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->string('usd_payment_discount_tag', 10)->default('PD')->after('usd_payment_discount');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->string('seller_usd_payment_discount_tag', 10)->default('PD')->after('seller_usd_payment_discount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn('global_usd_payment_discount_tag');
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('usd_payment_discount_tag');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('seller_usd_payment_discount_tag');
        });
    }
};
