<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Product;
use App\Models\Currency;
use App\Models\Configuration;
use App\Models\CashRegister;
use App\Livewire\Sales;
use App\Livewire\Users;
use App\Livewire\Reports\SellerGroupedReport;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SharedTerminalPosTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $operator1;
    protected $operator2;
    protected $sharedTerminalUser;
    protected $regularUser;
    protected $customer;
    protected $product;
    protected $currency;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset static cache in ConfigurationService
        $ref = new \ReflectionClass(\App\Services\ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        // Create Configuration
        Configuration::create([
            'business_name' => 'JSPOS Sales Test',
            'taxpayer_id' => 'V-12345678-9',
            'address' => 'Main Street 123',
            'phone' => '1234567',
            'enable_shared_cash_register' => true,
        ]);

        // Create Primary Currency
        $this->currency = Currency::create([
            'name' => 'Dolares',
            'code' => 'USD',
            'label' => 'USD',
            'symbol' => '$',
            'exchange_rate' => 1,
            'is_primary' => 1,
            'status' => 1
        ]);

        // Create Permissions
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'pos.select_operator']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'reports.sales']);

        // Create Users / Operators
        $this->admin = User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'status' => 'Active',
            'profile' => 'Administrador'
        ]);

        $this->operator1 = User::factory()->create([
            'name' => 'Cajero Carlos',
            'email' => 'carlos@test.com',
            'status' => 'Active',
            'profile' => 'Cajero'
        ]);

        $this->operator2 = User::factory()->create([
            'name' => 'Cajera Maria',
            'email' => 'maria@test.com',
            'status' => 'Active',
            'profile' => 'Cajero'
        ]);

        $this->regularUser = User::factory()->create([
            'name' => 'Caja Individual 1',
            'email' => 'caja1@test.com',
            'status' => 'Active',
            'profile' => 'Cajero',
            'is_shared_terminal' => false
        ]);

        $this->sharedTerminalUser = User::factory()->create([
            'name' => 'Caja Principal Compartida',
            'email' => 'cajacompartida@test.com',
            'status' => 'Active',
            'profile' => 'Cajero',
            'is_shared_terminal' => true
        ]);
        $this->sharedTerminalUser->assignedOperators()->sync([$this->operator1->id, $this->operator2->id]);

        // Create Customer and CustomerConfig
        $this->customer = Customer::create([
            'name' => 'Cliente General',
            'taxpayer_id' => '12345678',
            'address' => 'Calle 100',
            'city' => 'San Cristobal',
            'type' => 'Consumidor Final',
            'seller_id' => $this->admin->id,
        ]);
        \App\Models\CustomerConfig::create([
            'customer_id' => $this->customer->id,
            'commission_percent' => 0.00,
            'freight_percent' => 0.00,
            'exchange_diff_percent' => 0.00,
        ]);

        // Create Category, Supplier and Product
        $category = Category::create(['name' => 'General']);
        $supplier = \App\Models\Supplier::create([
            'name' => 'Test Supplier',
            'taxpayer_id' => 'J88888888',
            'address' => 'Supplier Address',
            'phone' => '0212-2222222',
        ]);
        $this->product = Product::create([
            'name' => 'Producto Prueba',
            'sku' => 'PROD-001',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'cost' => 10,
            'price' => 20,
            'price1' => 20,
            'stock_qty' => 100,
            'low_stock' => 0,
            'manage_stock' => 0,
            'show_in_sales' => true,
            'type' => 'physical'
        ]);

        // Open Cash Register
        CashRegister::create([
            'user_id' => $this->admin->id,
            'total_opening_amount' => 100,
            'status' => 'open',
            'opening_date' => now(),
        ]);
    }

    public function test_user_model_shared_terminal_relations()
    {
        $user = User::factory()->create([
            'name' => 'Terminal Test',
            'email' => 'terminal@test.com',
            'status' => 'Active',
            'is_shared_terminal' => true
        ]);

        $user->assignedOperators()->sync([$this->operator1->id, $this->operator2->id]);

        $this->assertTrue((bool)$user->is_shared_terminal);
        $this->assertCount(2, $user->assignedOperators);
        $this->assertEquals([$this->operator1->id, $this->operator2->id], $user->getAssignedOperatorIds());
    }

    public function test_users_livewire_saves_and_syncs_shared_terminal_operators()
    {
        $this->actingAs($this->admin);

        // Create a new shared terminal user via Livewire
        Livewire::test(Users::class)
            ->call('Add')
            ->set('user.name', 'Caja 15 Compartida')
            ->set('user.email', 'caja15@test.com')
            ->set('user.profile', 'Cajero')
            ->set('user.status', 'Active')
            ->set('pwd', 'password123')
            ->set('confirm_pwd', 'password123')
            ->set('user.is_shared_terminal', true)
            ->set('selectedTerminalOperators', [$this->operator1->id])
            ->call('Store')
            ->assertDispatched('noty');

        $createdUser = User::where('email', 'caja15@test.com')->first();
        $this->assertNotNull($createdUser);
        $this->assertTrue((bool)$createdUser->is_shared_terminal);
        $this->assertEquals([$this->operator1->id], $createdUser->getAssignedOperatorIds());

        // Edit the user and update assigned operators
        Livewire::test(Users::class)
            ->call('Edit', $createdUser->id)
            ->assertSet('selectedTerminalOperators', [$this->operator1->id])
            ->set('selectedTerminalOperators', [$this->operator1->id, $this->operator2->id])
            ->call('Store');

        $this->assertCount(2, $createdUser->fresh()->assignedOperators);
    }

    public function test_regular_user_pos_mounts_as_non_shared_terminal()
    {
        $this->actingAs($this->regularUser);

        Livewire::test(Sales::class)
            ->assertSet('isSharedTerminal', false)
            ->assertSet('availableOperators', [])
            ->assertSet('selected_operator_id', null);
    }

    public function test_shared_terminal_user_pos_mounts_with_assigned_operators()
    {
        $this->actingAs($this->sharedTerminalUser);

        $test = Livewire::test(Sales::class)
            ->assertSet('isSharedTerminal', true);

        $available = $test->get('availableOperators');
        $this->assertCount(2, $available);
        $operatorIds = collect($available)->pluck('id')->toArray();
        $this->assertContains($this->operator1->id, $operatorIds);
        $this->assertContains($this->operator2->id, $operatorIds);
    }

    protected function getCartItem($qty = 1)
    {
        return [
            'id' => uniqid(),
            'pid' => $this->product->id,
            'sku' => $this->product->sku,
            'name' => $this->product->name,
            'qty' => $qty,
            'price' => 20.00,
            'base_price' => 20.00,
            'sale_price' => 20.00,
            'tax' => 0.00,
            'total' => 20.00 * $qty,
            'pricelist' => [],
        ];
    }

    public function test_shared_terminal_blocks_payment_without_selected_operator()
    {
        $cartItem = $this->getCartItem(1);
        session(['cart' => [$cartItem]]);
        session(['sale_customer' => $this->customer->toArray()]);

        Livewire::actingAs($this->sharedTerminalUser)
            ->test(Sales::class)
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 20.00)
            ->set('itemsCart', 1)
            ->set('customer', $this->customer->toArray())
            ->call('initPayment', 1)
            ->assertDispatched('noty', msg: 'DEBE SELECCIONAR EL OPERADOR RESPONSABLE DE LA VENTA');
    }

    public function test_shared_terminal_blocks_hold_order_without_selected_operator()
    {
        $cartItem = $this->getCartItem(1);
        session(['cart' => [$cartItem]]);
        session(['sale_customer' => $this->customer->toArray()]);

        Livewire::actingAs($this->sharedTerminalUser)
            ->test(Sales::class)
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 20.00)
            ->set('itemsCart', 1)
            ->set('customer', $this->customer->toArray())
            ->call('storeOrder')
            ->assertDispatched('noty', msg: 'DEBE SELECCIONAR EL OPERADOR RESPONSABLE DE LA VENTA');
    }

    public function test_regular_user_sale_is_attributed_to_auth_user()
    {
        $cartItem = $this->getCartItem(1);
        session(['cart' => [$cartItem]]);
        session(['sale_customer' => $this->customer->toArray()]);

        Livewire::actingAs($this->regularUser)
            ->test(Sales::class)
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 20.00)
            ->set('itemsCart', 1)
            ->set('customer', $this->customer->toArray())
            ->set('payments', [
                [
                    'method' => 'cash',
                    'currency' => 'USD',
                    'symbol' => '$',
                    'amount' => 20,
                    'exchange_rate' => 1,
                    'amount_in_primary_currency' => 20,
                ]
            ])
            ->set('totalInPrimaryCurrency', 20)
            ->call('Store', app(\App\Services\CashRegisterService::class));

        $sale = Sale::latest('id')->first();
        $this->assertNotNull($sale);
        $this->assertEquals($this->regularUser->id, $sale->user_id);
    }

    public function test_shared_terminal_sale_is_attributed_to_selected_operator()
    {
        $cartItem = $this->getCartItem(1);
        session(['cart' => [$cartItem]]);
        session(['sale_customer' => $this->customer->toArray()]);

        Livewire::actingAs($this->sharedTerminalUser)
            ->test(Sales::class)
            ->set('cart', collect([$cartItem]))
            ->set('totalCart', 20.00)
            ->set('itemsCart', 1)
            ->set('customer', $this->customer->toArray())
            ->set('selected_operator_id', $this->operator2->id)
            ->set('payments', [
                [
                    'method' => 'cash',
                    'currency' => 'USD',
                    'symbol' => '$',
                    'amount' => 20,
                    'exchange_rate' => 1,
                    'amount_in_primary_currency' => 20,
                ]
            ])
            ->set('totalInPrimaryCurrency', 20)
            ->call('Store', app(\App\Services\CashRegisterService::class));

        $sale = Sale::latest('id')->first();
        $this->assertNotNull($sale);
        // CRITICAL: Must be attributed to operator2 (Maria), NOT the shared terminal login
        $this->assertEquals($this->operator2->id, $sale->user_id);
    }

    public function test_shared_terminal_sale_appears_under_selected_operator_in_report()
    {
        // Make sale under Operator 1 (Carlos)
        $cartItem1 = $this->getCartItem(1);
        session(['cart' => [$cartItem1]]);
        session(['sale_customer' => $this->customer->toArray()]);

        Livewire::actingAs($this->sharedTerminalUser)
            ->test(Sales::class)
            ->set('cart', collect([$cartItem1]))
            ->set('totalCart', 20.00)
            ->set('itemsCart', 1)
            ->set('customer', $this->customer->toArray())
            ->set('selected_operator_id', $this->operator1->id)
            ->set('payments', [
                [
                    'method' => 'cash',
                    'currency' => 'USD',
                    'symbol' => '$',
                    'amount' => 20,
                    'exchange_rate' => 1,
                    'amount_in_primary_currency' => 20,
                ]
            ])
            ->set('totalInPrimaryCurrency', 20)
            ->call('Store', app(\App\Services\CashRegisterService::class));

        // Make sale under Operator 2 (Maria)
        $cartItem2 = $this->getCartItem(2);
        session(['cart' => [$cartItem2]]);
        session(['sale_customer' => $this->customer->toArray()]);

        Livewire::actingAs($this->sharedTerminalUser)
            ->test(Sales::class)
            ->set('cart', collect([$cartItem2]))
            ->set('totalCart', 40.00)
            ->set('itemsCart', 2)
            ->set('customer', $this->customer->toArray())
            ->set('selected_operator_id', $this->operator2->id)
            ->set('payments', [
                [
                    'method' => 'cash',
                    'currency' => 'USD',
                    'symbol' => '$',
                    'amount' => 40,
                    'exchange_rate' => 1,
                    'amount_in_primary_currency' => 40,
                ]
            ])
            ->set('totalInPrimaryCurrency', 40)
            ->call('Store', app(\App\Services\CashRegisterService::class));

        // Clean session and enable module for SellerGroupedReport
        session()->forget(['cart', 'payments', 'remainingAmount', 'change', 'changeDistribution', 'totalCartAtPayment', 'sale_customer']);
        config(['tenant.modules' => ['module_seller_grouped']]);

        // Run SellerGroupedReport
        $reportComponent = Livewire::actingAs($this->admin)
            ->test(SellerGroupedReport::class)
            ->call('setToday');

        $reportData = $reportComponent->instance()->getReportData();
        $sellerNamesInReport = $reportData->keys()->toArray();

        $this->assertContains($this->operator1->name, $sellerNamesInReport);
        $this->assertContains($this->operator2->name, $sellerNamesInReport);
    }
}
