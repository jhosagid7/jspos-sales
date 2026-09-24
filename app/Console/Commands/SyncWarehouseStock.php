<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\Configuration;
use App\Models\Warehouse;

class SyncWarehouseStock extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:sync-default-warehouse {--dry-run : Show differences without applying updates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Synchronize products.stock_qty with the stock_qty of the default warehouse';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');

        $config = Configuration::first();
        $defaultWarehouse = Warehouse::find($config?->default_warehouse_id) ?? Warehouse::first();

        if (!$defaultWarehouse) {
            $this->error("No se encontró ningún depósito en el sistema.");
            return 1;
        }

        $this->info("Depósito Principal: [ID: {$defaultWarehouse->id}] {$defaultWarehouse->name}");
        if ($dryRun) {
            $this->warn("MODO SIMULACIÓN (DRY-RUN): No se aplicarán cambios a la base de datos.");
        }

        $products = Product::all();
        $syncedCount = 0;

        DB::beginTransaction();
        try {
            foreach ($products as $product) {
                $pw = ProductWarehouse::firstOrCreate(
                    ['product_id' => $product->id, 'warehouse_id' => $defaultWarehouse->id],
                    ['stock_qty' => 0]
                );

                $currentProductStock = (float) $product->stock_qty;
                $currentWarehouseStock = (float) $pw->stock_qty;

                if (abs($currentProductStock - $currentWarehouseStock) > 0.0001) {
                    $this->line("Producto ID {$product->id} ({$product->name}): Stock Actual = {$currentProductStock} -> Nuevo Stock = {$currentWarehouseStock}");

                    if (!$dryRun) {
                        Product::where('id', $product->id)->update(['stock_qty' => $currentWarehouseStock]);
                    }
                    $syncedCount++;
                }
            }

            if (!$dryRun) {
                DB::commit();
                $this->info("✅ Sincronización completada. Se actualizaron {$syncedCount} producto(s).");
            } else {
                DB::rollBack();
                $this->info("ℹ️ Simulación completada. {$syncedCount} producto(s) presentan diferencias de stock.");
            }

            return 0;
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error durante la sincronización: " . $e->getMessage());
            return 1;
        }
    }
}
