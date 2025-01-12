<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateConfigurationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('configurations', function (Blueprint $table) {
            $table->id();
            $table->string('business_name', 150);
            $table->string('address')->nullable();
            $table->string('city', 55)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('taxpayer_id', 35)->nullable();
            $table->integer('vat')->default(0);
            $table->integer('decimals')->default(2);
            $table->string('printer_name', 55)->nullable();
            $table->string('leyend', 99)->nullable();
            $table->string('website', 99)->nullable();
            $table->timestamps();
            $table->integer('credit_days')->default(15);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('configurations');
    }
}
