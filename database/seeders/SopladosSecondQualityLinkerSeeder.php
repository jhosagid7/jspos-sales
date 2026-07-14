<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\Tag;

/**
 * Links finished soplados products to their second quality homolog product
 * via the second_quality_product_id field.
 *
 * BOTELLONES: All color variants (AZUL, VERDE, AMARILLO, ROJO, MORADO) share
 * ONE single second quality product → "BOTELLON DE 2DA" (or similar name)
 *
 * PET BOTTLES: Each size has its OWN second quality product:
 *   330ML  → ENVASE DE SEGUNDA PET 330ML
 *   500ML  → ENVASE DE SEGUNDA PET 500ML
 *   1000ML → ENVASES DE SEGUNDA PET 1000ML
 *   1500ML → ENVASES DE SEGUNDA PET BULTO 1500ML
 *   GALON  → ENVASE DE SEGUNDA PET GALON
 *
 * This seeder is idempotent (safe to run multiple times).
 */
class SopladosSecondQualityLinkerSeeder extends Seeder
{
    public function run(): void
    {
        $sopladosTag = Tag::firstOrCreate(['name' => 'soplados']);

        // --- 1. BOTELLONES (18.9L) → Single "BOTELLON DE 2DA" product ---
        // Find by name containing BOTELLON and 2DA/SEGUNDA and 18/18.9
        $botellon2da = Product::where(function ($q) {
                $q->where('name', 'like', '%BOTELLON%')
                  ->orWhere('name', 'like', '%BOTELLÓN%');
            })
            ->where(function ($q) {
                $q->where('name', 'like', '%2DA%')
                  ->orWhere('name', 'like', '%SEGUNDA%');
            })
            ->where('name', 'like', '%18%')
            ->first();

        // Fallback search if no specific 18.9L second quality was found
        if (!$botellon2da) {
            $botellon2da = Product::where(function ($q) {
                    $q->where('name', 'like', '%BOTELLON%')
                      ->orWhere('name', 'like', '%BOTELLÓN%');
                })
                ->where(function ($q) {
                    $q->where('name', 'like', '%2DA%')
                      ->orWhere('name', 'like', '%SEGUNDA%');
                })
                ->where('name', 'not like', '%12%') // exclude 12L
                ->first();
        }

        if ($botellon2da) {
            // Auto-tag the second quality product with 'soplados' if missing
            if (!$botellon2da->tags()->where('name', 'soplados')->exists()) {
                $botellon2da->tags()->attach($sopladosTag->id);
            }

            $updated = Product::where(function ($q) {
                    $q->where('name', 'like', '%BOTELLON 18.9%')
                      ->orWhere('name', 'like', '%BOTELLÓN 18.9%');
                })
                ->whereHas('tags', function ($q) {
                    $q->where('name', 'soplados');
                })
                ->where('id', '!=', $botellon2da->id)
                ->update(['second_quality_product_id' => $botellon2da->id]);

            $this->command->info("Botellones → {$botellon2da->name} (ID: {$botellon2da->id}) | {$updated} variante(s) vinculadas");
        } else {
            $this->command->warn("No se encontró producto 'BOTELLON DE 2DA'. Omitiendo botellones.");
        }

        // --- 2. PET BOTTLES — Each size links to its own second quality product ---
        $petMappings = [
            '330ML'  => ['330ML', '330'],
            '500ML'  => ['500ML', '500'],
            '1000ML' => ['1000ML', '1000', '1 LITRO', '1LTS'],
            '1500ML' => ['1500ML', '1500', 'BULTO 1500'],
            'GALON'  => ['GALON', 'GALÓN', 'GALON 3.785'],
        ];

        foreach ($petMappings as $sizeKey => $sizeTerms) {
            // Find the SEGUNDA product for this size without filtering by soplados tag initially
            $segunda = Product::where(function ($q) {
                    $q->where('name', 'like', '%2DA%')
                      ->orWhere('name', 'like', '%SEGUNDA%');
                })
                ->where(function ($q) use ($sizeTerms) {
                    $q->where(function ($inner) use ($sizeTerms) {
                        foreach ($sizeTerms as $term) {
                            $inner->orWhere('name', 'like', "%{$term}%");
                        }
                    });
                })
                ->first();

            if (!$segunda) {
                $this->command->warn("No se encontró producto de 2da para PET {$sizeKey}. Omitiendo.");
                continue;
            }

            // Auto-tag the second quality product with 'soplados' if missing
            if (!$segunda->tags()->where('name', 'soplados')->exists()) {
                $segunda->tags()->attach($sopladosTag->id);
            }

            // Find first-quality PET products of this size (exclude the segunda itself)
            $updated = Product::whereHas('tags', function ($q) {
                    $q->where('name', 'soplados');
                })
                ->where('is_raw_material', false)
                ->where(function ($q) use ($sizeTerms) {
                    foreach ($sizeTerms as $term) {
                        $q->orWhere('name', 'like', "%{$term}%");
                    }
                })
                ->where(function ($q) {
                    // Exclude anything that already is a segunda/2da product
                    $q->where('name', 'not like', '%2DA%')
                      ->where('name', 'not like', '%SEGUNDA%');
                })
                ->where('id', '!=', $segunda->id)
                ->update(['second_quality_product_id' => $segunda->id]);

            $this->command->info("PET {$sizeKey} → {$segunda->name} (ID: {$segunda->id}) | {$updated} producto(s) vinculados");
        }
    }
}
