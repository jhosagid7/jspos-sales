<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('bag_products')) {
            Schema::create('bag_products', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('category')->nullable();
                $table->unsignedBigInteger('production_formula_id')->nullable();
                $table->string('sale_unit', 30)->default('BULTO');
                $table->string('sku', 50)->unique();
                $table->decimal('millar_per_bulto', 10, 4)->default(1.0000);
                $table->decimal('width_inch', 8, 2)->nullable();
                $table->decimal('length_inch', 8, 2)->nullable();
                $table->decimal('gauge_caliber', 10, 4)->nullable();
                $table->decimal('unit_weight_kg', 10, 4)->default(0.0000);
                $table->decimal('real_total_weight_kg', 10, 4)->default(0.0000);
                $table->decimal('margin_percentage', 8, 2)->default(45.00);
                $table->decimal('cost', 12, 4)->default(0.0000);
                $table->decimal('price', 12, 4)->default(0.0000);
                $table->decimal('price_tier_1', 12, 4)->default(0.0000);
                $table->decimal('price_tier_2', 12, 4)->default(0.0000);
                $table->decimal('price_tier_3', 12, 4)->default(0.0000);
                $table->integer('target_units_per_shift')->default(5);
                $table->decimal('target_daily_profit', 12, 4)->default(105.0000);
                $table->boolean('is_variable_quantity')->default(false);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('bag_products');
    }
};
