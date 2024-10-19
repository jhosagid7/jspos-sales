<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('purchase_id');
            $table->integer('quantity');
            $table->decimal('cost', 10, 2);
            $table->decimal('flete_product', 10, 2);
            $table->decimal('flete_total', 10, 2);
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            
            $table->foreign('product_id', 'purchase_details_product_id_foreign')->references('id')->on('products');
            $table->foreign('purchase_id', 'purchase_details_purchase_id_foreign')->references('id')->on('purchases');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_details');
    }
}
