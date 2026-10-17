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
        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 30)->unique()->index();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('vehicle_id')->constrained('vehicles')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->comment('Asesor o recepcionista creador');
            $table->foreignId('mechanic_id')->nullable()->constrained('users')->nullOnDelete()->comment('Mecanico lider asignado');
            $table->foreignId('sale_id')->nullable()->constrained('sales')->nullOnDelete()->comment('Venta generada al facturar');
            $table->unsignedInteger('entry_mileage')->default(0)->comment('Kilometraje al recibir');
            $table->unsignedInteger('exit_mileage')->nullable()->comment('Kilometraje al entregar');
            $table->string('fuel_level', 20)->nullable()->comment('Nivel tanque: reserva, 1/4, 1/2, 3/4, lleno');
            $table->text('initial_diagnosis')->comment('Motivo o diagnostico preliminar');
            $table->text('technical_notes')->nullable()->comment('Notas tecnicas o hallazgos del taller');
            $table->string('status', 30)->default('reception')->index()->comment('reception, budgeted, approved, in_progress, ready, delivered, invoiced, cancelled');
            $table->dateTime('promised_at')->nullable()->comment('Fecha y hora prometida de entrega');
            $table->dateTime('delivered_at')->nullable()->comment('Fecha y hora real de entrega');
            $table->decimal('subtotal_parts', 12, 2)->default(0.00);
            $table->decimal('subtotal_labor', 12, 2)->default(0.00);
            $table->decimal('tax', 12, 2)->default(0.00);
            $table->decimal('total', 12, 2)->default(0.00);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_orders');
    }
};
