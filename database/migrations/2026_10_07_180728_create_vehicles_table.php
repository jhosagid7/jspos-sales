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
        Schema::create('vehicles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('plate', 20)->index();
            $table->string('brand', 100)->index();
            $table->string('model', 100);
            $table->unsignedSmallInteger('year')->nullable();
            $table->string('color', 50)->nullable();
            $table->string('vin', 50)->nullable()->comment('Serial de carroceria / Chasis');
            $table->string('engine_serial', 50)->nullable()->comment('Serial de motor');
            $table->string('displacement', 50)->nullable()->comment('Cilindrada, ej. 4.2L, 2.0 16V');
            $table->string('transmission', 30)->nullable()->comment('manual, automatica, sincronica');
            $table->string('fuel_type', 30)->default('gasolina')->comment('gasolina, diesel, gas, hibrido, electrico');
            $table->unsignedInteger('current_mileage')->default(0)->comment('Ultimo kilometraje registrado');
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
