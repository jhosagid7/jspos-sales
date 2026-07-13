<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\SopladosProductionTarget;

class SopladosProductionTargetSeeder extends Seeder
{
    public function run(): void
    {
        // Auto-link variants to their base products to avoid NULL production_target_id on client databases
        $baseBotellon = Product::where('name', 'like', 'BOTELLON 18.9%AZUL%')
            ->orWhere('name', 'like', 'BOTELLON 18.9%BLUE%')
            ->first();
            
        if ($baseBotellon) {
            Product::where('name', 'like', 'BOTELLON 18.9%')
                ->where('id', '!=', $baseBotellon->id)
                ->update(['production_target_id' => $baseBotellon->id]);
        }

        $baseGalon = Product::where('name', 'ENVASE PET GALON 3.785 42UND')->first();
        if ($baseGalon) {
            Product::where('name', 'like', 'ENVASE PET GALON 3.785 42UND%')
                ->where('id', '!=', $baseGalon->id)
                ->update(['production_target_id' => $baseGalon->id]);
        }

        $products = Product::whereHas('tags', function($q) {
            $q->where('name', 'soplados');
        })->where('is_raw_material', false)->get();

        foreach ($products as $product) {
            $name = strtoupper($product->name);
            $min = 0;
            $max = 0;

            if (str_contains($name, '330ML') || str_contains($name, '300ML')) {
                $min = 3500;
                $max = 4000;
            } elseif (str_contains($name, '500ML')) {
                $min = 3500;
                $max = 4000;
            } elseif (str_contains($name, '1000ML') || str_contains($name, '1 LITRO') || str_contains($name, '1LTS')) {
                $min = 3450;
                $max = 3800;
            } elseif (str_contains($name, '1500ML') || str_contains($name, '1.5')) {
                $min = 3000;
                $max = 3600;
            } elseif (str_contains($name, 'GALON') || str_contains($name, 'GALÓN')) {
                $min = 1200;
                $max = 1600;
            } elseif (str_contains($name, 'BOTELLON') || str_contains($name, 'BOTELLÓN') || str_contains($name, '18 LTS') || str_contains($name, '18.9')) {
                $min = 390;
                $max = 416;
            }

            if ($min > 0 && $max > 0) {
                SopladosProductionTarget::firstOrCreate(
                    ['product_id' => $product->id],
                    ['min_target' => $min, 'max_target' => $max]
                );
            }
        }
    }
}
