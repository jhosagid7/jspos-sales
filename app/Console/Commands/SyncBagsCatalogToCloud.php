<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\BagCatalogSyncService;

class SyncBagsCatalogToCloud extends Command
{
    protected $signature = 'bolsas:sync-catalog';
    protected $description = 'Sincroniza el catálogo de productos de fábrica (Bolsas/Bobinas) desde JSPOS hacia JSBolsas Cloud';

    public function handle()
    {
        $this->info('Consultando productos de fábrica en base de datos local...');

        $result = BagCatalogSyncService::syncAll();

        if ($result['success']) {
            $this->info("✅ {$result['message']}");
            return 0;
        } else {
            $this->error("❌ {$result['message']}");
            return 1;
        }
    }
}
