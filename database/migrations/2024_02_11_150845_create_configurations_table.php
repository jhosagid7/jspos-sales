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
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->string('business_name', 150); //nombre del negocio
            $table->string('address')->nullable(); // direccion
            $table->string('city')->nullable(); // ciudad
            $table->string('phone', 20)->nullable();
            $table->string('taxpayer_id', 35)->nullable(); //rfc, rut, ruc
            $table->integer('vat')->default(0); //iva
            $table->string('printer_name', 55)->nullable();  //impresora default
            $table->string('leyend', 99)->nullable(); // gracias por su compra
            $table->string('website', 99)->nullable(); //misitioweb.com
            $table->timestamps();
            $table->integer('credit_days')->default(15)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configurations');
    }
};
