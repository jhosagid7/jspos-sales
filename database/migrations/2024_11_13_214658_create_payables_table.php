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
        Schema::create('payables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('purchase_id');
            $table->decimal('amount', 10, 2);
            $table->enum('type', ['pay', 'settled']);
            $table->enum('pay_way', ['cash', 'deposit', 'nequi']);
            $table->string('bank', 99)->nullable();
            $table->string('account_number', 99)->nullable();
            $table->string('deposit_number', 99)->nullable();
            $table->text('phone_number')->nullable();
            $table->timestamps();

            $table->foreign('purchase_id', 'payables_purchase_id_foreign')->references('id')->on('purchases');
            $table->foreign('user_id', 'payables_user_id_foreign')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payables');
    }
};
