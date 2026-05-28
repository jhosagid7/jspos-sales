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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;

class AccountsReceivableReportTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $supplier;
    protected $sale;
    protected $purchase;
    protected $debitNote;

    protected function setUp(): void
    {
        parent::setUp();

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
}


