<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\ProductWarehouse;
use App\Models\ProductItem;

class ZeroStockCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:zero';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set all stock in all warehouses to zero, including variable weight items (bobinas)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Iniciando el reseteo de stock a cero en todos los depósitos...");

        DB::beginTransaction();

        try {
            // 1. Set main product stock to 0
            Product::query()->update(['stock_qty' => 0]);
            $this->info("- Stock general de productos reseteado a 0.");

            // 2. Set warehouse specific stock to 0
            ProductWarehouse::query()->update(['stock_qty' => 0]);
            $this->info("- Stock por depósitos (ProductWarehouse) reseteado a 0.");

            // 3. Handle ProductItems (Bobinas)
            // Ya que se está reseteando el stock para un nuevo conteo, eliminamos los items ("bobinas")
            // que actualmente están como 'available' para que puedan ser escaneadas/creadas nuevamente con el nuevo conteo.
            ProductItem::query()
                ->where('status', 'available')
                ->delete();
            $this->info("- Items variables (Bobinas) disponibles han sido eliminadas para el reinicio.");

            DB::commit();
            $this->info("✅ El stock de todos los productos y depósitos ha sido puesto a CERO con éxito.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error al resetear el stock: " . $e->getMessage());
        }
    }
}
