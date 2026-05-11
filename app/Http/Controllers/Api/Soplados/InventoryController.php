<?php

namespace App\Http\Controllers\Api\Soplados;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductionFormula;
use App\Models\Transfer;
use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryController extends Controller
{
    /**
     * Get the current stock of finished products and raw materials in the plant.
     */
    public function index()
    {
        $config = Configuration::first();
        $soplados_id = $config->soplados_warehouse_id ?? $config->default_warehouse_id ?? 1;
        $warehouse_id = auth()->user()->warehouse_id ?? $soplados_id;

        // 1. Get Finished Products (Tagged with 'soplados')
        $finishedProductIds = Product::whereHas('tags', function($q) {
                $q->where('name', 'soplados');
            })->pluck('id');

        // 2. Get Raw Materials (Ingredients for Soplados formulas)
        $ingredientIds = ProductionFormula::whereIn('product_id', $finishedProductIds)
            ->distinct()
            ->pluck('ingredient_id');

        // Merge all relevant IDs
        $allIds = $finishedProductIds->merge($ingredientIds)->unique();

        // 3. Query stock in the plant warehouse
        $inventory = Product::with(['productWarehouses' => function($q) use ($warehouse_id) {
                $q->where('warehouse_id', $warehouse_id);
            }])
            ->whereIn('id', $allIds)
            ->get()
            ->map(function($p) use ($ingredientIds) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'stock' => $p->productWarehouses->first()->stock_qty ?? 0,
                    'type' => $ingredientIds->contains($p->id) ? 'Insumo/Materia Prima' : 'Producto Terminado'
                ];
            });

        return response()->json([
            'success' => true,
            'warehouse' => \App\Models\Warehouse::find($warehouse_id)->name ?? 'Planta',
            'inventory' => $inventory
        ]);
    }

    /**
     * List transfers sent TO this plant that have not been received yet.
     */
    public function pendingReceipts()
    {
        $config = Configuration::first();
        $soplados_id = $config->soplados_warehouse_id ?? $config->default_warehouse_id ?? 1;
        $warehouse_id = auth()->user()->warehouse_id ?? $soplados_id;

        $transfers = Transfer::with(['details.product', 'fromWarehouse'])
            ->where('to_warehouse_id', $warehouse_id)
            ->where(function($q) {
                $q->where('status', 'pending')
                  ->orWhere('status', 'Pending')
                  ->orWhere('status', 'dispatched')
                  ->orWhere('status', 'Dispatched');
            })
            ->get()
            ->map(function($t) {
                $t->origin_name = $t->fromWarehouse->name ?? 'Almacén Central';
                foreach($t->details as $d) {
                    $d->product_name = $d->product->name ?? 'Producto';
                }
                return $t;
            });

        return response()->json(['success' => true, 'transfers' => $transfers]);
    }

    /**
     * Accept a transfer, adding the stock to the plant.
     */
    public function receiveReceipt(Request $request, $id)
    {
        $transfer = Transfer::with('details')->findOrFail($id);

        if (in_array(strtolower($transfer->status), ['completed', 'received'])) {
            return response()->json(['success' => false, 'message' => 'Este traspaso ya fue recibido.'], 400);
        }

        try {
            DB::beginTransaction();

            // Mark as completed/received
            $transfer->update([
                'status' => 'completed',
                'note' => $transfer->note . ' [Recibido en Planta por App]'
            ]);

            // Add stock to the plant warehouse
            foreach ($transfer->details as $detail) {
                $this->updateStock($detail->product_id, $transfer->to_warehouse_id, $detail->quantity);
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Insumos recibidos y cargados al inventario de planta.']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al recibir insumos: ' . $e->getMessage()], 500);
        }
    }

    private function updateStock($productId, $warehouseId, $quantity)
    {
        $pw = \App\Models\ProductWarehouse::firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['stock_qty' => 0]
        );

        $pw->stock_qty += $quantity;
        $pw->save();

        $config = Configuration::first();
        $defaultWarehouseId = $config->default_warehouse_id ?? \App\Models\Warehouse::first()->id ?? 1;

        if ($warehouseId == $defaultWarehouseId) {
            $product = Product::find($productId);
            if ($product) {
                $product->stock_qty += $quantity;
                $product->save();
            }
        }
    }
}
