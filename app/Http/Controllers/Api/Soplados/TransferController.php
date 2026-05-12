<?php

namespace App\Http\Controllers\Api\Soplados;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TransferController extends Controller
{
    public function counts()
    {
        $config = \App\Models\Configuration::first();
        $soplados_id = $config->soplados_warehouse_id ?? $config->default_warehouse_id ?? 1;
        $warehouse_id = auth()->user()->warehouse_id ?? $soplados_id;

        $pending_dispatches = \App\Models\Transfer::where('from_warehouse_id', $warehouse_id)
            ->where(function($q) {
                $q->where('status', 'pending')->orWhere('status', 'Pending');
            })
            ->count();

        $pending_returns = \App\Models\Transfer::where('from_warehouse_id', $warehouse_id)
            ->whereIn('status', ['Partial', 'partial', 'completed_partial', 'Completed Partial', 'Rejected', 'rejected'])
            ->where(function ($query) {
                $query->whereNull('note')
                      ->orWhere('note', 'not like', '%[Devolución Recibida]%');
            })
            ->whereHas('details', function ($query) {
                $query->where('rejected_quantity', '>', 0);
            })
            ->count();

        $pending_receipts = \App\Models\Transfer::where('to_warehouse_id', $warehouse_id)
            ->whereIn('status', ['pending', 'Pending', 'dispatched', 'Dispatched'])
            ->count();

        return response()->json([
            'success' => true,
            'counts' => [
                'dispatches' => $pending_dispatches,
                'returns' => $pending_returns,
                'receipts' => $pending_receipts,
            ]
        ]);
    }

    public function pending()
    {
        $config = \App\Models\Configuration::first();
        $soplados_id = $config->soplados_warehouse_id ?? $config->default_warehouse_id ?? 1;
        $warehouse_id = auth()->user()->warehouse_id ?? $soplados_id;

        $transfers = \App\Models\Transfer::with(['details.product', 'toWarehouse'])
            ->where('from_warehouse_id', $warehouse_id)
            ->where(function($q) {
                $q->where('status', 'pending')->orWhere('status', 'Pending');
            })
            ->get()
            ->map(function ($t) {
                $t->dest_warehouse_name = $t->toWarehouse->name ?? 'General';
                foreach ($t->details as $d) {
                    $d->product_name = $d->product->name ?? 'Producto';
                }
                return $t;
            });

        return response()->json(['success' => true, 'transfers' => $transfers]);
    }

    public function dispatchTransfer(Request $request, $id)
    {
        $transfer = \App\Models\Transfer::with('details')->findOrFail($id);

        if (strtolower($transfer->status) !== 'pending') {
            return response()->json(['success' => false, 'message' => 'El traspaso ya no está pendiente.'], 400);
        }

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            // Use 'dispatched' for consistency with web admin badges
            $transfer->update([
                'status' => 'dispatched',
                'dispatched_by_id' => auth()->id()
            ]);

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

    public function pendingReturns()
    {
        $config = \App\Models\Configuration::first();
        $soplados_id = $config->soplados_warehouse_id ?? $config->default_warehouse_id ?? 1;
        $warehouse_id = auth()->user()->warehouse_id ?? $soplados_id;

        $transfers = \App\Models\Transfer::with(['details.product', 'toWarehouse'])
            ->where('from_warehouse_id', $warehouse_id)
            ->whereIn('status', ['Partial', 'partial', 'Rejected', 'rejected', 'completed_partial', 'Completed_partial'])
            ->where(function ($query) {
                $query->whereNull('note')
                      ->orWhere('note', 'not like', '%[Devolución Recibida]%');
            })
            ->whereHas('details', function ($query) {
                $query->where('rejected_quantity', '>', 0);
            })
            ->get()
            ->map(function ($t) {
                $t->dest_warehouse_name = $t->toWarehouse->name ?? 'General';
                foreach ($t->details as $d) {
                    $d->product_name = $d->product->name ?? 'Producto';
                }
                return $t;
            });

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
            $transfer->update([
                'note' => $transfer->note . ' [Devolución Recibida]',
                'received_by_id' => auth()->id()
            ]);

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
