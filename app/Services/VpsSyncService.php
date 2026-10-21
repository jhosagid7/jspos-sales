<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\OrderHistoryLog;
use App\Models\Product;
use App\Models\Configuration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class VpsSyncService
{
    protected string $baseUrl;
    protected ?string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim(env('VPS_SYNC_URL', 'https://pedidos.plasticosmyf.com'), '/');
        $this->token = env('VPS_SYNC_TOKEN', '');
    }

    /**
     * Send active catalog products to the VPS portal
     */
    public function pushCatalog(): array
    {
        if (empty($this->token)) {
            return ['success' => false, 'message' => 'VPS_SYNC_TOKEN no está configurado en .env'];
        }

        $products = Product::where('status', 'available')
            ->where('show_in_sales', true)
            ->with(['category'])
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'sku' => $p->sku,
                    'name' => $p->name,
                    'price' => (float) $p->price,
                    'freight_type' => $p->freight_type ?? 'none',
                    'freight_value' => (float) ($p->freight_value ?? 0),
                    'stock_qty' => (float) $p->stock_qty,
                    'photo' => $p->photo,
                    'category_name' => $p->category ? $p->category->name : null,
                    'status' => $p->status,
                    'show_in_sales' => true,
                ];
            })
            ->toArray();

        try {
            $response = Http::withHeaders([
                'X-Sync-Token' => $this->token,
                'Accept' => 'application/json',
            ])->timeout(30)->post("{$this->baseUrl}/api/sync/catalog", [
                'products' => $products,
            ]);

            if ($response->successful()) {
                Log::info("VPS Sync: Catálogo enviado con éxito (" . count($products) . " productos).");
                return ['success' => true, 'count' => count($products), 'data' => $response->json()];
            }

            Log::error("VPS Sync Error Catalog: " . $response->body());
            return ['success' => false, 'message' => $response->body()];
        } catch (\Exception $e) {
            Log::error("VPS Sync Exception Catalog: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Send customers with passwords and pricing configurations to the VPS portal
     */
    public function pushCustomers(): array
    {
        if (empty($this->token)) {
            return ['success' => false, 'message' => 'VPS_SYNC_TOKEN no está configurado en .env'];
        }

        // Only send customers that can log in (have password) or all customers
        $customers = Customer::with(['latestCustomerConfig', 'dispatchRoute'])
            ->get()
            ->map(function ($c) {
                $config = $c->latestCustomerConfig;
                $route = $c->dispatchRoute;
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'taxpayer_id' => $c->taxpayer_id,
                    'email' => $c->email,
                    'phone' => $c->phone,
                    'password' => $c->password, // Already bcrypt hashed
                    'address' => $c->address,
                    'city' => $c->city,
                    'seller_id' => $c->seller_id,
                    'dispatch_route' => $route ? [
                        'name' => $route->name,
                        'route_type' => $route->route_type,
                        'dispatch_day' => $route->dispatch_day,
                        'cutoff_day' => $route->cutoff_day,
                        'cutoff_time' => $route->cutoff_time,
                    ] : null,
                    'config' => $config ? [
                        'commission_percent' => (float) $config->commission_percent,
                        'freight_percent' => (float) $config->freight_percent,
                        'exchange_diff_percent' => (float) $config->exchange_diff_percent,
                        'base_markup_percent' => (float) ($config->base_markup_percent ?? 0),
                    ] : null,
                ];
            })
            ->toArray();

        try {
            $response = Http::withHeaders([
                'X-Sync-Token' => $this->token,
                'Accept' => 'application/json',
            ])->timeout(30)->post("{$this->baseUrl}/api/sync/customers", [
                'customers' => $customers,
            ]);

            if ($response->successful()) {
                Log::info("VPS Sync: Clientes enviados con éxito (" . count($customers) . " clientes).");
                return ['success' => true, 'count' => count($customers), 'data' => $response->json()];
            }

            Log::error("VPS Sync Error Customers: " . $response->body());
            return ['success' => false, 'message' => $response->body()];
        } catch (\Exception $e) {
            Log::error("VPS Sync Exception Customers: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Pull pending customer orders from the VPS portal and insert into local jspos-sales
     */
    public function pullPendingOrders(): array
    {
        if (empty($this->token)) {
            return ['success' => false, 'message' => 'VPS_SYNC_TOKEN no está configurado en .env'];
        }

        try {
            $response = Http::withHeaders([
                'X-Sync-Token' => $this->token,
                'Accept' => 'application/json',
            ])->timeout(30)->get("{$this->baseUrl}/api/sync/orders/pending");

            if (!$response->successful()) {
                Log::error("VPS Sync Error Pull Orders: " . $response->body());
                return ['success' => false, 'message' => $response->body()];
            }

            $data = $response->json();
            $pendingOrders = $data['orders'] ?? [];
            $ingestedCount = 0;

            foreach ($pendingOrders as $vpsOrder) {
                DB::beginTransaction();
                try {
                    $customer = Customer::find($vpsOrder['customer_id']);
                    $assignedSellerId = ($customer && $customer->seller_id) ? $customer->seller_id : 1;

                    // Correlative Order Sequence
                    $config = Configuration::lockForUpdate()->first();
                    $config->order_sequence += 1;
                    $config->save();
                    $posOrderNumber = 'P' . str_pad($config->order_sequence, 8, '0', STR_PAD_LEFT);

                    $deliveryNote = ($vpsOrder['delivery_type'] ?? 'route') === 'pickup'
                        ? '🏢 [RETIRO EN SEDE]'
                        : '🚚 [RUTA: ' . ($customer->dispatchRoute->name ?? 'General') . ']';

                    $localOrder = Order::create([
                        'order_number' => $posOrderNumber,
                        'customer_id' => $vpsOrder['customer_id'],
                        'user_id' => $assignedSellerId,
                        'status' => 'pending', // Pending for office review
                        'total' => (float) $vpsOrder['total'],
                        'items' => count($vpsOrder['details'] ?? []),
                        'notes' => "{$deliveryNote} Pedido Web [{$vpsOrder['vps_order_number']}]. " . ($vpsOrder['notes'] ?? ''),
                        'discount' => 0,
                    ]);

                    foreach ($vpsOrder['details'] as $detail) {
                        $product = Product::find($detail['product_id']);
                        $regularPrice = $product ? (float) $product->price : (float) $detail['regular_price'];

                        OrderDetail::create([
                            'order_id' => $localOrder->id,
                            'product_id' => $detail['product_id'],
                            'quantity' => (float) $detail['quantity'],
                            'regular_price' => $regularPrice,
                            'sale_price' => (float) $detail['sale_price'],
                            'discount' => 0,
                            'warehouse_id' => $config->default_warehouse_id ?? 1,
                        ]);
                    }

                    OrderHistoryLog::create([
                        'order_id' => $localOrder->id,
                        'user_id' => $assignedSellerId,
                        'action' => 'created_from_vps',
                        'description' => "Pedido recibido automáticamente desde portal VPS ({$vpsOrder['vps_order_number']})",
                        'details' => ['vps_order_id' => $vpsOrder['id'], 'vps_order_number' => $vpsOrder['vps_order_number']],
                    ]);

                    DB::commit();

                    // Send Acknowledgment back to VPS
                    Http::withHeaders([
                        'X-Sync-Token' => $this->token,
                        'Accept' => 'application/json',
                    ])->timeout(15)->post("{$this->baseUrl}/api/sync/orders/{$vpsOrder['id']}/ack", [
                        'pos_order_number' => $posOrderNumber,
                    ]);

                    $ingestedCount++;
                    Log::info("VPS Sync: Orden {$vpsOrder['vps_order_number']} descargada con éxito como {$posOrderNumber}");
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error("Error procesando orden VPS ID {$vpsOrder['id']}: " . $e->getMessage());
                }
            }

            return ['success' => true, 'ingested_orders' => $ingestedCount];
        } catch (\Exception $e) {
            Log::error("VPS Sync Exception Pull Orders: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Fetch orders that have requested editing on the VPS portal
     */
    public function getEditRequestedOrders(): array
    {
        if (empty($this->token)) {
            return [];
        }

        try {
            $response = Http::withHeaders([
                'X-Sync-Token' => $this->token,
                'Accept' => 'application/json',
            ])->timeout(10)->get("{$this->baseUrl}/api/sync/orders/edit-requests");

            if ($response->successful()) {
                return $response->json('orders', []);
            }
            return [];
        } catch (\Exception $e) {
            Log::warning("VPS Sync Warning getEditRequestedOrders: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Notify VPS that an order was reverted to draft by the office
     */
    public function notifyOrderRevertedToDraft(Order $order): array
    {
        if (empty($this->token)) {
            return ['success' => false, 'message' => 'Token no configurado'];
        }

        // Extract ORD-VPS number from notes if present
        $vpsOrderNumber = null;
        if (preg_match('/(ORD-VPS-[A-Z0-9\-]+)/', (string)$order->notes, $matches)) {
            $vpsOrderNumber = $matches[1];
        }

        try {
            $response = Http::withHeaders([
                'X-Sync-Token' => $this->token,
                'Accept' => 'application/json',
            ])->timeout(15)->post("{$this->baseUrl}/api/sync/orders/revert-draft", [
                'vps_order_number' => $vpsOrderNumber,
                'pos_order_number' => $order->order_number,
            ]);

            if ($response->successful()) {
                Log::info("VPS Sync: Orden {$vpsOrderNumber} ({$order->order_number}) revertida a borrador en VPS.");
                return ['success' => true, 'data' => $response->json()];
            }

            Log::warning("VPS Sync revert-draft response: " . $response->body());
            return ['success' => false, 'message' => $response->body()];
        } catch (\Exception $e) {
            Log::error("VPS Sync Exception notifyOrderRevertedToDraft: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
