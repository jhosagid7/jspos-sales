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
        Schema::table('zelle_records', function (Blueprint $table) {
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->decimal('invoice_total', 10, 2)->nullable();
            $table->string('payment_type')->nullable(); // 'full', 'partial'
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('zelle_records', function (Blueprint $table) {
            $table->dropColumn([
                'customer_id',
                'sale_id',
                'invoice_total',
                'payment_type'
            ]);
        });
    }
};
