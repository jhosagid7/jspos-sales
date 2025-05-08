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
        Schema::table('payments', function (Blueprint $table) {
            $table->string('currency', 3)->after('amount')->nullable(); // Código de la moneda (ISO 4217)
            $table->decimal('exchange_rate', 15, 6)->after('currency')->nullable(); // Tasa de cambio
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('currency');
            $table->dropColumn('exchange_rate');
        });
    }
};
