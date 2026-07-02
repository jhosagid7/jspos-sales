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
        
        \App\Models\CashRegister::create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'opening_date' => now(),
            'total_opening_amount' => 1000.00,
        ]);

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

    public function test_bcv_credit_sale_blocked_if_insufficient_differential()
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

        // Gap is (70.00 - 54.50)/54.50 * 100 = 28.44%. activeDiff is 0.00%.
        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->call('setCustomer', $this->customer->toArray())
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 10.00)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'credit')
            ->set('paymentAgreement', 'BCV')
            ->call('addPayment')
            ->call('Store')
            ->assertDispatched('noty', msg: "VENTA BLOQUEADA: El diferencial del cliente (0.00%) no cubre la brecha cambiaria (28.44%).");

        $this->assertEquals(0, Sale::count());
    }

    public function test_bcv_credit_sale_allowed_if_sufficient_differential()
    {
        // Update customer differential to 30%
        \App\Models\CustomerConfig::where('customer_id', $this->customer->id)->update(['exchange_diff_percent' => 30.00]);

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
            ->set('selectedPaymentMethod', 'credit')
            ->set('paymentAgreement', 'BCV')
            ->call('addPayment')
            ->call('Store')
            ->assertHasNoErrors();

        $this->assertEquals(1, Sale::count());
        $sale = Sale::first();
        $this->assertEquals('BCV', $sale->payment_agreement);
    }

    public function test_cash_sale_with_ved_payment_blocked_if_underpaid()
    {
        // Temporarily set VAT to 0 so base total USD is exactly 10.00 USD
        Configuration::first()->update(['vat' => 0]);
        
        // Set customer commission to 0 to keep base total at 10.00 USD
        \App\Models\CustomerConfig::where('customer_id', $this->customer->id)->update([
            'commission_percent' => 0.00
        ]);

        // Reset static cache in ConfigurationService
        $ref = new \ReflectionClass(\App\Services\ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

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

        // Total USD base is 10.00 USD.
        // We pay 600.00 VES. At system rate (60.00), this covers totalCart.
        // At Binance rate (70.00), this is 600/70 = 8.57 USD, which does not cover 10.00 USD.
        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->call('setCustomer', $this->customer->toArray())
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 10.00)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'cash')
            ->set('paymentCurrency', 'VED')
            ->set('paymentAmount', 600.00)
            ->call('addPayment')
            ->call('Store')
            ->assertDispatched('noty', msg: "VENTA BLOQUEADA: El pago recibido en Bolívares no cubre el valor real de la venta debido a una tasa incorrecta o diferencial insuficiente.");

        $this->assertEquals(0, Sale::count());
    }

    public function test_cash_sale_with_ved_payment_allowed_if_paid_at_binance()
    {
        // Temporarily set VAT to 0 so base total USD is exactly 10.00 USD
        Configuration::first()->update(['vat' => 0]);

        // Set customer commission to 0 to keep base total at 10.00 USD
        \App\Models\CustomerConfig::where('customer_id', $this->customer->id)->update([
            'commission_percent' => 0.00
        ]);

        // Reset static cache in ConfigurationService
        $ref = new \ReflectionClass(\App\Services\ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

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

        // Pay 750.00 VES which at the adjusted Binance rate (75.00) covers exactly 10.00 USD.
        $test = Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->call('setCustomer', $this->customer->toArray())
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 10.00)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'cash')
            ->set('paymentCurrency', 'VED')
            ->set('paymentAmount', 750.00)
            ->call('addPayment');

        fwrite(STDERR, "Payments added in Binance test: " . print_r($test->get('payments'), true) . "\n");

        $test->call('Store');

        foreach ($test->effects['dispatches'] ?? [] as $dispatch) {
            if ($dispatch['name'] === 'noty') {
                fwrite(STDERR, "NOTY DISPATCHED: " . print_r($dispatch['params'], true) . "\n");
            }
        }

        $this->assertEquals(1, Sale::count());
    }

    public function test_customer_differential_ignored_when_commissions_check_inactive()
    {
        // Setup customer with differential
        \App\Models\CustomerConfig::where('customer_id', $this->customer->id)->update([
            'exchange_diff_percent' => 30.00,
            'commission_percent' => 0.00
        ]);

        // Temporarily set VAT to 0 so base total USD is exactly 10.00 USD
        Configuration::first()->update(['vat' => 0]);

        // Reset static cache in ConfigurationService
        $ref = new \ReflectionClass(\App\Services\ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

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

        // Grant permission to prevent auto-enable of commissions in render()
        \Spatie\Permission\Models\Permission::findOrCreate('sales.manage_adjustments');
        $this->user->givePermissionTo('sales.manage_adjustments');

        // Even though client has 30% differential configured, since applyCommissions is FALSE,
        // the differential is ignored. 
        // Thus, paying 545.00 VES (which would cover 10 USD at BCV rate 54.50) is converted at Binance rate (75.00),
        // resulting in 545 / 75 = 7.27 USD, which does not cover 10 USD.
        // The sale should be BLOCKED.
        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->call('setCustomer', $this->customer->toArray())
            ->set('applyCommissions', false)
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 10.00)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'cash')
            ->set('paymentCurrency', 'VED')
            ->set('paymentAmount', 545.00)
            ->call('addPayment')
            ->call('Store')
            ->assertDispatched('noty', msg: "VENTA BLOQUEADA: El pago recibido en Bolívares no cubre el valor real de la venta debido a una tasa incorrecta o diferencial insuficiente.");

        $this->assertEquals(0, Sale::count());
    }

    public function test_customer_differential_applied_when_commissions_check_active()
    {
        // Setup customer with differential
        \App\Models\CustomerConfig::where('customer_id', $this->customer->id)->update([
            'exchange_diff_percent' => 40.00,
            'commission_percent' => 0.00
        ]);

        // Temporarily set VAT to 0 so base total USD is exactly 10.00 USD
        Configuration::first()->update(['vat' => 0]);

        // Reset static cache in ConfigurationService
        $ref = new \ReflectionClass(\App\Services\ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

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

        // With applyCommissions = TRUE and sufficient differential, the rate to use for VES is BCV (54.50).
        // Paying 763.00 VES covers exactly 14.00 USD (10 USD base + 40% differential) at BCV rate.
        // The sale should succeed.
        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->call('setCustomer', $this->customer->toArray())
            ->set('applyCommissions', true)
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 14.00)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'cash')
            ->set('paymentCurrency', 'VED')
            ->set('paymentAmount', 763.00)
            ->call('addPayment')
            ->call('Store')
            ->assertHasNoErrors();

        $this->assertEquals(1, Sale::count());
    }

    public function test_payments_preserved_and_recalculated_when_toggling_commissions_checkbox()
    {
        // Setup customer with differential
        \App\Models\CustomerConfig::where('customer_id', $this->customer->id)->update([
            'exchange_diff_percent' => 30.00,
            'commission_percent' => 0.00
        ]);

        // Temporarily set VAT to 0 so base total USD is exactly 10.00 USD
        Configuration::first()->update(['vat' => 0]);

        // Reset static cache in ConfigurationService
        $ref = new \ReflectionClass(\App\Services\ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

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

        // Grant permission to prevent auto-enable of commissions in render()
        \Spatie\Permission\Models\Permission::findOrCreate('sales.manage_adjustments');
        $this->user->givePermissionTo('sales.manage_adjustments');

        session(['cart' => [$cartItem]]);

        $component = Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->call('setCustomer', $this->customer->toArray())
            ->set('applyCommissions', false)
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 10.00)
            ->set('itemsCart', 1)
            ->call('initPayment', 1)
            ->set('selectedPaymentMethod', 'cash')
            ->set('paymentCurrency', 'VED')
            ->set('paymentAmount', 500.00)
            ->call('addPayment');

        // At Binance rate (75.00), 500 VES is 6.67 USD.
        // Remaining should be 10.00 - 6.67 = 3.33 USD.
        $this->assertEquals(500, $component->get('payments')[0]['amount']);
        $this->assertEquals('VED', $component->get('payments')[0]['currency']);
        $this->assertEquals(75, $component->get('payments')[0]['exchange_rate']);
        $this->assertEquals(round(500/75, 2), round($component->get('payments')[0]['amount_in_primary_currency'], 2));
        $this->assertEquals(round(10 - 500/75, 2), round($component->get('remainingAmount'), 2));

        // Now toggle commissions to TRUE, which also changes totalCart to 13.00 USD
        $component->set('applyCommissions', true)
            ->set('totalCart', 13.00)
            ->call('initPayment', 1); // Re-init payment (simulating opening modal again)

        // Payments list must be preserved, and VED payment should have been converted at BCV rate (54.50).
        // 500 VES / 54.50 = 9.17 USD.
        // Remaining should be 13.00 - 9.17 = 3.83 USD.
        $this->assertCount(1, $component->get('payments'));
        $this->assertEquals(500, $component->get('payments')[0]['amount']);
        $this->assertEquals(54.50, $component->get('payments')[0]['exchange_rate']);
        $this->assertEquals(round(500/54.50, 2), round($component->get('payments')[0]['amount_in_primary_currency'], 2));
        $this->assertEquals(round(13 - 500/54.50, 2), round($component->get('remainingAmount'), 2));
    }

    public function test_pos_component_loads_outstanding_invoices_correctly_with_foreign_currency_payments()
    {
        // 1. Create a credit sale for $100.00 USD
        $sale = Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'invoice_number' => 'F00000999',
            'total' => 100.00,
            'total_usd' => 100.00,
            'primary_exchange_rate' => 1.0,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'credit_days' => 15,
            'created_at' => now()->subDays(5),
        ]);

        // 2. Add an approved payment in VES/VED (3500.00 VES with exchange rate 70.00 = 50.00 USD)
        \App\Models\Payment::create([
            'user_id' => $this->user->id,
            'sale_id' => $sale->id,
            'amount' => 3500.00,
            'currency' => 'VES',
            'exchange_rate' => 70.00,
            'primary_exchange_rate' => 1.0,
            'pay_way' => 'cash',
            'type' => 'pay',
            'status' => 'approved',
            'payment_date' => now()->subDays(2),
        ]);

        // 3. Test the Sales Livewire component
        $component = Livewire::actingAs($this->user)
            ->test(Sales::class);

        // Select the customer to trigger loading outstanding invoices
        $component->call('setCustomer', $this->customer->toArray());

        $customerData = $component->get('customer');
        $this->assertNotNull($customerData);
        $this->assertArrayHasKey('outstanding_invoices', $customerData);
        
        // The list should contain our invoice F00000999
        $outstanding = collect($customerData['outstanding_invoices']);
        $this->assertCount(1, $outstanding);

        $invoice = $outstanding->first();
        $this->assertEquals('F00000999', $invoice['invoice_number']);
        $this->assertEquals(100.00, $invoice['total']);
        $this->assertEquals(50.00, $invoice['paid']);
        $this->assertEquals(50.00, $invoice['pending']);
    }
}
