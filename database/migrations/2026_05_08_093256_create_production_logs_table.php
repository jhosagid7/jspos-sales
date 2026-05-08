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
        Schema::create('production_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shift_id')->constrained('shifts')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('production_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_log_id')->constrained('production_logs')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->comment('Raw Material / Insumo');
            $table->decimal('quantity', 10, 2);
            $table->timestamps();
        });

        Schema::create('production_outputs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_log_id')->constrained('production_logs')->onDelete('cascade');
            $table->foreignId('product_id')->nullable()->constrained('products')->comment('Finished Good. Null if just damaged tracking without specific product.');
            $table->decimal('quantity', 10, 2);
            $table->enum('quality', ['1st', '2nd', 'damaged'])->default('1st');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('production_outputs');
        Schema::dropIfExists('production_materials');
        Schema::dropIfExists('production_logs');
    }
};
