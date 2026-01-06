<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Payment;
use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SaleSeeder extends Seeder
{
    public function run()
    {
        $seller = User::where('profile', 'Vendedor')->first();
        $customer = Customer::first();
        $product = Product::first();
        $primaryCurrency = Currency::where('is_primary', 1)->first();

        if (!$seller || !$customer || !$product) {
            $this->command->info('Missing dependencies for SaleSeeder (User, Customer, or Product).');
            return;
        }

        // 1. Venta de Contado (Comisión Inmediata)
        $this->createSale($seller, $customer, $product, 'paid', 'cash', now(), 'Venta Contado - Comisión Inmediata');

        // 2. Venta a Crédito (Pendiente - Sin Comisión)
        $this->createSale($seller, $customer, $product, 'pending', 'credit', now(), 'Venta Crédito - Pendiente');

        // 3. Venta a Crédito Pagada (Comisión al Liquidar)
        $sale = $this->createSale($seller, $customer, $product, 'pending', 'credit', now()->subDays(5), 'Venta Crédito - Pagada');
        $this->createPayment($sale, $sale->total); // Pagar total
        $sale->update(['status' => 'paid']);
        \App\Services\CommissionService::calculateCommission($sale); // Trigger manual para simular el flujo

        // 4. Venta Antigua Pagada Hoy (Prueba de Umbral 2 o Vencido)
        $saleOld = $this->createSale($seller, $customer, $product, 'pending', 'credit', now()->subDays(35), 'Venta Antigua - Pagada Hoy');
        $this->createPayment($saleOld, $saleOld->total);
        $saleOld->update(['status' => 'paid']);
        \App\Services\CommissionService::calculateCommission($saleOld);
    }

    private function createSale($seller, $customer, $product, $status, $type, $date, $note)
    {
        $qty = 2;
        $price = $product->price;
        $total = $qty * $price;
        
        $sale = Sale::create([
            'user_id' => $seller->id,
            'customer_id' => $customer->id,
            'total' => $total,
            'items' => $qty,
            'cash' => $type == 'cash' ? $total : 0,
            'change' => 0,
            'status' => $status,
            'type' => $type,
            'notes' => $note,
            'created_at' => $date,
            'updated_at' => $date,
            'primary_currency_code' => 'COP', // Asumiendo default
            'primary_exchange_rate' => 1,
            'invoice_number' => 'TEST-' . uniqid(),
        ]);

        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => $qty,
            'regular_price' => $price,
            'sale_price' => $price,
            'price_usd' => $price, // Asumiendo base USD
            'exchange_rate' => 1,
        ]);

        if ($status == 'paid' && $type == 'cash') {
             \App\Services\CommissionService::calculateCommission($sale);
        }

        return $sale;
    }

    private function createPayment($sale, $amount)
    {
        Payment::create([
            'user_id' => $sale->user_id,
            'sale_id' => $sale->id,
            'amount' => $amount,
            'currency' => 'COP',
            'pay_way' => 'cash',
            'type' => 'settled',
            'payment_date' => now()
        ]);
    }
}
