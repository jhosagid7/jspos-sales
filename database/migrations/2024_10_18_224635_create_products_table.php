<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('sku', 25)->nullable();
            $table->string('name', 60);
            $table->text('description')->nullable();
            $table->enum('type', ['service', 'physical'])->default('physical');
            $table->enum('status', ['available', 'out_of_stock'])->default('available');
            $table->decimal('cost', 10, 2);
            $table->decimal('price', 10, 2);
            $table->boolean('manage_stock')->default(1);
            $table->integer('stock_qty');
            $table->integer('low_stock');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('category_id');
            $table->timestamp('deleted_at')->nullable();
            $table->timestamps();
            
            $table->foreign('category_id', 'products_category_id_foreign')->references('id')->on('categories');
            $table->foreign('supplier_id', 'products_supplier_id_foreign')->references('id')->on('suppliers');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('products');
    }
}
