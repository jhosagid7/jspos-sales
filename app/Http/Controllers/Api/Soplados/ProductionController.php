<?php

namespace App\Http\Controllers\Api\Soplados;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'shift_id' => 'required|exists:shifts,id',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'materials' => 'required|array',
            'materials.*.product_id' => 'required|exists:products,id',
            'materials.*.quantity' => 'required|numeric|min:0.01',
            'outputs' => 'required|array',
            'outputs.*.product_id' => 'required|exists:products,id',
            'outputs.*.quantity' => 'required|numeric|min:0.01',
            'outputs.*.quality' => 'required|in:1st,2nd,damaged',
        ]);

        try {
            $warehouseId = $request->warehouse_id ?? auth()->user()->warehouse_id ?? 1;
            \Illuminate\Support\Facades\DB::beginTransaction();

            $log = \App\Models\ProductionLog::create([
                'shift_id' => $request->shift_id,
                'user_id' => auth()->id() ?? 1, // Fallback for testing
                'notes' => $request->notes
            ]);

            // Process Materials (Deduct)
            foreach ($request->materials as $mat) {
                \App\Models\ProductionMaterial::create([
                    'production_log_id' => $log->id,
                    'product_id' => $mat['product_id'],
                    'quantity' => $mat['quantity']
                ]);

                $this->updateStock($mat['product_id'], $warehouseId, -$mat['quantity']);
            }

            // Process Outputs (Add, unless damaged)
            foreach ($request->outputs as $out) {
                \App\Models\ProductionOutput::create([
                    'production_log_id' => $log->id,
                    'product_id' => $out['product_id'],
                    'quantity' => $out['quantity'],
                    'quality' => $out['quality']
                ]);

                if ($out['quality'] === '1st' || $out['quality'] === '2nd') {
                    $this->updateStock($out['product_id'], $warehouseId, $out['quantity']);
                }
            }

            \Illuminate\Support\Facades\DB::commit();

            return response()->json(['success' => true, 'message' => 'Producción registrada correctamente', 'log' => $log->load('materials', 'outputs')]);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al registrar producción: ' . $e->getMessage()], 500);
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

        // Check if default warehouse, update product stock
        $config = \App\Models\Configuration::first();
        $defaultWarehouseId = $config->default_warehouse_id ?? \App\Models\Warehouse::first()->id ?? 1;

        if ($warehouseId == $defaultWarehouseId) {
            $product = \App\Models\Product::find($productId);
            if ($product) {
                $product->stock_qty += $quantity;
                $product->save();
            }
        }
    }
}
