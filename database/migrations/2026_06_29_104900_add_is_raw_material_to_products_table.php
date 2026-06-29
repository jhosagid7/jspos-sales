<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_raw_material')->default(false)->after('type');
        });

        // 1. Mark existing ingredients as raw materials
        $ingredientIds = DB::table('production_formulas')->pluck('ingredient_id')->unique()->toArray();
        if (!empty($ingredientIds)) {
            DB::table('products')->whereIn('id', $ingredientIds)->update(['is_raw_material' => true]);
        }

        // 2. Find or create the 'soplados' tag
        $tagId = DB::table('tags')->where('name', 'soplados')->value('id');
        if (!$tagId) {
            $tagId = DB::table('tags')->insertGetId([
                'name' => 'soplados',
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // 3. Tag finished products and ingredients in production formulas as soplados
        $productIds = DB::table('production_formulas')->pluck('product_id')->unique()->toArray();
        $allSopladosIds = array_unique(array_merge($productIds, $ingredientIds));

        foreach ($allSopladosIds as $prodId) {
            $exists = DB::table('product_tags')
                ->where('product_id', $prodId)
                ->where('tag_id', $tagId)
                ->exists();
            if (!$exists) {
                DB::table('product_tags')->insert([
                    'product_id' => $prodId,
                    'tag_id' => $tagId
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_raw_material');
        });
    }
};
