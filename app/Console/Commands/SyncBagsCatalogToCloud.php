<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Product;
use Illuminate\Support\Facades\Http;

class SyncBagsCatalogToCloud extends Command
{
    protected $signature = 'bolsas:sync-catalog';
    protected $description = 'Sincroniza el catálogo de productos de fábrica (Bolsas/Bobinas) desde JSPOS hacia JSBolsas Cloud';

    public function handle()
    {
        $this->info('Consultando productos de fábrica en base de datos local...');

        $products = Product::query()
            ->where(function ($q) {
                $q->where('supplier_id', 10) // FABRICA BOLSA
                  ->orWhere('category_id', 2) // BOLSAS
                  ->orWhereHas('tags', function ($sub) {
                      $sub->where('name', 'M&F');
                  });
            })
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'cost', 'price', 'is_variable_quantity'])
            ->toArray();

        $count = count($products);
        $this->info("Se encontraron {$count} productos de fábrica.");

        try {
            $response = Http::timeout(20)->post('https://bolsas.plasticosmyf.com/api/sync-catalog', [
                'products' => $products,
            ]);

            if ($response->successful()) {
                $this->info("✅ Catálogo sincronizado con éxito a la nube: {$count} productos.");
                return 0;
            } else {
                $this->error("❌ Error en el servidor nube: " . $response->body());
                return 1;
            }
        } catch (\Exception $e) {
            $this->error("❌ Error de conexión: " . $e->getMessage());
            return 1;
        }
    }
}
