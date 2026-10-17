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
        Schema::create('work_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained('work_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete()->comment('Articulo de inventario si aplica');
            $table->foreignId('mechanic_id')->nullable()->constrained('users')->nullOnDelete()->comment('Mecanico que ejecuta la tarea');
            $table->string('type', 20)->default('part')->comment('part, labor');
            $table->string('description', 255);
            $table->decimal('quantity', 10, 2)->default(1.00);
            $table->decimal('unit_cost', 12, 2)->default(0.00);
            $table->decimal('unit_price', 12, 2)->default(0.00);
            $table->decimal('subtotal', 12, 2)->default(0.00);
            $table->decimal('mechanic_commission_percentage', 5, 2)->default(0.00)->comment('% comision sobre mano de obra');
            $table->decimal('mechanic_commission_amount', 12, 2)->default(0.00)->comment('Monto en divisa de comision');
            $table->boolean('is_approved')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_order_items');
    }
};
