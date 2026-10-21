<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VpsSyncService;

class SyncVpsOrdersCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'vps:sync {--push-catalog : Subir catálogo al VPS} {--push-customers : Subir clientes al VPS} {--pull-orders : Descargar pedidos pendientes}';

    /**
     * The console command description.
     */
    protected $description = 'Sincronizar catálogo, clientes y órdenes con el servidor VPS de pedidos';

    /**
     * Execute the console command.
     */
    public function handle(VpsSyncService $syncService): int
    {
        $this->info("Iniciando sincronización con VPS...");

        $pushCatalog = $this->option('push-catalog');
        $pushCustomers = $this->option('push-customers');
        $pullOrders = $this->option('pull-orders');

        $runAll = !$pushCatalog && !$pushCustomers && !$pullOrders;

        if ($runAll || $pushCatalog) {
            $this->line("1. Subiendo catálogo de productos...");
            $res = $syncService->pushCatalog();
            if ($res['success']) {
                $this->info("✓ Catálogo sincronizado (" . ($res['count'] ?? 0) . " productos).");
            } else {
                $this->error("✗ Error en catálogo: " . ($res['message'] ?? 'Desconocido'));
            }
        }

        if ($runAll || $pushCustomers) {
            $this->line("2. Subiendo clientes y listas de precios...");
            $res = $syncService->pushCustomers();
            if ($res['success']) {
                $this->info("✓ Clientes sincronizados (" . ($res['count'] ?? 0) . " clientes).");
            } else {
                $this->error("✗ Error en clientes: " . ($res['message'] ?? 'Desconocido'));
            }
        }

        if ($runAll || $pullOrders) {
            $this->line("3. Descargando pedidos pendientes del VPS...");
            $res = $syncService->pullPendingOrders();
            if ($res['success']) {
                $this->info("✓ Pedidos descargados e ingresados: " . ($res['ingested_orders'] ?? 0));
            } else {
                $this->error("✗ Error descargando pedidos: " . ($res['message'] ?? 'Desconocido'));
            }
        }

        $this->info("Sincronización finalizada.");
        return 0;
    }
}
