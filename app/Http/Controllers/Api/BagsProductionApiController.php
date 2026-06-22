<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionDetail;
use App\Models\Cargo;
use App\Models\CargoDetail;
use App\Models\Configuration;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BagsProductionApiController extends Controller
{
    /**
     * Get products filtered by Tag "M&F" or Supplier "M&F Steel".
     */
    public function products(Request $request)
    {
        $query = Product::query()
            ->where(function ($q) {
                $q->whereHas('tags', function ($sub) {
                    $sub->where('name', 'M&F');
                })
                ->orWhereHas('supplier', function ($sub) {
                    $sub->where('name', 'like', '%M&F Steel%');
                });
            });

        if ($request->has('search') && !empty($request->search)) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', '=', $search);
            });
        }

        $products = $query->orderBy('name')
            ->get(['id', 'name', 'sku', 'cost', 'is_variable_quantity']);

        return response()->json($products);
    }

    /**
     * Store bags production and generate pending cargo.
     */
    public function store(Request $request)
    {
        $request->validate([
            'production_date'        => 'required|date_format:Y-m-d',
            'notes'                  => 'nullable|string',
            'details'                => 'required|array|min:1',
            'details.*.product_id'   => 'required|exists:products,id',
            'details.*.quantity'     => 'required|numeric|min:0.0001',
            'details.*.weight'       => 'required|numeric|min:0.0001',
            'details.*.operator_name'=> 'required|string|max:255',
            'details.*.production_date' => 'required|date_format:Y-m-d',
            'details.*.metadata'     => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $config = Configuration::first();
            
            // Resolve warehouse: use configured bags warehouse, fallback to default or first active warehouse
            $warehouseId = $config->bolsas_warehouse_id 
                ?? $config->default_warehouse_id 
                ?? Warehouse::where('is_active', 1)->first()?->id 
                ?? 1;

            // 1. Create Production Header in state 'pending'
            $production = Production::create([
                'user_id'         => auth()->id(),
                'production_date' => $request->production_date,
                'status'          => 'pending',
                'note'            => $request->notes,
            ]);

            // 2. Process Details
            foreach ($request->details as $item) {
                $product = Product::find($item['product_id']);

                // Create ProductionDetail
                ProductionDetail::create([
                    'production_id'   => $production->id,
                    'product_id'      => $item['product_id'],
                    'production_date' => $item['production_date'],
                    'warehouse_id'    => $warehouseId,
                    'material_type'   => 'Original', // Default tag required by DB
                    'quantity'        => $item['quantity'],
                    'weight'          => $item['weight'],
                    'operator_name'   => $item['operator_name'],
                    'metadata'        => $item['metadata'] ?? null,
                    'cost'            => $product->cost ?? 0,
                ]);
            }

            DB::commit();

            return response()->json([
                'success'       => true,
                'message'       => 'Levantamiento de producción registrado correctamente',
                'production_id' => $production->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la producción: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch production history.
     */
    public function history(Request $request)
    {
        $query = Production::with(['details.product', 'user'])->orderBy('id', 'desc');

        if ($request->filled('production_date')) {
            $query->whereDate('production_date', $request->production_date);
        }

        if ($request->filled('lifting_date')) {
            $query->whereDate('created_at', $request->lifting_date);
        }

        if ($request->filled('operator_name')) {
            $query->whereHas('details', function ($q) use ($request) {
                $q->where('operator_name', 'like', '%' . $request->operator_name . '%');
            });
        }

        if ($request->filled('product_id')) {
            $query->whereHas('details', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('note', 'like', "%{$search}%")
                  ->orWhereHas('details', function ($sub) use ($search) {
                      $sub->where('operator_name', 'like', "%{$search}%")
                          ->orWhereHas('product', function ($p) use ($search) {
                              $p->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                          });
                  });
            });
        }

        $history = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $history,
        ]);
    }
}
