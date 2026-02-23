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
            $table->boolean('whatsapp_notify_sales')->default(true)->after('usd_payment_discount');
            $table->boolean('whatsapp_notify_payments')->default(true)->after('whatsapp_notify_sales');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['whatsapp_notify_sales', 'whatsapp_notify_payments']);
        });
    }
};
