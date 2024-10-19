<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchasesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->decimal('total', 10, 2);
            $table->decimal('flete', 10, 2)->default(0.00);
            $table->decimal('discount', 10, 2)->default(0.00);
            $table->integer('items');
            $table->enum('status', ['paid', 'returned', 'pending'])->default('paid');
            $table->enum('type', ['credit', 'cash'])->default('cash');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('user_id');
            $table->string('notes')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            
            $table->foreign('supplier_id', 'purchases_supplier_id_foreign')->references('id')->on('suppliers');
            $table->foreign('user_id', 'purchases_user_id_foreign')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchases');
    }
}
