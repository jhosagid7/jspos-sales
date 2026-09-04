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
        if (!Schema::hasTable('bag_cost_settings')) {
            Schema::create('bag_cost_settings', function (Blueprint $table) {
                $table->id();
                $table->decimal('resin_price_per_kg', 10, 4)->default(1.4000);
                $table->decimal('shift_fixed_cost', 10, 4)->default(25.0000);
                $table->decimal('daily_profit_target', 10, 4)->default(100.0000);
                $table->decimal('margin_40_multiplier', 5, 2)->default(1.40);
                $table->decimal('margin_45_multiplier', 5, 2)->default(1.45);
                $table->decimal('margin_50_multiplier', 5, 2)->default(1.50);
                $table->decimal('margin_60_multiplier', 5, 2)->default(1.65);
                $table->decimal('tier1_multiplier', 5, 2)->default(1.10);
                $table->decimal('tier2_multiplier', 5, 2)->default(1.17);
                $table->decimal('tier3_multiplier', 5, 2)->default(1.21);
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bag_cost_settings');
    }
};
