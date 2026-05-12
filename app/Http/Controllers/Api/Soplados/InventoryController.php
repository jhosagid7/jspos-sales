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
                // Determine type: Insumo vs Producto Terminado
                $type = 'Producto Terminado';
                
                // Fallback keywords for materials
                $isMaterial = false;
                $materialsKeywords = ['PREFORMA', 'TAPA', 'ASA', 'ETIQUETA', 'RESINA', 'LINER', 'TAPON', 'INGREDIENTE'];
                $nameUpper = strtoupper($p->name);
                foreach($materialsKeywords as $kw) {
                    if (strpos($nameUpper, $kw) !== false) {
                        $isMaterial = true;
                        break;
                    }
                }

                if ($ingredientIds->contains($p->id) || $isMaterial) {
                    $type = 'Insumo/Materia Prima';
                }

                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'stock' => $p->productWarehouses->first()->stock_qty ?? 0,
                    'type' => $type
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
     * Supports partial receipts.
     */
    public function receiveReceipt(Request $request, $id)
    {
        $transfer = Transfer::with('details')->findOrFail($id);

        if (in_array(strtolower($transfer->status), ['completed', 'received', 'completed_partial'])) {
            return response()->json(['success' => false, 'message' => 'Este traspaso ya fue procesado.'], 400);
        }

        $receivedItems = $request->input('items', []); // Array of {id: detail_id, received: qty}
        $rejectionReason = $request->input('rejection_reason', '');

        try {
            DB::beginTransaction();

            $hasRejections = false;

            // Process each item
            foreach ($transfer->details as $detail) {
                // Find received quantity for this detail in the request
                $received = $detail->quantity; // Default to full
                foreach ($receivedItems as $ri) {
                    if ($ri['id'] == $detail->id) {
                        $received = floatval($ri['received']);
                        break;
                    }
                }

                if ($received > $detail->quantity) $received = $detail->quantity;
                if ($received < 0) $received = 0;

                $rejected = $detail->quantity - $received;
                if ($rejected > 0) $hasRejections = true;

                // Update detail
                $detail->update([
                    'received_quantity' => $received,
                    'rejected_quantity' => $rejected
                ]);

                // 1. Deduct from origin if it wasn't already dispatched (Still Pending)
                // If it was already 'dispatched', it was already deducted in dispatchTransfer()
                if (strtolower($transfer->status) === 'pending') {
                    $this->updateStock($detail->product_id, $transfer->from_warehouse_id, -$detail->quantity);
                }

                // 2. Add received stock to destination (Plant)
                if ($received > 0) {
                    $this->updateStock($detail->product_id, $transfer->to_warehouse_id, $received);
                }
                
                // Note: Rejected quantities remain out of both inventories until 'receiveReturn' is called.
            }

            // Mark transfer as processed
            $transfer->update([
                'status' => $hasRejections ? 'completed_partial' : 'completed',
                'rejection_reason' => $rejectionReason,
                'note' => $transfer->note . ($hasRejections ? ' [Recibido Parcial]' : ' [Recibido Total]')
            ]);

            DB::commit();
            return response()->json([
                'success' => true, 
                'message' => $hasRejections ? 'Recepción parcial registrada correctamente.' : 'Insumos recibidos y cargados al inventario.'
            ]);

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
