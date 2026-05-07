<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // En MySQL, cambiar de ENUM a VARCHAR es seguro y previene errores de truncado
        // Agregamos los tipos faltantes: sale_cancellation, sale_edit_reversal
        Schema::table('cash_movements', function (Blueprint $table) {
            $table->string('type', 50)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_movements', function (Blueprint $table) {
            $table->enum('type', ['opening', 'sale_payment', 'sale_change', 'adjustment', 'closing'])->change();
        });
    }
};
