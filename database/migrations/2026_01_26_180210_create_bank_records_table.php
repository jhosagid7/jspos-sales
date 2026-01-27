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
        Schema::create('bank_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained('banks');
            $table->date('payment_date');
            $table->decimal('amount', 10, 2);
            $table->string('reference');
            $table->string('image_path')->nullable();
            $table->text('note')->nullable();
            $table->unsignedBigInteger('sale_id')->nullable(); // Can be null if created for a credit payment independent of a sale? No, usually linked.
            $table->unsignedBigInteger('payment_id')->nullable(); // For credit payments (Abonos)
            $table->string('status')->default('unused');
            $table->decimal('remaining_balance', 10, 2)->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_records');
    }
};
