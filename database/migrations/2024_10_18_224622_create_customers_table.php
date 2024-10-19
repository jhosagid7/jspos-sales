<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 45);
            $table->string('address')->nullable();
            $table->string('city', 55)->nullable();
            $table->string('email', 65)->nullable();
            $table->string('phone', 15)->nullable();
            $table->string('taxpayer_id')->nullable();
            $table->enum('type', ['Mayoristas', 'Consumidor Final', 'Descuento1', 'Descuento2', 'Otro'])->default('Mayoristas');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
