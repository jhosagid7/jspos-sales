<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\DebitNote;
use App\Models\Configuration;
use Livewire\Livewire;
use App\Livewire\AccountsReceivableReport;
use App\Livewire\AccountsPayableReport;
use App\Livewire\PartialPayment;
use App\Livewire\PurchasePartialPayment;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Permission;

class AccountsReceivableReportTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $customer;
    protected $supplier;
    protected $sale;
    protected $purchase;
    protected $debitNote;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset static cache in ConfigurationService
        $ref = new \ReflectionClass(\App\Services\ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        // Setup Configuration
        Configuration::create([
            'business_name' => 'Test Business',
            'bcv_rate' => 54.50,
            'binance_rate' => 70.00,
            'binance_markup_points' => 5.00,
        ]);

        // Seed currencies
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Create user and give permissions
        $this->user = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::findOrCreate('Seller');
        $this->user->assignRole($role);
        Permission::findOrCreate('payments.register_direct');
        Permission::findOrCreate('payments.upload');
        Permission::findOrCreate('payments.pay');
        Permission::findOrCreate('payments.register');
        $this->user->givePermissionTo([
            'payments.register_direct',
            'payments.upload',
            'payments.pay',
        ]);

        // Create Customer with single quote in name
        $this->customer = Customer::create([
            'name' => "D' SANTIAGO C.A",
        ]);

        // Create Supplier with single quote in name
        $this->supplier = Supplier::create([
            'name' => "O' CONNOR SUPPLIES",
        ]);

        // Create Sale
        $this->sale = Sale::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_exchange_rate' => 1.0,
            'primary_currency_code' => 'USD',
        ]);

        // Create Debit Note
        $this->debitNote = DebitNote::create([
            'debit_number' => 'DN-001',
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'amount' => 50.00,
            'exchange_rate' => 1.0,
            'currency' => 'USD',
            'concept' => 'Test Debit Note',
            'status' => 'pending',
        ]);

        // Create Purchase
        $this->purchase = Purchase::create([
            'user_id' => $this->user->id,
            'supplier_id' => $this->supplier->id,
            'total' => 150.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
        ]);
    }

    public function test_init_payment_resolves_customer_name_when_passed_empty()
    {
        Livewire::actingAs($this->user)
            ->test(AccountsReceivableReport::class)
            ->call('initPayment', $this->sale->id, '')
            ->assertDispatched('initPayment', function ($name, $params) {
                // Ensure name of event is initPayment and the customer name is resolved correctly
                return $params['customer'] === "D' SANTIAGO C.A";
            });
    }

    public function test_init_debit_note_payment_resolves_customer_name_when_passed_empty()
    {
        Livewire::actingAs($this->user)
            ->test(AccountsReceivableReport::class)
            ->call('initDebitNotePayment', $this->debitNote->id, '')
            ->assertDispatched('initPayment', function ($name, $params) {
                return $params['customer'] === "D' SANTIAGO C.A";
            });
    }

    public function test_partial_payment_init_pay_resolves_customer_name_when_passed_empty()
    {
        Livewire::actingAs($this->user)
            ->test(PartialPayment::class)
            ->call('initPay', $this->sale->id, '', 100.00)
            ->assertDispatched('initPayment', function ($name, $params) {
                // The first argument to dispatched initPayment is total, but we can verify the customer param
                return $params['customer'] === "D' SANTIAGO C.A";
            });
    }

    public function test_purchase_partial_payment_init_pay_resolves_supplier_name_when_passed_empty()
    {
        Livewire::actingAs($this->user)
            ->test(PurchasePartialPayment::class)
            ->call('initPay', $this->purchase->id, '', 150.00)
            ->assertDispatched('initPayment', function ($name, $params) {
                return $params['customer'] === "O' CONNOR SUPPLIES";
            });
    }

    public function test_accounts_payable_report_init_payable_resolves_supplier_name_when_passed_empty()
    {
        Livewire::actingAs($this->user)
            ->test(AccountsPayableReport::class)
            ->call('initPayable', $this->purchase, '')
            ->assertDispatched('initPayment', function ($name, $params) {
                return $params['customer'] === "O' CONNOR SUPPLIES";
            });
    }

    public function test_accounts_receivable_report_set_supplier_can_be_called_with_null_or_empty()
    {
        // First set to a customer
        Livewire::actingAs($this->user)
            ->test(AccountsReceivableReport::class)
            ->call('setSupplier', $this->customer->toArray())
            ->assertSet('customer', $this->customer->toArray())
            // Now call without arguments to simulate clearing in real application (which dispatches event with no args or null)
            ->call('setSupplier')
            ->assertSet('customer', null);

        $this->assertNull(session('account_customer'));
    }

    public function test_accounts_payable_report_set_supplier_can_be_called_with_null_or_empty()
    {
        // First set to a supplier
        Livewire::actingAs($this->user)
            ->test(AccountsPayableReport::class)
            ->call('setSupplier', $this->supplier->toArray())
            ->assertSet('supplier', $this->supplier->toArray())
            // Now call without arguments to simulate clearing in real application
            ->call('setSupplier')
            ->assertSet('supplier', null);

        $this->assertNull(session('account_supplier'));
    }

    public function test_credit_sale_with_full_initial_payment_resolves_to_paid_at_checkout()
    {
        // Setup Customer
        $customer = Customer::create([
            'name' => 'Montenegro Customer',
            'allow_credit' => true,
            'credit_limit' => 1000.00
        ]);
        $customer->credit_status = 'active';
        $customer->saveQuietly();

        // Create a mock category
        $category = \App\Models\Category::create(['name' => 'Test Category']);

        // Create warehouse
        $warehouse = \App\Models\Warehouse::create([
            'name' => 'General Warehouse',
            'is_active' => true
        ]);

        // Associate user to warehouse
        $this->user->warehouse_id = $warehouse->id;
        $this->user->save();

        // Create a mock product
        $product = \App\Models\Product::create([
            'name' => 'Test Product', 
            'sku' => 'TEST-002', 
            'price' => 100, 
            'cost' => 50, 
            'stock_qty' => 10,
            'type' => 'physical',
            'status' => 'available',
            'manage_stock' => 1,
            'low_stock' => 1,
            'supplier_id' => $this->supplier->id,
            'category_id' => $category->id,
            'freight_type' => 'none'
        ]);

        // Attach product to warehouse with stock
        $product->warehouses()->attach($warehouse->id, ['stock_qty' => 10]);

        // Create an active cash register for the user
        \App\Models\CashRegister::create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'open_amount' => 1000,
            'opening_date' => now()
        ]);

        // Setup the cart session (Store reads from session("cart"))
        session(['cart' => [
            $product->id => [
                'pid' => $product->id,
                'qty' => 1,
                'base_price' => 100.00,
                'sale_price' => 100.00,
                'conversion_factor' => 1,
                'tax' => 0.00,
                'total' => 100.00,
                'sku' => $product->sku,
                'name' => $product->name,
                'pricelist' => [],
                'warehouse_id' => $warehouse->id
            ]
        ]]);

        // Test Livewire Sales component
        $component = Livewire::actingAs($this->user)
            ->test(\App\Livewire\Sales::class)
            ->call('setCustomer', $customer->toArray())
            ->set('payType', 2) // Credit type
            ->set('paymentAgreement', 'USD')
            ->set('totalCart', 100.00)
            ->set('totalInPrimaryCurrency', 100.00)
            ->set('cashAmount', 0.00)
            ->set('payments', [
                [
                    'method' => 'cash',
                    'currency' => 'USD',
                    'symbol' => '$',
                    'amount' => 100.00,
                    'amount_in_primary_currency' => 100.00,
                    'exchange_rate' => 1.0
                ]
            ])
            ->call('Store')
            ->assertHasNoErrors();

        // Check if sale exists and its status is paid (instead of pending)
        $sale = Sale::where('customer_id', $customer->id)->latest()->first();
        $this->assertNotNull($sale);
        $this->assertEquals('paid', $sale->status);
    }

    public function test_sale_debt_calculation_handles_foreign_currency_payments_correctly()
    {
        // 1. Create a credit sale of $100.00 USD
        $customer = Customer::create(['name' => 'Montenegro Client']);
        $sale = Sale::create([
            'user_id' => $this->user->id,
            'customer_id' => $customer->id,
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_exchange_rate' => 1.0,
            'primary_currency_code' => 'USD',
            'credit_days' => 15,
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
            'payment_date' => now(),
        ]);

        // 3. Add an approved payment in USD (20.00 USD with exchange rate 1.0 = 20.00 USD)
        \App\Models\Payment::create([
            'user_id' => $this->user->id,
            'sale_id' => $sale->id,
            'amount' => 20.00,
            'currency' => 'USD',
            'exchange_rate' => 1.0,
            'primary_exchange_rate' => 1.0,
            'pay_way' => 'cash',
            'type' => 'pay',
            'status' => 'approved',
            'payment_date' => now(),
        ]);

        // Assert debt is calculated correctly (100 - 50 - 20 = 30)
        $this->assertEquals(30.00, $sale->debt);

        // Act as user and make request to the PDF route to ensure it works without errors
        $response = $this->actingAs($this->user)->get(route('customer.debt.pdf', $customer->id));
        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_purchase_debt_calculation_handles_foreign_currency_payables_correctly()
    {
        // 1. Create a credit purchase of $150.00 USD
        $supplier = Supplier::create(['name' => 'Montenegro Supplier']);
        $purchase = Purchase::create([
            'user_id' => $this->user->id,
            'supplier_id' => $supplier->id,
            'total' => 150.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
        ]);

        // 2. Add an approved payable in VES/VED (3500.00 VES with exchange rate 70.00 = 50.00 USD)
        \App\Models\Payable::create([
            'user_id' => $this->user->id,
            'purchase_id' => $purchase->id,
            'amount' => 3500.00,
            'currency_code' => 'VES',
            'exchange_rate' => 70.00,
            'pay_way' => 'cash',
            'type' => 'pay',
        ]);

        // 3. Add an approved payable in USD (20.00 USD with exchange rate 1.0 = 20.00 USD)
        \App\Models\Payable::create([
            'user_id' => $this->user->id,
            'purchase_id' => $purchase->id,
            'amount' => 20.00,
            'currency_code' => 'USD',
            'exchange_rate' => 1.0,
            'pay_way' => 'cash',
            'type' => 'pay',
        ]);

        // Assert debt is calculated correctly (150 - 50 - 20 = 80)
        $this->assertEquals(80.00, $purchase->debt);

        // Test AccountsPayableReport initPayable displays correctly
        $component = Livewire::actingAs($this->user)
            ->test(AccountsPayableReport::class)
            ->call('initPayable', $purchase, $supplier->name);
        
        $this->assertEquals(80.00, $component->get('debt'));

        // Test PurchasePartialPayment displays correctly
        $component2 = Livewire::actingAs($this->user)
            ->test(PurchasePartialPayment::class)
            ->call('initPay', $purchase->id, $supplier->name);

        $this->assertEquals(80.00, $component2->get('debt'));
    }
}


