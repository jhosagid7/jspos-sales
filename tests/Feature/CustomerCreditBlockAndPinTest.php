<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use App\Models\Configuration;
use App\Services\CreditConfigService;
use App\Services\ConfigurationService;
use Carbon\Carbon;
use Livewire\Livewire;

class CustomerCreditBlockAndPinTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $category;
    protected $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset static cache in ConfigurationService
        $ref = new \ReflectionClass(ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        // Setup base configuration
        Configuration::create([
            'business_name' => 'Test Business',
            'sales_edit_timeout' => 1800,
            'global_credit_limit' => 500,
            'global_allow_credit' => true,
        ]);

        $this->seed(\Database\Seeders\CurrencySeeder::class);

        $this->adminUser = User::firstOrCreate(
            ['id' => 1],
            ['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password')]
        );

        $this->category = \App\Models\Category::create(['name' => 'Test Category']);
        $this->supplier = \App\Models\Supplier::create(['name' => 'Test Supplier', 'phone' => '12345']);
    }

    public function test_defaulted_customer_without_unpaid_overdue_invoices_is_allowed_credit()
    {
        $customer = Customer::create([
            'name' => 'Defaulted Paid Customer',
            'phone' => '12345678',
            'credit_status' => 'defaulted',
            'allow_credit' => true,
            'credit_limit' => 300,
        ]);

        // Create a historical credit sale that was paid
        $sale = Sale::create([
            'reference' => 'SALE-1',
            'customer_id' => $customer->id,
            'type' => 'credit',
            'status' => 'paid',
            'total_usd' => 100,
            'total' => 100,
            'items' => 1,
            'user_id' => $this->adminUser->id,
        ]);

        // Resolve credit config
        $config = CreditConfigService::getCreditConfig($customer);

        // Since there are no unpaid overdue invoices, allow_credit should be true
        $this->assertTrue($config['allow_credit']);
        $this->assertEquals(300, $config['credit_limit']);
    }

    public function test_defaulted_customer_with_unpaid_overdue_invoices_is_blocked_credit()
    {
        $customer = Customer::create([
            'name' => 'Defaulted Unpaid Customer',
            'phone' => '12345678',
            'credit_status' => 'defaulted',
            'allow_credit' => true,
            'credit_limit' => 300,
        ]);

        // Create an unpaid credit sale whose due date has passed
        $sale = Sale::create([
            'reference' => 'SALE-2',
            'customer_id' => $customer->id,
            'type' => 'credit',
            'status' => 'pending',
            'credit_days' => 10,
            'total_usd' => 100,
            'total' => 100,
            'items' => 1,
            'user_id' => $this->adminUser->id,
            'created_at' => now()->subDays(15), // Due date is 5 days ago
        ]);

        // Resolve credit config
        $config = CreditConfigService::getCreditConfig($customer);

        // Since there is an unpaid overdue invoice, allow_credit should be false (blocked)
        $this->assertFalse($config['allow_credit']);
        $this->assertEquals(0, $config['credit_limit']);
    }

    public function test_credit_sale_with_credito_payment_detail_saves_correctly_as_credit_pending()
    {
        // Authenticate seller
        $this->actingAs($this->adminUser);

        // Setup customer
        $customer = Customer::create([
            'name' => 'Normal Customer',
            'phone' => '12345678',
            'seller_id' => $this->adminUser->id,
            'allow_credit' => true,
            'credit_limit' => 500,
            'credit_days' => 30,
        ]);

        \App\Models\SellerConfig::create([
            'user_id' => $this->adminUser->id,
            'commission_percent' => 10.00,
            'freight_percent' => 6.00,
            'exchange_diff_percent' => 60.00,
        ]);

        \App\Models\CustomerConfig::create([
            'customer_id' => $customer->id,
            'commission_percent' => 8.00,
            'freight_percent' => 6.00,
            'exchange_diff_percent' => 45.00,
        ]);

        // Setup product
        $product = \App\Models\Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-01',
            'price' => 50.00,
            'cost' => 0.00,
            'manage_stock' => 0,
            'stock_qty' => 0,
            'low_stock' => 0,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
        ]);

        config(['tenant.modules' => ['module_credits']]);

        // Test Livewire component
        $component = Livewire::test(\App\Livewire\Sales::class)
            ->call('setCustomer', $customer->toArray());

        $cartItem = [
            'id' => $product->id,
            'pid' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'qty' => 1,
            'price' => 50.00,
            'base_price' => 50.00,
            'sale_price' => 50.00,
            'tax' => 0.00,
            'total' => 50.00,
            'pricelist' => [],
        ];

        session(['cart' => [$cartItem]]);
        $component->set('cart', collect([$cartItem]));
        $component->set('totalCart', 50.00);
        $component->set('itemsCart', 1);

        // Simulate choosing credit method in unified payment modal (which populates payments array with 'CREDITO')
        $component->set('selectedPaymentMethod', 'credit');
        $component->set('paymentAgreement', 'USD');
        $component->call('addPayment');

        // Verify payment list has CREDITO
        $payments = $component->get('payments');
        $this->assertCount(1, $payments);
        $this->assertEquals('CREDITO', $payments[0]['method']);

        // Finalize sale by calling Store
        $component->call('Store', new \App\Services\CashRegisterService());

        // Verify sale is saved in DB as credit/pending
        $sale = Sale::where('customer_id', $customer->id)->first();
        $this->assertNotNull($sale);
        $this->assertEquals('credit', $sale->type);
        $this->assertEquals('pending', $sale->status);
        $this->assertEquals(50.00, floatval($sale->debt));

        // Verify that NO sale_payment_details were created for CREDITO
        $this->assertCount(0, $sale->paymentDetails);
    }
}
