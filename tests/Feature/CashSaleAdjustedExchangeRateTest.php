<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Configuration;
use App\Models\Sale;
use App\Livewire\Sales;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CashSaleAdjustedExchangeRateTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.installed' => false,
            'tenant.modules' => ['module_credits', 'module_commissions', 'module_advanced_payments'],
        ]);

        // Reset static cache in ConfigurationService
        $ref = new \ReflectionClass(\App\Services\ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        // Configuration with 70.00 Binance rate and 5.00 markup points (Adjusted rate = 75.00)
        Configuration::create([
            'business_name' => 'Adjusted Rate Test Business',
            'decimals' => 2,
            'vat' => 0, // 0 VAT for simple math
            'bcv_rate' => 50.00,
            'binance_rate' => 70.00,
            'binance_markup_points' => 5.00,
        ]);

        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Sync VES/VED Currency rate to adjusted rate (75.00)
        \Illuminate\Support\Facades\DB::table('currencies')
            ->whereIn('code', ['VES', 'VED'])
            ->update([
                'exchange_rate' => 75.00,
                'updated_at' => now()
            ]);

        $this->user = User::factory()->create();
        
        \App\Models\CashRegister::create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'opening_date' => now(),
            'total_opening_amount' => 1000.00,
        ]);

        $this->customer = Customer::create([
            'name' => 'Cash Customer',
            'type' => 'Consumidor Final',
            'allow_credit' => false,
            'taxpayer_id' => 'V12345678',
            'address' => 'Caracas',
            'city' => 'Caracas',
            'phone' => '0412-0000000',
            'email' => 'cash@email.com',
            'seller_id' => $this->user->id,
        ]);
        
        \App\Models\CustomerConfig::create([
            'customer_id' => $this->customer->id,
            'commission_percent' => 0.00,
            'freight_percent' => 0.00,
            'exchange_diff_percent' => 0.00,
        ]);

        $category = \App\Models\Category::create(['name' => 'POS Category']);
        $supplier = \App\Models\Supplier::create([
            'name' => 'POS Supplier',
            'taxpayer_id' => 'J12345678',
            'address' => 'Address',
            'phone' => '0212-0000000',
        ]);

        $this->product = Product::create([
            'name' => 'POS Test Product',
            'sku' => 'POS-TEST-001',
            'cost' => 10.00,
            'price' => 10.00,
            'price1' => 10.00,
            'price_usd' => 10.00,
            'show_in_sales' => true,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_cash_sale_ved_payment_uses_adjusted_rate_75_instead_of_70()
    {
        $cartItem = [
            'id' => $this->product->id,
            'pid' => $this->product->id,
            'sku' => $this->product->sku,
            'name' => $this->product->name,
            'qty' => 1,
            'price' => 10.00,
            'base_price' => 10.00,
            'sale_price' => 10.00,
            'tax' => 0.00,
            'total' => 10.00,
            'pricelist' => [],
        ];

        session(['cart' => [$cartItem]]);

        // If paying 700.00 VES, at 75.00 rate it equals 9.33 USD (underpaid, should fail or block)
        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->call('setCustomer', $this->customer->toArray())
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 10.00)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'cash')
            ->set('paymentCurrency', 'VED')
            ->set('paymentAmount', 700.00)
            ->call('addPayment')
            ->call('Store')
            ->assertDispatched('noty', msg: "VENTA BLOQUEADA: El pago recibido en Bolívares no cubre el valor real de la venta debido a una tasa incorrecta o diferencial insuficiente.");

        $this->assertEquals(0, Sale::count());

        // Reset session for the next attempt
        session()->forget(['cart', 'payments', 'remainingAmount', 'change', 'changeDistribution', 'totalCartAtPayment', 'sale_customer']);
        session(['cart' => [$cartItem]]);

        // If paying 750.00 VES, at 75.00 rate it equals 10.00 USD (fully paid, should succeed)
        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->call('setCustomer', $this->customer->toArray())
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 10.00)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'cash')
            ->set('paymentCurrency', 'VED')
            ->set('paymentAmount', 750.00)
            ->call('addPayment')
            ->call('Store')
            ->assertHasNoErrors();

        $this->assertEquals(1, Sale::count());
        $sale = Sale::first();
        
        // Assert the exchange rate saved in the payment details is 75.00
        $paymentDetail = $sale->paymentDetails()->first();
        $this->assertNotNull($paymentDetail);
        $this->assertEquals(75.00, floatval($paymentDetail->exchange_rate));
        $this->assertEquals(10.00, floatval($paymentDetail->amount_in_primary_currency));
    }
}
