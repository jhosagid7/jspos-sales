<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add second_quality_product_id to products table.
     * 
     * This field points to the "second quality" product that corresponds to
     * this finished product. For example:
     *   - ENVASE PET 330ML → ENVASE PET DE 2DA (or similar second quality product)
     *   - BOTELLÓN 18.9 AZUL → BOTELLÓN DE 2DA
     *
     * This is separate from production_target_id, which is used for grouping
     * color variants under a base product for production target compliance.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->foreignId('second_quality_product_id')
                ->nullable()
                ->after('production_target_id')
                ->constrained('products')
                ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['second_quality_product_id']);
            $table->dropColumn('second_quality_product_id');
        });
    }
};
