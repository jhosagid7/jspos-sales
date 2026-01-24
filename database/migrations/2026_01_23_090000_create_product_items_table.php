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
        Schema::create('product_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained()->onDelete('cascade');
            $table->decimal('quantity', 10, 2); // Represents current weight
            $table->decimal('original_quantity', 10, 2); // Represents initial weight
            $table->enum('status', ['available', 'reserved', 'sold', 'consumed', 'discharged'])->default('available');
            $table->string('batch')->nullable();
            $table->string('color')->nullable(); // Optional as requested
            $table->string('location')->nullable();
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_variable_quantity')->default(false)->after('allow_decimal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_items');

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_variable_quantity');
        });
    }
};
