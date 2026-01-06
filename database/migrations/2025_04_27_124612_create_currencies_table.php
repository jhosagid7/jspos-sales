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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('code', 3); // Código de la moneda (ISO 4217)
            $table->string('name'); // Nombre de la moneda
            $table->string('label'); // Nombre de la moneda
            $table->string('symbol'); // Nombre de la moneda
            $table->decimal('exchange_rate', 15, 6); // Tasa de cambio en relación a la moneda principal
            $table->boolean('is_primary')->default(false); // Indica si es la moneda principal
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
