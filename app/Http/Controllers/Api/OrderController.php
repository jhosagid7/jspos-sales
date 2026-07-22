<?php

namespace App\Http\Controllers\Api;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\OrderHistoryLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class OrderController extends Controller
{
    /**
     * Check if the user is currently allowed to place orders based on deadline.
     */
    private function checkDeadline($user)
    {
        if ($user->is_deadline_active && $user->order_deadline_at) {
            $deadline = Carbon::parse($user->order_deadline_at);
            if (Carbon::now()->greaterThan($deadline)) {
                return false;
            }
        }
        return true;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        
        // Base query showing only active (non-deleted) orders
        $query = Order::with(['customer', 'details.product'])
            ->where('status', '!=', 'deleted');

        // Administrative profiles see everything; others see only their own
        if (!$user->hasRole(['Admin', 'Super Admin']) && $user->profile !== 'Admin' && $user->profile !== 'Super Admin') {
            $query->where('user_id', $user->id);
        }

        $orders = $query->orderBy('id', 'desc')
            ->limit(50) 
            ->get();

        return response()->json($orders);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        // 🛑 DEADLINE CHECK
        if (!$this->checkDeadline($user)) {
            return response()->json([
                'success' => false, 
                'message' => 'El periodo de recepción de pedidos ha culminado. Por favor, contacte a administración.'
            ], 403);
        }

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'items' => 'required|array|min:1',
        ]);

        try {
            DB::beginTransaction();

            $order = null;
            $isNew = true;

            // Check if we are UPDATING an existing order
            if ($request->has('original_order_id')) {
                $order = Order::find($request->original_order_id);
                if ($order) {
                    // Only Allow editing if it's draft or pending (if permissions allow)
                    if ($order->status === 'processed') {
                        return response()->json(['success' => false, 'message' => 'No se puede editar un pedido ya PROCESADO/FACTURADO'], 422);
                    }
                    
                    // If it's already pending, standard sellers might be blocked from editing (as per Elizabeth's request)
                    if ($order->status === 'pending' && !$user->hasRole(['Admin', 'Super Admin'])) {
                         return response()->json(['success' => false, 'message' => 'El pedido ya fue enviado a oficina y no puede editarse.'], 403);
                    }

                    $isNew = false;
                    // Clean old details to overwrite
                    OrderDetail::where('order_id', $order->id)->delete();
                }
            }

            if ($isNew) {
                // Generate Order Number consistently using Configuration table
                $config_ord = \App\Models\Configuration::lockForUpdate()->first();
                $config_ord->order_sequence += 1;
                $config_ord->save();
                $orderNumber = 'P' . str_pad($config_ord->order_sequence, 8, '0', STR_PAD_LEFT);

                $order = Order::create([
                    'order_number' => $orderNumber,
                    'customer_id' => $request->customer_id,
                    'user_id' => $user->id,
                    'status' => 'draft', // 🆕 Born as DRAFT (Pre-orden)
                    'total' => 0, 
                    'items' => count($request->items),
                    'notes' => $request->notes,
                    'discount' => 0,
                ]);

                OrderHistoryLog::create([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'action' => 'created',
                    'description' => 'Apertura de orden desde la App Móvil.',
                    'details' => ['items_count' => count($request->items)],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
            } else {
                $oldSnapshot = $order->load('details.product')->toArray();
                
                $order->update([
                    'customer_id' => $request->customer_id,
                    'items' => count($request->items),
                    'notes' => $request->notes,
                    'total' => 0,
                ]);

                // Calculate Diff for Elizabeth
                $changes = [];
                $oldItems = [];
                foreach($oldSnapshot['details'] as $od) {
                    $oldItems[$od['product_id']] = [
                        'name' => $od['product']['name'],
                        'qty' => $od['quantity']
                    ];
                }

                $newItems = [];
                foreach($request->items as $ni) {
                    $prod = Product::find($ni['product_id']);
                    $newItems[$ni['product_id']] = [
                        'name' => $prod->name,
                        'qty' => $ni['quantity']
                    ];
                }

                // Detect removals
                foreach($oldItems as $pid => $data) {
                    if (!isset($newItems[$pid])) {
                        $changes[] = "QUITÓ: " . $data['name'];
                    }
                }

                // Detect additions and quantity changes
                foreach($newItems as $pid => $data) {
                    if (!isset($oldItems[$pid])) {
                        $changes[] = "AGREGÓ: " . $data['name'] . " (Cant: " . $data['qty'] . ")";
                    } else if ($oldItems[$pid]['qty'] != $data['qty']) {
                        $changes[] = "CAMBIÓ: " . $data['name'] . " (" . $oldItems[$pid]['qty'] . " -> " . $data['qty'] . ")";
                    }
                }

                OrderHistoryLog::create([
                    'order_id' => $order->id,
                    'user_id' => $user->id,
                    'action' => 'edited',
                    'description' => count($changes) > 0 ? implode(", ", $changes) : 'Orden actualizada sin cambios en productos.',
                    'details' => ['changes' => $changes],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
            }

            $total = 0;
            $checkStockReservation = \App\Models\Configuration::first()->check_stock_reservation ?? false;
            $warehouseId = $user->warehouse_id ?? \App\Models\Configuration::first()->default_warehouse_id ?? 1;

            foreach ($request->items as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) continue;

                $qty = (float) $item['quantity'];

                // 🛑 CRITICAL STOCK VALIDATION
                if ($checkStockReservation) {
                    $physicalStock = (float) $product->stockIn($warehouseId);
                    $reservedStock = (float) $product->getReservedStock($warehouseId);
                    $available = $physicalStock - $reservedStock;

                    if ($qty > $available) {
                        throw new \Exception("Stock insuficiente para '{$product->name}'. Disponible: " . ($available > 0 ? $available : 0));
                    }
                }

                $price = $item['price'] ?? $product->price;
                $subtotal = $price * $qty;

                OrderDetail::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                    'regular_price' => $product->price,
                    'sale_price' => $price,
                    'discount' => 0,
                    'warehouse_id' => $warehouseId,
                ]);

                $total += $subtotal;
            }

            $order->update(['total' => $total]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pedido guardado en borrador',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error guardando pedido: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Promote a DRAFT order to PENDING (Send to Office)
     */
    public function sendToOffice($id, Request $request)
    {
        $user = $request->user();
        $order = Order::where('id', $id)->where('user_id', $user->id)->first();

        if (!$order) return response()->json(['success' => false, 'message' => 'Pedido no encontrado'], 404);

        if ($order->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'El pedido ya fue enviado o procesado anteriormente.'], 422);
        }

        // 🛑 DEADLINE CHECK 
        if (!$this->checkDeadline($user)) {
             return response()->json(['success' => false, 'message' => 'El periodo de envío de pedidos ha culminado.'], 403);
        }

        $order->update(['status' => 'pending']);

        OrderHistoryLog::create([
            'order_id' => $order->id,
            'user_id' => $user->id,
            'action' => 'sent_to_office',
            'description' => 'Orden enviada a la oficina para facturación.',
            'details' => ['from' => 'draft', 'to' => 'pending'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json(['success' => true, 'message' => 'Pedido enviado a oficina con éxito']);
    }

    public function destroy($id, Request $request)
    {
        $user = $request->user();
        $query = Order::where('id', $id);

        if (!$user->hasRole(['Admin', 'Super Admin']) && $user->profile !== 'Admin' && $user->profile !== 'Super Admin') {
            $query->where('user_id', $user->id);
            // Extra check: Can't delete if already processed or sent?
            // Elizabeth wants them to be able to delete if it's draft.
        }

        $order = $query->first();

        if (!$order) return response()->json(['success' => false, 'message' => 'Pedido no encontrado'], 404);

        if ($order->status === 'processed') {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar un pedido ya facturado'], 422);
        }

        try {
            DB::beginTransaction();
            
            OrderHistoryLog::create([
                'order_id' => $order->id,
                'user_id' => $user->id,
                'action' => 'deleted',
                'description' => 'Orden eliminada/cancelada permanentemente por el usuario.',
                'details' => ['folio' => $order->order_number],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            OrderDetail::where('order_id', $order->id)->delete();
            $order->delete();
            
            DB::commit();
            return response()->json(['success' => true, 'message' => 'Pedido eliminado con éxito']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function logs($id)
    {
        $logs = OrderHistoryLog::with('user')
            ->where('order_id', $id)
            ->orderBy('id', 'desc')
            ->get();
            
        return response()->json($logs);
    }
}
