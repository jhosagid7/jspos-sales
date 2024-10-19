<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->decimal('total', 10, 2);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->decimal('cash', 10, 2)->default(0.00);
            $table->decimal('change', 10, 2)->default(0.00);
            $table->integer('items');
            $table->enum('status', ['paid', 'returned', 'pending'])->default('paid');
            $table->enum('type', ['credit', 'cash', 'deposit', 'nequi', 'cash/nequi'])->default('cash');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('user_id');
            $table->string('notes')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            
            $table->foreign('customer_id', 'sales_customer_id_foreign')->references('id')->on('customers');
            $table->foreign('user_id', 'sales_user_id_foreign')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sales');
    }
}
