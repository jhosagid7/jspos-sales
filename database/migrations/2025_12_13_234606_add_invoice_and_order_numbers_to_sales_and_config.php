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
            $table->string('invoice_number')->nullable()->after('id');
            $table->string('order_number')->nullable()->after('invoice_number');
        });

        Schema::table('configurations', function (Blueprint $table) {
            $table->integer('invoice_sequence')->default(0);
            $table->integer('order_sequence')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['invoice_number', 'order_number']);
        });

        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn(['invoice_sequence', 'order_sequence']);
        });
    }
};
