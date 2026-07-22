<?php

namespace App\Http\Controllers\Api\Vip;

use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\OrderHistoryLog;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * Get VIP Customer's own orders
     */
    public function index(Request $request)
    {
        $customer = $request->user();
        
        $orders = Order::with(['details.product'])
            ->where('customer_id', $customer->id)
            ->where('status', '!=', 'deleted')
            ->orderBy('id', 'desc')
            ->limit(50) 
            ->get();

        // Formato para que la app lo lea correctamente (incluyendo datos de 'customer')
        foreach ($orders as $order) {
            $order->customer = ['id' => $customer->id, 'name' => $customer->name];
        }

        return response()->json($orders);
    }

    /**
     * VIP Customer creates or updates an order (draft)
     */
    public function store(Request $request)
    {
        $customer = $request->user();

        $request->validate([
            'items' => 'required|array|min:1',
        ]);

        try {
            DB::beginTransaction();

            $isNew = true;
            $order = null;

            if ($request->original_order_id) {
                // Editing an existing draft order
                $order = Order::where('id', $request->original_order_id)->where('customer_id', $customer->id)->first();
                if ($order) {
                    if ($order->status === 'processed') {
                        return response()->json(['success' => false, 'message' => 'No se puede editar un pedido ya PROCESADO/FACTURADO'], 422);
                    }
                    if ($order->status === 'pending') {
                         return response()->json(['success' => false, 'message' => 'El pedido ya fue enviado a oficina y no puede editarse.'], 403);
                    }
                    $isNew = false;
                    OrderDetail::where('order_id', $order->id)->delete();
                }
            }

            $assignedSellerId = ($customer->seller_id && $customer->seller_id > 0) ? $customer->seller_id : 1;

            if ($isNew) {
                // Generate Order Number consistently using Configuration table
                $config_ord = \App\Models\Configuration::lockForUpdate()->first();
                $config_ord->order_sequence += 1;
                $config_ord->save();
                $orderNumber = 'P' . str_pad($config_ord->order_sequence, 8, '0', STR_PAD_LEFT);

                $order = Order::create([
                    'order_number' => $orderNumber,
                    'customer_id' => $customer->id,
                    'user_id' => $assignedSellerId,
                    'status' => 'draft', // DRAFT
                    'total' => 0, 
                    'items' => count($request->items),
                    'notes' => "Pedido directo por cliente VIP. " . $request->notes,
                    'discount' => 0,
                ]);

                OrderHistoryLog::create([
                    'order_id' => $order->id,
                    'user_id' => $assignedSellerId,
                    'action' => 'created',
                    'description' => 'Apertura de orden en borrador desde App VIP.',
                    'details' => ['items_count' => count($request->items)],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
            } else {
                $order->update([
                    'items' => count($request->items),
                    'notes' => "Pedido directo por cliente VIP. " . $request->notes,
                    'total' => 0,
                ]);

                OrderHistoryLog::create([
                    'order_id' => $order->id,
                    'user_id' => $assignedSellerId,
                    'action' => 'edited',
                    'description' => 'Orden actualizada por el cliente VIP.',
                    'details' => ['items_count' => count($request->items)],
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent()
                ]);
            }

            $total = 0;
            $checkStockReservation = \App\Models\Configuration::first()->check_stock_reservation ?? false;
            $warehouseId = $customer->seller->warehouse_id ?? \App\Models\Configuration::first()->default_warehouse_id ?? 1;

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
                'message' => 'Pedido guardado en borrador con éxito',
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $order->status
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error VIP guardando pedido: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Send to office (promote to pending)
     */
    public function sendToOffice($id, Request $request)
    {
        $customer = $request->user();
        $order = Order::where('id', $id)->where('customer_id', $customer->id)->first();

        if (!$order) return response()->json(['success' => false, 'message' => 'Pedido no encontrado'], 404);

        if ($order->status !== 'draft') {
            return response()->json(['success' => false, 'message' => 'El pedido ya fue enviado o procesado.'], 422);
        }

        $order->update(['status' => 'pending']);

        $assignedSellerId = ($customer->seller_id && $customer->seller_id > 0) ? $customer->seller_id : 1;

        OrderHistoryLog::create([
            'order_id' => $order->id,
            'user_id' => $assignedSellerId,
            'action' => 'sent_to_office',
            'description' => 'Orden enviada a la oficina por el cliente VIP.',
            'details' => ['from' => 'draft', 'to' => 'pending'],
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent()
        ]);

        return response()->json(['success' => true, 'message' => 'Pedido enviado a oficina con éxito']);
    }

    public function destroy($id, Request $request)
    {
        $customer = $request->user();
        $order = Order::where('id', $id)->where('customer_id', $customer->id)->first();

        if (!$order) return response()->json(['success' => false, 'message' => 'Pedido no encontrado'], 404);

        if ($order->status === 'processed') {
            return response()->json(['success' => false, 'message' => 'No se puede eliminar un pedido procesado'], 422);
        }

        try {
            DB::beginTransaction();

            $assignedSellerId = ($customer->seller_id && $customer->seller_id > 0) ? $customer->seller_id : 1;
            
            OrderHistoryLog::create([
                'order_id' => $order->id,
                'user_id' => $assignedSellerId,
                'action' => 'deleted',
                'description' => 'Orden cancelada/eliminada por el cliente VIP.',
                'details' => ['folio' => $order->order_number],
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            OrderDetail::where('order_id', $order->id)->delete();
            $order->update(['status' => 'deleted']);
            
            DB::commit();

            return response()->json(['success' => true, 'message' => 'Pedido eliminado con éxito']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Error al eliminar pedido'], 500);
        }
    }

    public function logs($id, Request $request)
    {
        $customer = $request->user();
        $order = Order::where('id', $id)->where('customer_id', $customer->id)->first();
        if (!$order) return response()->json(['success' => false, 'message' => 'No autorizado'], 403);

        $logs = OrderHistoryLog::with('user:id,name')->where('order_id', $id)->orderBy('id', 'desc')->get();
        return response()->json($logs);
    }
}
