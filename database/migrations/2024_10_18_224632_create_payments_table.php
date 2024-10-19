<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('sale_id');
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['pay', 'settled']);
            $table->enum('pay_way', ['cash', 'deposit', 'nequi']);
            $table->string('bank', 99)->nullable();
            $table->string('account_number', 99)->nullable();
            $table->string('deposit_number', 99)->nullable();
            $table->text('phone_number')->nullable();
            $table->timestamps();
            
            $table->foreign('sale_id', 'payments_sale_id_foreign')->references('id')->on('sales');
            $table->foreign('user_id', 'payments_user_id_foreign')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('payments');
    }
}
