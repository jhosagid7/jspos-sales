<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Configuration;
use App\Models\Order;
use App\Models\Sale;
use App\Livewire\Sales;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class POSPaymentAgreementEnforcementTest extends TestCase
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
            'tenant.modules' => ['module_credits', 'module_commissions'],
        ]);

        // Reset static cache in ConfigurationService
        $ref = new \ReflectionClass(\App\Services\ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        Configuration::create([
            'business_name' => 'POS Agreement Test Business',
            'decimals' => 2,
            'vat' => 16,
            'bcv_rate' => 54.50,
            'binance_rate' => 70.00,
            'binance_markup_points' => 5.00,
        ]);

        $this->seed(\Database\Seeders\CurrencySeeder::class);

        $this->user = User::factory()->create();
        $this->customer = Customer::create([
            'name' => 'Test POS Customer',
            'type' => 'Consumidor Final',
            'allow_credit' => true,
            'credit_days' => 15,
            'credit_limit' => 1000.00,
            'taxpayer_id' => 'V99999999',
            'address' => 'Customer Address',
            'city' => 'Caracas',
            'phone' => '0412-1111111',
            'email' => 'customer@email.com',
            'seller_id' => $this->user->id,
        ]);
        
        // Create customer config
        \App\Models\CustomerConfig::create([
            'customer_id' => $this->customer->id,
            'commission_percent' => 5.00,
            'freight_percent' => 0.00,
            'exchange_diff_percent' => 0.00,
        ]);

        $category = \App\Models\Category::create([
            'name' => 'Test Category POS',
        ]);

        $supplier = \App\Models\Supplier::create([
            'name' => 'Test Supplier POS',
            'taxpayer_id' => 'J88888888',
            'address' => 'Supplier Address POS',
            'phone' => '0212-2222222',
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

    public function test_payment_agreement_starts_empty_and_resets_on_customer_select()
    {
        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->assertSet('paymentAgreement', '')
            ->call('setCustomer', $this->customer->toArray())
            ->assertSet('paymentAgreement', '');
    }

    public function test_cannot_register_credit_payment_without_selecting_agreement()
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

        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->call('setCustomer', $this->customer->toArray())
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 10.00)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'credit')
            ->set('paymentAgreement', '') // Empty
            ->call('addPayment')
            ->assertDispatched('noty', msg: 'DEBE SELECCIONAR UN ACUERDO DE PAGO ANTES DE REGISTRAR EL CRÉDITO')
            ->assertSet('payments', []);
    }

    public function test_can_register_credit_payment_with_selected_agreement()
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

        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->call('setCustomer', $this->customer->toArray())
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 10.00)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'credit')
            ->set('paymentAgreement', 'USD') // Selected
            ->call('addPayment')
            ->assertSet('payments', [
                [
                    'method' => 'CREDITO',
                    'amount' => 10,
                    'currency' => 'USD',
                    'symbol' => '$',
                    'exchange_rate' => 1,
                    'amount_in_primary_currency' => 10,
                    'details' => 'Crédito 15 días',
                ]
            ]);
    }

    public function test_store_order_defaults_empty_agreement_to_usd_or_bcv()
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

        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->call('setCustomer', $this->customer->toArray())
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 10.00)
            ->set('itemsCart', 1)
            ->set('paymentAgreement', '') // Empty
            ->call('storeOrder');

        // Order is saved and defaults to USD
        $order = Order::where('customer_id', $this->customer->id)->first();
        $this->assertNotNull($order);
        $this->assertEquals('USD', $order->payment_agreement);
    }
}
