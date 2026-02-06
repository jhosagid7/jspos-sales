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
            $table->decimal('bcv_rate', 20, 10)->nullable();
            $table->decimal('binance_rate', 20, 10)->nullable();
        });

        Schema::create('exchange_rate_histories', function (Blueprint $table) {
            $table->id();
            $table->string('rate_type'); // 'BCV', 'Binance'
            $table->decimal('rate', 20, 10);
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_rate_histories');

        Schema::table('configurations', function (Blueprint $table) {
            $table->dropColumn(['bcv_rate', 'binance_rate']);
        });
    }
};
