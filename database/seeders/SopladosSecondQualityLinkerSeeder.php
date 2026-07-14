<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

/**
 * Links finished soplados products to their second quality homolog product
 * via the second_quality_product_id field.
 *
 * For botellones: all colors (AZUL, VERDE, AMARILLO, ROJO, MORADO) → BOTELLON DE 2DA
 * For PET bottles: all sizes (330ML, 500ML, 1000ML, 1500ML, GALON) → PET DE 2DA (if exists)
 *
 * The second quality product must already exist with the 'soplados' tag.
 * This seeder is idempotent (safe to run multiple times).
 */
class SopladosSecondQualityLinkerSeeder extends Seeder
{
    public function run(): void
    {
        // --- 1. BOTELLONES (18.9L) → BOTELLON DE 2DA ---
        $botellon2da = Product::where('name', 'like', '%2DA%')
            ->where(function($q) {
                $q->where('name', 'like', '%BOTELLON%')
                  ->orWhere('name', 'like', '%BOTELLÓN%');
            })
            ->whereHas('tags', function($q) {
                $q->where('name', 'soplados');
            })
            ->first();

        if ($botellon2da) {
            // Link all botellón 18.9 color variants to the 2da product
            Product::where(function($q) {
                    $q->where('name', 'like', '%BOTELLON 18.9%')
                      ->orWhere('name', 'like', '%BOTELLÓN 18.9%');
                })
                ->whereHas('tags', function($q) {
                    $q->where('name', 'soplados');
                })
                ->where('id', '!=', $botellon2da->id)
                ->update(['second_quality_product_id' => $botellon2da->id]);

            $this->command->info("Linked botellón variants → {$botellon2da->name} (ID: {$botellon2da->id})");
        } else {
            $this->command->warn("No 'BOTELLON DE 2DA' product found. Skipping botellón linkage.");
        }

        // --- 2. PET BOTTLES → PET DE 2DA (if exists) ---
        $pet2da = Product::where('name', 'like', '%PET%2DA%')
            ->whereHas('tags', function($q) {
                $q->where('name', 'soplados');
            })
            ->first();

        if ($pet2da) {
            // Link all PET bottle sizes to the PET 2da product
            Product::whereHas('tags', function($q) {
                    $q->where('name', 'soplados');
                })
                ->where('is_raw_material', false)
                ->where(function($q) {
                    $q->where('name', 'like', '%330ML%')
                      ->orWhere('name', 'like', '%500ML%')
                      ->orWhere('name', 'like', '%1000ML%')
                      ->orWhere('name', 'like', '%1500ML%')
                      ->orWhere('name', 'like', '%GALON%')
                      ->orWhere('name', 'like', '%GALÓN%');
                })
                ->where('id', '!=', $pet2da->id)
                ->update(['second_quality_product_id' => $pet2da->id]);

            $this->command->info("Linked PET bottle variants → {$pet2da->name} (ID: {$pet2da->id})");
        } else {
            $this->command->warn("No 'PET DE 2DA' product found. PET bottles will not show 2da calidad in inventory until this product is created.");
        }
    }
}
