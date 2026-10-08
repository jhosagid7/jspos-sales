<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Supplier;
use App\Models\Vehicle;
use App\Models\WorkOrder;
use App\Models\WorkOrderItem;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WorkOrderBillingService
{
    /**
     * Bill and convert a WorkOrder to a formal Sale in jspos-sales.
     *
     * @param WorkOrder $workOrder
     * @param array $billingData [
     *     'user_id' => int,
     *     'payment_method' => string, // 'cash_usd', 'cash_bs', 'pago_movil', 'credit', etc.
     *     'exit_mileage' => int|null,
     *     'notes' => string|null,
     *     'currency' => string|null,
     *     'exchange_rate' => float|null,
     * ]
     * @return Sale
     */
    public function bill(WorkOrder $workOrder, array $billingData): Sale
    {
        if ($workOrder->sale_id || $workOrder->status === 'invoiced') {
            throw new InvalidArgumentException("La Orden de Trabajo {$workOrder->order_number} ya ha sido facturada.");
        }

        $userId = $billingData['user_id'] ?? auth()->id();
        if (!$userId) {
            throw new InvalidArgumentException("Se requiere un usuario operador para emitir la factura.");
        }

        $customer = $workOrder->customer;
        $paymentMethod = $billingData['payment_method'] ?? 'cash_usd';
        $isCredit = ($paymentMethod === 'credit');

        if ($isCredit && !$customer->allow_credit) {
            throw new InvalidArgumentException("El cliente '{$customer->name}' no tiene habilitado el credito.");
        }

        $currency = $billingData['currency'] ?? 'USD';
        $exchangeRate = (float)($billingData['exchange_rate'] ?? 1.0);
        $exitMileage = $billingData['exit_mileage'] ?? $workOrder->entry_mileage;

        return DB::transaction(function () use ($workOrder, $customer, $userId, $paymentMethod, $isCredit, $currency, $exchangeRate, $exitMileage, $billingData) {
            $serviceProduct = $this->getOrCreateLaborServiceProduct();

            // 1. Create the Sale
            $sale = Sale::create([
                'user_id' => $userId,
                'customer_id' => $customer->id,
                'seller_id' => $customer->seller_id ?: $userId,
                'total' => $workOrder->total,
                'total_usd' => $workOrder->total,
                'items' => $workOrder->items()->where('is_approved', true)->count(),
                'status' => $isCredit ? 'pending' : 'paid',
                'type' => $isCredit ? 'credit' : 'cash',
                'credit_days' => $isCredit ? ($customer->credit_days ?: 15) : 0,
                'primary_currency_code' => $currency,
                'primary_exchange_rate' => $exchangeRate,
                'notes' => $billingData['notes'] ?? "Orden de Trabajo #{$workOrder->order_number} - Vehiculo: {$workOrder->vehicle?->full_name}",
            ]);

            // 2. Create SaleDetails and deduct inventory
            $approvedItems = $workOrder->items()->where('is_approved', true)->get();

            foreach ($approvedItems as $item) {
                $productId = $item->product_id;

                if (!$productId) {
                    $productId = $serviceProduct->id;
                }

                SaleDetail::create([
                    'sale_id' => $sale->id,
                    'product_id' => $productId,
                    'quantity' => (int)ceil((float)$item->quantity),
                    'regular_price' => (float)$item->unit_price,
                    'sale_price' => (float)$item->unit_price,
                    'discount' => 0.00,
                    'metadata' => json_encode([
                        'custom_name' => $item->description,
                        'work_order_item_id' => $item->id,
                        'type' => $item->type,
                        'mechanic_id' => $item->mechanic_id,
                        'mechanic_commission_amount' => $item->mechanic_commission_amount,
                    ]),
                ]);

                // Deduct inventory if it's a physical product with manage_stock enabled
                if ($item->type === 'part' && $item->product && $item->product->manage_stock) {
                    $item->product->decrement('stock_qty', $item->quantity);
                }
            }

            // 3. Register payment if not credit
            if (!$isCredit && (float)$workOrder->total > 0) {
                $payWay = match (strtolower($paymentMethod)) {
                    'zelle' => 'zelle',
                    'deposit', 'transfer', 'pago_movil' => 'deposit',
                    'wallet' => 'wallet',
                    default => 'cash',
                };

                Payment::create([
                    'user_id' => $userId,
                    'sale_id' => $sale->id,
                    'amount' => $workOrder->total,
                    'currency' => $currency,
                    'exchange_rate' => $exchangeRate,
                    'pay_way' => $payWay,
                    'type' => 'pay',
                    'payment_date' => now(),
                    'status' => 'approved',
                ]);
            }

            // 4. Update WorkOrder
            $workOrder->sale_id = $sale->id;
            $workOrder->status = 'invoiced';
            $workOrder->exit_mileage = $exitMileage;
            $workOrder->delivered_at = now();
            $workOrder->save();

            // 5. Update Vehicle current mileage
            if ($workOrder->vehicle && $exitMileage > $workOrder->vehicle->current_mileage) {
                $workOrder->vehicle->current_mileage = $exitMileage;
                $workOrder->vehicle->save();
            }

            return $sale;
        });
    }

    /**
     * Get or create a default service product for labor/services.
     */
    protected function getOrCreateLaborServiceProduct(): Product
    {
        $product = Product::where('sku', 'SRV-LABOR')->first();
        if ($product) {
            return $product;
        }

        $category = Category::firstOrCreate(
            ['name' => 'SERVICIOS TALLER']
        );

        $supplier = Supplier::firstOrCreate(
            ['name' => 'TALLER AUTOMOTRIZ INTERNO']
        );

        return Product::create([
            'sku' => 'SRV-LABOR',
            'name' => 'SERVICIO MANO DE OBRA TALLER',
            'type' => 'service',
            'status' => 'available',
            'cost' => 0.00,
            'price' => 0.00,
            'manage_stock' => 0,
            'stock_qty' => 0,
            'low_stock' => 0,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'show_in_sales' => false,
        ]);
    }
}
