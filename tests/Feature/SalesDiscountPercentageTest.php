<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\Configuration;
use App\Models\Sale;
use App\Models\Order;
use App\Livewire\Settings;
use App\Livewire\Sales;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SalesDiscountPercentageTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $warehouse;
    protected $category;
    protected $supplier;
    protected $customer;
    protected $config;
    protected $currency;

    protected function setUp(): void
    {
        parent::setUp();
        \App\Services\ConfigurationService::clearCache();

        $this->user = User::factory()->create();

        \App\Models\CashRegister::create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'opening_date' => now(),
            'total_opening_amount' => 1000.00,
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Almacén Principal',
            'is_active' => true,
        ]);

        $this->currency = Currency::create([
            'name' => 'Dólar Estadounidense',
            'code' => 'USD',
            'symbol' => '$',
            'label' => 'USD',
            'exchange_rate' => 1.00,
            'is_primary' => true,
        ]);

        $this->config = Configuration::create([
            'business_name' => 'Test Business',
            'taxpayer_id' => 'J123456789',
            'address' => 'Av Principal',
            'city' => 'Caracas',
            'printer_name' => 'POS-80',
            'default_warehouse_id' => $this->warehouse->id,
            'sales_show_discount_percentage' => false,
            'vat' => 0,
            'decimals' => 2,
        ]);

        $this->category = Category::create(['name' => 'General']);
        $this->supplier = Supplier::create([
            'name' => 'Proveedor General',
            'address' => 'Local',
            'phone' => '123456',
            'email' => 'p@example.com',
        ]);

        $this->customer = Customer::create([
            'name' => 'Cliente Mostrador',
            'type' => 'Consumidor Final',
            'seller_id' => $this->user->id,
        ]);

        \Spatie\Permission\Models\Permission::findOrCreate('sales.manage_adjustments');
        $this->user->givePermissionTo('sales.manage_adjustments');
    }

    protected function tearDown(): void
    {
        \App\Services\ConfigurationService::clearCache();
        parent::tearDown();
    }

    public function test_settings_can_toggle_sales_discount_percentage_visibility()
    {
        $this->actingAs($this->user);

        // Initially disabled by default
        $this->assertFalse((bool) $this->config->sales_show_discount_percentage);

        // Toggle on via Settings Livewire
        Livewire::test(Settings::class)
            ->set('salesShowDiscountPercentage', true)
            ->call('saveConfig')
            ->assertDispatched('noty');

        $this->config->refresh();
        $this->assertTrue((bool) $this->config->sales_show_discount_percentage);

        // Toggle off via Settings Livewire
        Livewire::test(Settings::class)
            ->set('salesShowDiscountPercentage', false)
            ->call('saveConfig')
            ->assertDispatched('noty');

        $this->config->refresh();
        $this->assertFalse((bool) $this->config->sales_show_discount_percentage);
    }

    public function test_discount_percentage_field_is_hidden_when_disabled_in_settings()
    {
        $this->actingAs($this->user);
        $this->config->update(['sales_show_discount_percentage' => false]);
        \App\Services\ConfigurationService::clearCache();

        Livewire::test(Sales::class)
            ->assertDontSeeHtml('Descuento (%):');
    }

    public function test_discount_percentage_field_is_visible_when_enabled_in_settings()
    {
        $this->actingAs($this->user);
        $this->config->update(['sales_show_discount_percentage' => true]);
        \App\Services\ConfigurationService::clearCache();

        Livewire::test(Sales::class)
            ->assertSeeHtml('Descuento (%):');
    }

    public function test_discount_percentage_recalculates_cart_total_and_stores_in_metadata()
    {
        $this->actingAs($this->user);
        $this->config->update(['sales_show_discount_percentage' => true]);
        \App\Services\ConfigurationService::clearCache();

        // Create product: price $2000
        $product = Product::create([
            'name' => 'PRODUCTO TEST 2000',
            'sku' => 'TEST-2000',
            'cost' => 1000.00,
            'price' => 2000.00,
            'stock_qty' => 100,
            'low_stock' => 5,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'show_in_sales' => true,
            'manage_stock' => 1,
        ]);

        \App\Models\ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'stock_qty' => 100,
        ]);

        $component = Livewire::test(Sales::class)
            ->call('setCustomer', $this->customer)
            ->call('AddProduct', $product, 5);

        // Verify Gross Total is $10,000 before discount
        $this->assertEquals(10000.00, (float)$component->get('grossTotalCart'));
        $this->assertEquals(10000.00, (float)$component->get('totalCart'));

        // Apply 10% discount
        $component->set('discount_percentage', 10);

        // Verify Discount Amount is $1,000 and Net Total is $9,000
        $this->assertEquals(1000.00, (float)$component->get('discountAmountPrimary'));
        $this->assertEquals(1000.00, (float)$component->get('displayDiscountAmount'));
        $this->assertEquals(9000.00, (float)$component->get('totalCart'));
        $this->assertEquals(9000.00, (float)$component->get('displayTotalCart'));

        // Simulate payment with exact net total ($9,000)
        $component->set('cashAmount', 9000.00)
            ->set('totalInPrimaryCurrency', 9000.00)
            ->set('payments', [
                [
                    'method' => 'cash',
                    'amount' => 9000.00,
                    'currency' => 'USD',
                    'symbol' => '$',
                    'exchange_rate' => 1.0,
                    'amount_in_primary_currency' => 9000.00,
                ]
            ]);

        // Complete sale
        $cashRegisterService = app(\App\Services\CashRegisterService::class);
        $component->call('Store', $cashRegisterService);

        // Verify Sale in database
        $sale = Sale::orderBy('id', 'desc')->first();
        $this->assertNotNull($sale);
        $this->assertEquals(9000.00, (float)$sale->total);
        $this->assertEquals(9000.00, (float)$sale->total_usd);
        $this->assertEquals(1000.00, (float)$sale->discount);

        // Verify Metadata JSON
        $this->assertNotNull($sale->metadata);
        $metadata = is_array($sale->metadata) ? $sale->metadata : json_decode($sale->metadata, true);
        $this->assertEquals(10, $metadata['discount_percentage']);
        $this->assertEquals(1000.00, (float)$metadata['discount_amount']);
        $this->assertEquals(10000.00, (float)$metadata['subtotal_before_discount']);
    }

    public function test_pending_order_stores_and_restores_discount_percentage()
    {
        $this->actingAs($this->user);
        $this->config->update(['sales_show_discount_percentage' => true]);
        \App\Services\ConfigurationService::clearCache();

        $product = Product::create([
            'name' => 'PRODUCTO TEST 100',
            'sku' => 'TEST-100',
            'cost' => 50.00,
            'price' => 100.00,
            'stock_qty' => 50,
            'low_stock' => 5,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'show_in_sales' => true,
            'manage_stock' => 1,
        ]);

        \App\Models\ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'stock_qty' => 50,
        ]);

        // Create pending order with 15% discount on $200 (2 units)
        $component = Livewire::test(Sales::class)
            ->call('setCustomer', $this->customer)
            ->call('AddProduct', $product, 2);

        $this->assertEquals(200.00, (float)$component->get('grossTotalCart'));
        $this->assertEquals(200.00, (float)$component->get('totalCart'));

        $component->set('discount_percentage', 15);
        $this->assertEquals(170.00, (float)$component->get('totalCart'));

        $component->call('storeOrder');

        $order = Order::orderBy('id', 'desc')->first();
        $this->assertNotNull($order);
        $this->assertEquals(170.00, (float)$order->total); // $200 - 15% ($30) = $170
        $this->assertEquals(30.00, (float)$order->discount);

        $metadata = is_array($order->metadata) ? $order->metadata : json_decode($order->metadata, true);
        $this->assertEquals(15, $metadata['discount_percentage']);
        $this->assertEquals(30.00, (float)$metadata['discount_amount']);

        // Load order back to cart and verify discount percentage is restored
        $restoreComponent = Livewire::test(Sales::class)
            ->call('loadOrderToCart', $order->id);

        $this->assertEquals(15, (float)$restoreComponent->get('discount_percentage'));
        $this->assertEquals(170.00, (float)$restoreComponent->get('totalCart'));
    }
}
