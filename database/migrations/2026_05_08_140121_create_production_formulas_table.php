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
        Schema::create('production_formulas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade'); // Producto Terminado
            $table->foreignId('ingredient_id')->constrained('products')->onDelete('cascade'); // Insumo (Preforma/Tapa)
            $table->decimal('quantity', 10, 4); // Cantidad de insumo por 1 unidad de producto
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_formulas');
    }
};
