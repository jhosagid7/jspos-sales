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

        $secondQualityDestinationIds = Product::whereNotNull('second_quality_product_id')
            ->pluck('second_quality_product_id')
            ->unique()
            ->all();

        // 1. Get Soplados Products (Tagged with 'soplados', excluding target second quality products)
        $allIds = Product::whereHas('tags', function($q) {
                $q->where('name', 'soplados');
            })
            ->whereNotIn('id', $secondQualityDestinationIds)
            ->pluck('id');

        // 2. Query stock in the plant warehouse
        $inventory = Product::with(['productWarehouses' => function($q) use ($warehouse_id) {
                $q->where('warehouse_id', $warehouse_id);
            }])
            ->whereIn('id', $allIds)
            ->get()
            ->map(function($p) {
                return [
                    'id' => $p->id,
                    'name' => $p->name,
                    'sku' => $p->sku,
                    'stock' => $p->productWarehouses->first()->stock_qty ?? 0,
                    'type' => $p->is_raw_material ? 'Insumo/Materia Prima' : 'Producto Terminado'
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

    /**
     * Get the list of products (finished and raw materials) with current stock for count.
     */
    public function productsForCount()
    {
        abort_if(!auth()->user()->can('soplados.manager'), 403, 'No tienes permisos de supervisor de soplados.');

        $config = Configuration::first();
        $soplados_id = $config->soplados_warehouse_id ?? $config->default_warehouse_id ?? 1;
        $warehouse_id = auth()->user()->warehouse_id ?? $soplados_id;

        $secondQualityDestinationIds = Product::whereNotNull('second_quality_product_id')
            ->pluck('second_quality_product_id')
            ->unique()
            ->all();

        // 1. Get Finished Products (Tagged with 'soplados' and is_raw_material = false, excluding target second quality products)
        $finishedProducts = Product::with(['productionTarget', 'secondQualityProduct', 'productWarehouses' => function($q) use ($warehouse_id) {
                $q->where('warehouse_id', $warehouse_id);
            }])
            ->whereHas('tags', function($q) {
                $q->where('name', 'soplados');
            })
            ->whereNotIn('id', $secondQualityDestinationIds)
            ->where('is_raw_material', false)
            ->get();

        // 2. Get Raw Materials (Tagged with 'soplados' and is_raw_material = true)
        $rawMaterials = Product::with(['productWarehouses' => function($q) use ($warehouse_id) {
                $q->where('warehouse_id', $warehouse_id);
            }])
            ->whereHas('tags', function($q) {
                $q->where('name', 'soplados');
            })
            ->where('is_raw_material', true)
            ->get();

        $lastInventory = \App\Models\SopladosInventory::where('warehouse_id', $warehouse_id)
            ->where('status', 'completed')
            ->orderBy('id', 'desc')
            ->first();

        $lastInventoryDate = $lastInventory ? $lastInventory->accepted_at : null;

        $list = [];

        foreach ($finishedProducts as $p) {
            $primera_stock = $p->productWarehouses->first()->stock_qty ?? 0;
            
            $segunda_stock = null;
            $target_id = null;
            $target_name = null;

            // Use second_quality_product_id to identify the 2da calidad homolog product
            if ($p->second_quality_product_id) {
                $secondQualityProduct = $p->secondQualityProduct;
                $target_id = $p->second_quality_product_id;
                $target_name = $secondQualityProduct ? $secondQualityProduct->name : '2da Calidad';
                
                $targetWarehouseStock = \App\Models\ProductWarehouse::where('product_id', $target_id)
                    ->where('warehouse_id', $warehouse_id)
                    ->first();
                $segunda_stock = $targetWarehouseStock->stock_qty ?? 0;
            }

            // Calculate expected/system merma
            $mermaQuery = \App\Models\ProductionOutput::where('product_id', $p->id)
                ->where('quality', 'damaged')
                ->whereHas('productionLog.shift', function($q) use ($warehouse_id) {
                    $q->where('warehouse_id', $warehouse_id);
                });

            if ($lastInventoryDate) {
                $mermaQuery->where('created_at', '>', $lastInventoryDate);
            }

            $system_merma = (float) $mermaQuery->sum('quantity');

            $list[] = [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'type' => 'finished_product',
                'system_stock_primera' => (float)$primera_stock,
                'production_target_id' => $target_id,
                'production_target_name' => $target_name,
                'system_stock_segunda' => $segunda_stock !== null ? (float)$segunda_stock : null,
                'system_stock_merma' => $system_merma,
            ];
        }

        foreach ($rawMaterials as $p) {
            if (collect($list)->pluck('id')->contains($p->id)) {
                continue;
            }

            $stock = $p->productWarehouses->first()->stock_qty ?? 0;

            $list[] = [
                'id' => $p->id,
                'name' => $p->name,
                'sku' => $p->sku,
                'type' => 'raw_material',
                'system_stock_primera' => (float)$stock,
                'production_target_id' => null,
                'production_target_name' => null,
                'system_stock_segunda' => null,
                'system_stock_merma' => 0.0,
            ];
        }

        return response()->json([
            'success' => true,
            'warehouse' => \App\Models\Warehouse::find($warehouse_id)->name ?? 'Planta',
            'warehouse_id' => $warehouse_id,
            'products' => $list
        ]);
    }

    /**
     * Store the initial physical count (pending operator acceptance).
     */
    public function storeCount(Request $request)
    {
        abort_if(!auth()->user()->can('soplados.manager'), 403, 'No tienes permisos de supervisor de soplados.');

        $request->validate([
            'notes' => 'nullable|string',
            'products' => 'required|array|min:1',
            'products.*.id' => 'required|exists:products,id',
            'products.*.type' => 'required|in:finished_product,raw_material',
            'products.*.counted_primera' => 'required|numeric|min:0',
            'products.*.counted_segunda' => 'nullable|numeric|min:0',
            'products.*.counted_merma' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $config = Configuration::first();
            $soplados_id = $config->soplados_warehouse_id ?? $config->default_warehouse_id ?? 1;
            $warehouse_id = auth()->user()->warehouse_id ?? $soplados_id;

            $currentShift = \App\Models\Shift::where('warehouse_id', $warehouse_id)
                ->where('status', 'open')
                ->first();

            $inventory = \App\Models\SopladosInventory::create([
                'warehouse_id' => $warehouse_id,
                'supervisor_id' => auth()->id(),
                'operator_id' => null,
                'shift_id' => $currentShift->id ?? null,
                'status' => 'pending_acceptance',
                'notes' => $request->notes,
                'operator_notes' => null,
                'accepted_at' => null,
            ]);

            $lastInventory = \App\Models\SopladosInventory::where('warehouse_id', $warehouse_id)
                ->where('status', 'completed')
                ->orderBy('id', 'desc')
                ->first();

            $lastInventoryDate = $lastInventory ? $lastInventory->accepted_at : null;

            foreach ($request->products as $pData) {
                $p = Product::find($pData['id']);
                
                $pw = \App\Models\ProductWarehouse::where('product_id', $p->id)
                    ->where('warehouse_id', $warehouse_id)
                    ->first();
                $system_primera = $pw->stock_qty ?? 0;

                $counted_primera = floatval($pData['counted_primera']);
                $diff_primera = $counted_primera - $system_primera;

                $system_segunda = null;
                $counted_segunda = null;
                $diff_segunda = null;
                $system_merma = null;
                $counted_merma = null;
                $diff_merma = null;

                if ($pData['type'] === 'finished_product') {
                    if ($p->second_quality_product_id) {
                        $pwSegunda = \App\Models\ProductWarehouse::where('product_id', $p->second_quality_product_id)
                            ->where('warehouse_id', $warehouse_id)
                            ->first();
                        $system_segunda = $pwSegunda->stock_qty ?? 0;
                        $counted_segunda = floatval($pData['counted_segunda'] ?? 0);
                        $diff_segunda = $counted_segunda - $system_segunda;
                    }
                    
                    // Calculate expected/system merma at this moment
                    $mermaQuery = \App\Models\ProductionOutput::where('product_id', $p->id)
                        ->where('quality', 'damaged')
                        ->whereHas('productionLog.shift', function($q) use ($warehouse_id) {
                            $q->where('warehouse_id', $warehouse_id);
                        });

                    if ($lastInventoryDate) {
                        $mermaQuery->where('created_at', '>', $lastInventoryDate);
                    }

                    $system_merma = (float) $mermaQuery->sum('quantity');
                    $counted_merma = floatval($pData['counted_merma'] ?? 0);
                    $diff_merma = $counted_merma - $system_merma;
                }

                \App\Models\SopladosInventoryDetail::create([
                    'soplados_inventory_id' => $inventory->id,
                    'product_id' => $p->id,
                    'type' => $pData['type'],
                    'system_stock_primera' => $system_primera,
                    'counted_primera' => $counted_primera,
                    'difference_primera' => $diff_primera,
                    'system_stock_segunda' => $system_segunda,
                    'counted_segunda' => $counted_segunda,
                    'difference_segunda' => $diff_segunda,
                    'system_stock_merma' => $system_merma,
                    'counted_merma' => $counted_merma,
                    'difference_merma' => $diff_merma,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventario registrado y enviado para conformidad del operador.',
                'inventory' => $inventory->load('details.product')
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar inventario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List pending inventories for operator acceptance.
     */
    public function pendingAcceptance()
    {
        $config = Configuration::first();
        $soplados_id = $config->soplados_warehouse_id ?? $config->default_warehouse_id ?? 1;
        $warehouse_id = auth()->user()->warehouse_id ?? $soplados_id;

        $inventories = \App\Models\SopladosInventory::with(['details.product', 'supervisor'])
            ->where('warehouse_id', $warehouse_id)
            ->where('status', 'pending_acceptance')
            ->orderBy('id', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'inventories' => $inventories
        ]);
    }

    /**
     * Accept a pending inventory and apply stock adjustments.
     */
    public function acceptCount(Request $request, $id)
    {
        abort_if(!auth()->user()->can('soplados.operator'), 403, 'No tienes permisos de operario de soplados.');

        $request->validate([
            'operator_notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $inventory = \App\Models\SopladosInventory::with('details.product')->findOrFail($id);

            if ($inventory->status !== 'pending_acceptance') {
                return response()->json([
                    'success' => false,
                    'message' => 'Este inventario ya fue procesado o no está pendiente.'
                ], 400);
            }

            $inventory->update([
                'status' => 'completed',
                'operator_id' => auth()->id(),
                'operator_notes' => $request->operator_notes,
                'accepted_at' => now(),
            ]);

            foreach ($inventory->details as $detail) {
                $this->setWarehouseStock($detail->product_id, $inventory->warehouse_id, $detail->counted_primera);

                if ($detail->type === 'finished_product' && $detail->product->second_quality_product_id) {
                    $this->setWarehouseStock($detail->product->second_quality_product_id, $inventory->warehouse_id, $detail->counted_segunda);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Inventario aceptado y stock ajustado en el sistema.',
                'inventory' => $inventory
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al confirmar inventario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the count history (paginated).
     */
    public function countHistory(Request $request)
    {
        $config = Configuration::first();
        $soplados_id = $config->soplados_warehouse_id ?? $config->default_warehouse_id ?? 1;
        $warehouse_id = auth()->user()->warehouse_id ?? $soplados_id;

        $query = \App\Models\SopladosInventory::with(['details.product', 'supervisor', 'operator', 'shift'])
            ->where('warehouse_id', $warehouse_id)
            ->orderBy('id', 'desc');

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $inventories = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $inventories
        ]);
    }

    private function setWarehouseStock($productId, $warehouseId, $stockQty)
    {
        $pw = \App\Models\ProductWarehouse::firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['stock_qty' => 0]
        );

        $pw->stock_qty = $stockQty;
        $pw->save();

        $config = Configuration::first();
        $defaultWarehouseId = $config->default_warehouse_id ?? \App\Models\Warehouse::first()->id ?? 1;

        if ($warehouseId == $defaultWarehouseId) {
            $product = Product::find($productId);
            if ($product) {
                $product->stock_qty = $stockQty;
                $product->save();
            }
        }
    }
}
