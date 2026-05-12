<?php

namespace App\Http\Controllers\Api\Soplados;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductionFormula;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductionController extends Controller
{
    /**
     * Returns only products that have a production formula configured.
     * These are the "finished products" the operator can register.
     */
    public function products()
    {
        // Filter products that have the tag 'soplados'
        $products = Product::whereHas('tags', function($q) {
                $q->where('name', 'soplados');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'sku']);

        return response()->json($products);
    }

    /**
     * Returns the formula (ingredients) for a given finished product,
     * already scaled to quantity=1. The app multiplies by actual quantity.
     */
    public function formula($id)
    {
        $formulas = ProductionFormula::with('ingredient:id,name,sku')
            ->where('product_id', $id)
            ->get()
            ->map(fn($f) => [
                'ingredient_id'   => $f->ingredient_id,
                'ingredient_name' => $f->ingredient->name ?? 'Desconocido',
                'ingredient_sku'  => $f->ingredient->sku ?? '',
                'quantity_per_unit' => (float) $f->quantity,
            ]);

        if ($formulas->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'Este producto no tiene fórmula configurada.'], 404);
        }

        return response()->json(['success' => true, 'formula' => $formulas]);
    }

    /**
     * Store a production log.
     * Accepts outputs (finished products + quantities + quality).
     * Auto-calculates materials from the formula.
     * If formula is missing for a product, returns an error.
     */
    public function store(Request $request)
    {
        $request->validate([
            'shift_id'              => 'required|exists:shifts,id',
            'warehouse_id'          => 'nullable|exists:warehouses,id',
            'notes'                 => 'nullable|string',
            'outputs'               => 'required|array|min:1',
            'outputs.*.product_id'  => 'required|exists:products,id',
            'outputs.*.quantity'    => 'required|numeric|min:0.01',
            'outputs.*.quality'     => 'required|in:1st,2nd,damaged',
        ]);

        try {
            $shift = \App\Models\Shift::find($request->shift_id);
            $config = \App\Models\Configuration::first();
            
            // Primary Factory Warehouse for Soplados
            $sopladosId = $config->soplados_warehouse_id ?? $config->default_warehouse_id ?? 1;

            $warehouseId = $request->warehouse_id
                ?? $shift->warehouse_id
                ?? auth()->user()->warehouse_id
                ?? $sopladosId;
            
            // Resolve Materials Source Warehouse (Centralized or local to plant)
            $materialsWarehouseId = $config->production_materials_warehouse_id ?? $warehouseId;

            DB::beginTransaction();

            $log = \App\Models\ProductionLog::create([
                'shift_id' => $request->shift_id,
                'user_id'  => auth()->id(),
                'notes'    => $request->notes,
            ]);

            // Calculate aggregated materials from formulas
            $aggregatedMaterials = [];

            foreach ($request->outputs as $out) {
                $formulas = ProductionFormula::where('product_id', $out['product_id'])->get();

                if ($formulas->isEmpty()) {
                    DB::rollBack();
                    $product = Product::find($out['product_id']);
                    return response()->json([
                        'success' => false,
                        'message' => 'El producto "' . ($product->name ?? $out['product_id']) . '" no tiene fórmula configurada. Configúrala en el panel web antes de registrar producción.',
                    ], 422);
                }

                // Register output
                \App\Models\ProductionOutput::create([
                    'production_log_id' => $log->id,
                    'product_id'        => $out['product_id'],
                    'quantity'          => $out['quantity'],
                    'quality'           => $out['quality'],
                ]);

                // Add stock for 1st and 2nd quality outputs
                if (in_array($out['quality'], ['1st', '2nd'])) {
                    $product = Product::find($out['product_id']);
                    $targetId = $product->production_target_id ?? $out['product_id'];
                    $this->updateStock($targetId, $warehouseId, $out['quantity']);
                }

                // Accumulate material consumption from formula
                foreach ($formulas as $formula) {
                    $consumed = $formula->quantity * $out['quantity'];
                    $aggregatedMaterials[$formula->ingredient_id] =
                        ($aggregatedMaterials[$formula->ingredient_id] ?? 0) + $consumed;
                }
            }

            // Register and deduct each material
            foreach ($aggregatedMaterials as $ingredientId => $totalQty) {
                \App\Models\ProductionMaterial::create([
                    'production_log_id' => $log->id,
                    'product_id'        => $ingredientId,
                    'quantity'          => $totalQty,
                ]);

                $this->updateStock($ingredientId, $materialsWarehouseId, -$totalQty);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Producción registrada correctamente',
                'log'     => $log->load('materials', 'outputs'),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar producción: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function history(Request $request)
    {
        $query = \App\Models\ProductionLog::with(['user', 'shift', 'materials.product', 'outputs.product.productionTarget'])
            ->orderBy('id', 'desc');

        if ($request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate(20);

        // Add calculated stats to each record for the mobile app
        $logs->getCollection()->transform(function($log) {
            $data = $log->toArray();
            $data['stats'] = $log->stats; // Uses the model accessor we added
            return $data;
        });

        return response()->json([
            'success' => true,
            'data' => $logs
        ]);
    }

    private function updateStock($productId, $warehouseId, $quantity)
    {
        $pw = \App\Models\ProductWarehouse::firstOrCreate(
            ['product_id' => $productId, 'warehouse_id' => $warehouseId],
            ['stock_qty'  => 0]
        );

        $pw->stock_qty += $quantity;
        $pw->save();

        $config            = \App\Models\Configuration::first();
        $defaultWarehouseId = $config->default_warehouse_id
            ?? \App\Models\Warehouse::first()->id
            ?? 1;

        if ($warehouseId == $defaultWarehouseId) {
            $product = Product::find($productId);
            if ($product) {
                $product->stock_qty += $quantity;
                $product->save();
            }
        }
    }
}
