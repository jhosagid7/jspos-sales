<?php

namespace App\Http\Controllers\Api\Soplados;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function pending(Request $request)
    {
        $warehouse_id = $request->warehouse_id;

        $transfers = \App\Models\Transfer::with('details.product')
            ->where('from_warehouse_id', $warehouse_id)
            ->where('status', 'pending')
            ->get();

        return response()->json(['success' => true, 'transfers' => $transfers]);
    }

    public function dispatchTransfer(Request $request, $id)
    {
        $transfer = \App\Models\Transfer::with('details')->findOrFail($id);

        if ($transfer->status !== 'pending') {
            return response()->json(['success' => false, 'message' => 'El traspaso ya no está pendiente.'], 400);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            $transfer->update(['status' => 'dispatched']);

            // Deduct stock from origin warehouse
            foreach ($transfer->details as $detail) {
                $this->updateStock($detail->product_id, $transfer->from_warehouse_id, -$detail->quantity);
            }

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['success' => true, 'message' => 'Traspaso despachado correctamente.']);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al despachar: ' . $e->getMessage()], 500);
        }
    }

    public function pendingReturns(Request $request)
    {
        $warehouse_id = $request->warehouse_id;

        // Find transfers that have rejected quantities and haven't been acknowledged by Soplados yet
        // We use the 'note' field to tag if it was acknowledged to avoid a new database migration
        $transfers = \App\Models\Transfer::with('details.product')
            ->where('from_warehouse_id', $warehouse_id)
            ->whereIn('status', ['completed_partial', 'rejected'])
            ->where(function ($query) {
                $query->whereNull('note')
                      ->orWhere('note', 'not like', '%[Devolución Recibida]%');
            })
            ->whereHas('details', function ($query) {
                $query->where('rejected_quantity', '>', 0);
            })
            ->get();

        return response()->json(['success' => true, 'transfers' => $transfers]);
    }

    public function receiveReturn(Request $request, $id)
    {
        $transfer = \App\Models\Transfer::with('details')->findOrFail($id);

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Add rejected stock back to origin warehouse
            foreach ($transfer->details as $detail) {
                if ($detail->rejected_quantity > 0) {
                    $this->updateStock($detail->product_id, $transfer->from_warehouse_id, $detail->rejected_quantity);
                }
            }

            // Mark as acknowledged
            $transfer->update(['note' => $transfer->note . ' [Devolución Recibida]']);

            \Illuminate\Support\Facades\DB::commit();
            return response()->json(['success' => true, 'message' => 'Devolución recibida y sumada al inventario.']);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al recibir devolución: ' . $e->getMessage()], 500);
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
