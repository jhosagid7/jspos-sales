<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class BagCatalogSyncService
{
    const CLOUD_URL = 'https://bolsas.plasticosmyf.com/api/sync-catalog';

    /**
     * Get all factory products according to standard filters.
     * Supplier: FABRICA BOLSA (ID: 10), Category: BOLSAS (ID: 2), or Tag: M&F.
     */
    public static function getFactoryProductsQuery()
    {
        return Product::query()
            ->where(function ($q) {
                $q->where('supplier_id', 10) // FABRICA BOLSA
                  ->orWhere('category_id', 2) // BOLSAS
                  ->orWhereHas('tags', function ($sub) {
                      $sub->where('name', 'M&F');
                  });
            });
    }

    /**
     * Synchronize entire factory catalog to JSBolsas Pro cloud.
     *
     * @return array [success => bool, count => int, message => string]0
     */
    public static function syncAll(): array
    {
        $products = self::getFactoryProductsQuery()
            ->orderBy('name')
            ->get(['id', 'sku', 'name', 'cost', 'price', 'is_variable_quantity'])
            ->toArray();

        $count = count($products);

        if ($count === 0) {
            return [
                'success' => true,
                'count' => 0,
                'message' => 'No se encontraron productos de fábrica para sincronizar.',
            ];
        }

        try {
            $response = Http::timeout(25)->post(self::CLOUD_URL, [
                'products' => $products,
            ]);

            if ($response->successful()) {
                Log::info("[BagCatalogSyncService] Sincronización exitosa hacia JSBolsas Pro: {$count} productos.");
                return [
                    'success' => true,
                    'count' => $count,
                    'message' => "Catálogo de fábrica sincronizado con éxito ({$count} productos actualizados/creados en JSBolsas Pro).",
                ];
            }

            Log::error("[BagCatalogSyncService] Error en respuesta de JSBolsas Cloud: " . $response->body());
            return [
                'success' => false,
                'count' => $count,
                'message' => "Error devuelto por el servidor JSBolsas Pro: " . ($response->json('message') ?? $response->status()),
            ];
        } catch (\Exception $e) {
            Log::error("[BagCatalogSyncService] Excepción de conexión con JSBolsas Cloud: " . $e->getMessage());
            return [
                'success' => false,
                'count' => $count,
                'message' => "No se pudo conectar con el servidor JSBolsas Pro (bolsas.plasticosmyf.com): " . $e->getMessage(),
            ];
        }
    }
}
