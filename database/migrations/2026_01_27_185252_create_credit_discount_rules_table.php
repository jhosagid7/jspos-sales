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
        Schema::create('credit_discount_rules', function (Blueprint $table) {
            $table->id();
            
            // Entidad a la que aplica (jerárquico)
            $table->enum('entity_type', ['customer', 'seller', 'global'])->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            
            // Rango de días
            $table->integer('days_from')->comment('Desde qué día aplica (ej: 0, 6, 11, 16)');
            $table->integer('days_to')->nullable()->comment('Hasta qué día (NULL = infinito)');
            
            // Descuento/Recargo
            $table->decimal('discount_percentage', 5, 2)->comment('Positivo = descuento, Negativo = recargo');
            
            // Tipo de regla
            $table->enum('rule_type', ['early_payment', 'overdue']);
            
            // Descripción
            $table->string('description')->nullable()->comment('Ej: "Pronto pago 0-5 días"');
            
            $table->timestamps();
            
            // Índice compuesto para búsquedas rápidas
            $table->index(['entity_type', 'entity_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_discount_rules');
    }
};
