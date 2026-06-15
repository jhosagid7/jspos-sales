<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Audit\InvoicesAuditList;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;

class InvoicesAuditListTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $auditorUser;
    protected $unauthorizedUser;
    protected $customer;
    protected $seller;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-10 12:00:00'));

        config([
            'app.installed' => false,
            'tenant.modules' => ['module_credits', 'module_roles'],
        ]);

        Configuration::create([
            'business_name' => 'Test Business',
            'taxpayer_id' => 'V12345678',
            'address' => 'Test Address 123',
            'city' => 'Caracas',
            'phone' => '0212-5555555',
            'bcv_rate' => 54.50,
            'binance_rate' => 70.00,
            'binance_markup_points' => 5.00,
        ]);

        $this->seed(\Database\Seeders\CurrencySeeder::class);

        $this->adminUser = User::factory()->create();
        $this->auditorUser = User::factory()->create();
        $this->unauthorizedUser = User::factory()->create();
        $this->seller = User::factory()->create();

        Permission::findOrCreate('collections.audit');
        Permission::findOrCreate('system.is_seller');

        $this->auditorUser->givePermissionTo('collections.audit');
        $this->seller->givePermissionTo('system.is_seller');

        $this->customer = Customer::create([
            'name' => 'John Doe Customer',
            'taxpayer_id' => 'V11111111',
            'address' => 'Caracas',
            'city' => 'Caracas',
            'seller_id' => $this->seller->id,
        ]);
    }

    public function test_global_dashboard_requires_permission()
    {
        $this->get('/audit/invoices')->assertRedirect('/login');

        $this->actingAs($this->unauthorizedUser)
            ->get('/audit/invoices')
            ->assertStatus(403);

        $this->actingAs($this->auditorUser)
            ->get('/audit/invoices')
            ->assertStatus(200);
    }

    public function test_global_dashboard_lists_sales_and_filters()
    {
        $this->actingAs($this->auditorUser);

        // Sale 1: Audited, USD Agreement, Today
        $sale1 = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 150.00,
            'total_usd' => 150.00,
            'items' => 2,
            'status' => 'paid',
            'type' => 'credit',
            'payment_agreement' => 'USD',
            'is_audited' => true,
            'audited_at' => Carbon::now(),
            'created_at' => Carbon::now(),
        ]);

        // Sale 2: Not audited, BCV Agreement, Today
        $sale2 = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 200.00,
            'total_usd' => 200.00,
            'items' => 3,
            'status' => 'pending',
            'type' => 'credit',
            'payment_agreement' => 'BCV',
            'is_audited' => false,
            'created_at' => Carbon::now(),
        ]);

        // Sale 3: Voided / Deleted, USD Agreement
        $sale3 = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'status' => 'returned',
            'type' => 'credit',
            'payment_agreement' => 'USD',
            'deletion_reason' => 'Duplicate sale entry',
            'deletion_approved_at' => Carbon::now(),
            'created_at' => Carbon::now(),
        ]);

        // 1. Initial load (sees all)
        Livewire::test(InvoicesAuditList::class)
            ->assertViewHas('sales', function ($sales) use ($sale1, $sale2, $sale3) {
                return $sales->count() === 3 && $sales->pluck('id')->contains($sale1->id);
            });

        // 2. Filter by audited
        Livewire::test(InvoicesAuditList::class)
            ->set('auditStatus', 'audited')
            ->assertViewHas('sales', function ($sales) use ($sale1) {
                return $sales->count() === 1 && $sales->first()->id === $sale1->id;
            });

        // 3. Filter by not audited
        Livewire::test(InvoicesAuditList::class)
            ->set('auditStatus', 'not_audited')
            ->assertViewHas('sales', function ($sales) use ($sale2) {
                return $sales->count() === 1 && $sales->first()->id === $sale2->id;
            });

        // 4. Filter by deleted
        Livewire::test(InvoicesAuditList::class)
            ->set('auditStatus', 'deleted')
            ->assertViewHas('sales', function ($sales) use ($sale3) {
                return $sales->count() === 1 && $sales->first()->id === $sale3->id;
            });

        // 5. Filter by agreement BCV
        Livewire::test(InvoicesAuditList::class)
            ->set('paymentAgreement', 'BCV')
            ->assertViewHas('sales', function ($sales) use ($sale2) {
                return $sales->count() === 1 && $sales->first()->id === $sale2->id;
            });

        // 6. Filter by seller
        Livewire::test(InvoicesAuditList::class)
            ->set('sellerId', $this->seller->id)
            ->assertViewHas('sales', function ($sales) {
                return $sales->count() === 3;
            })
            ->set('sellerId', 9999) // Non-existent seller
            ->assertViewHas('sales', function ($sales) {
                return $sales->count() === 0;
            });

        // 7. Filter by paymentStatus paid
        Livewire::test(InvoicesAuditList::class)
            ->set('paymentStatus', 'paid')
            ->assertViewHas('sales', function ($sales) use ($sale1) {
                return $sales->count() === 1 && $sales->first()->id === $sale1->id;
            });

        // 8. Filter by paymentStatus pending
        Livewire::test(InvoicesAuditList::class)
            ->set('paymentStatus', 'pending')
            ->assertViewHas('sales', function ($sales) use ($sale2) {
                return $sales->count() === 1 && $sales->first()->id === $sale2->id;
            });
    }

    public function test_toggle_invoice_audit_manual()
    {
        $this->actingAs($this->auditorUser);

        $sale = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'payment_agreement' => 'USD',
            'is_audited' => false,
        ]);

        Livewire::test(InvoicesAuditList::class)
            ->call('toggleInvoiceAudit', $sale->id)
            ->assertHasNoErrors();

        $sale->refresh();
        $this->assertTrue($sale->is_audited);
        $this->assertNotNull($sale->audited_at);

        Livewire::test(InvoicesAuditList::class)
            ->call('toggleInvoiceAudit', $sale->id)
            ->assertHasNoErrors();

        $sale->refresh();
        $this->assertFalse($sale->is_audited);
        $this->assertNull($sale->audited_at);
    }

    public function test_toggle_invoice_audit_blocked_for_usd_invoice_paid_at_bcv()
    {
        $this->actingAs($this->auditorUser);

        // Setup Rate History for the date of payment/sale
        $paymentDate = Carbon::parse('2026-06-10 12:00:00');
        \App\Models\ExchangeRateHistory::create([
            'rate_type' => 'BCV',
            'rate' => 54.50,
            'created_at' => $paymentDate,
        ]);
        \App\Models\ExchangeRateHistory::create([
            'rate_type' => 'BinanceReal',
            'rate' => 70.00,
            'created_at' => $paymentDate,
        ]);

        $sale = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'total_usd' => 100.00,
            'base_amount' => 80.00,
            'freight_amount' => 10.00,
            'commission_amount' => 10.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'payment_agreement' => 'USD',
            'is_audited' => false,
            'created_at' => $paymentDate,
        ]);

        // Payment in VES at BCV rate
        Payment::create([
            'user_id' => $this->adminUser->id,
            'sale_id' => $sale->id,
            'amount' => 5450.00, // 100 * 54.50
            'currency' => 'VES',
            'exchange_rate' => 54.50,
            'pay_way' => 'deposit',
            'status' => 'approved',
            'payment_date' => $paymentDate,
            'created_at' => $paymentDate,
        ]);

        // Attempt manual toggle -> should flash error and NOT audit
        Livewire::test(InvoicesAuditList::class)
            ->call('toggleInvoiceAudit', $sale->id)
            ->assertHasNoErrors()
            ->assertSee('No se puede auditar esta factura: fue facturada a acuerdo USD pero pagada a tasa BCV.');

        $sale->refresh();
        $this->assertFalse($sale->is_audited);
    }

    public function test_show_sale_details_loads_and_handles_multiple_payments()
    {
        $this->actingAs($this->auditorUser);

        $sale = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'payment_agreement' => 'USD',
            'is_audited' => false,
        ]);

        $payment1 = Payment::create([
            'user_id' => $this->adminUser->id,
            'sale_id' => $sale->id,
            'amount' => 60.00,
            'currency' => 'USD',
            'exchange_rate' => 1.00,
            'pay_way' => 'zelle',
            'status' => 'approved',
        ]);

        $payment2 = Payment::create([
            'user_id' => $this->adminUser->id,
            'sale_id' => $sale->id,
            'amount' => 40.00,
            'currency' => 'USD',
            'exchange_rate' => 1.00,
            'pay_way' => 'cash',
            'status' => 'approved',
        ]);

        dump("payment1 id: " . $payment1->id);
        dump("payment2 id: " . $payment2->id);

        Livewire::test(InvoicesAuditList::class)
            ->call('showSaleDetails', $sale->id)
            ->assertSet('selectedSale.id', $sale->id)
            ->assertSet('selectedPaymentId', 'payment-' . $payment2->id) // payment2 has ID 2, first due to desc order
            ->call('selectPayment', 'payment-' . $payment1->id)
            ->assertSet('selectedPaymentId', 'payment-' . $payment1->id)
            ->call('closeSaleDetails')
            ->assertSet('selectedSale', null)
            ->assertSet('selectedPaymentId', null);
    }

    public function test_show_sale_details_handles_checkout_payments()
    {
        $this->actingAs($this->auditorUser);

        $sale = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 80.00,
            'total_usd' => 80.00,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'payment_agreement' => 'USD',
            'is_audited' => false,
        ]);

        $checkoutPayment = \App\Models\SalePaymentDetail::create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 80.00,
            'exchange_rate' => 1.00,
            'amount_in_primary_currency' => 80.00,
        ]);

        Livewire::test(InvoicesAuditList::class)
            ->call('showSaleDetails', $sale->id)
            ->assertSet('selectedSale.id', $sale->id)
            ->assertSet('selectedPaymentId', 'detail-' . $checkoutPayment->id)
            ->call('closeSaleDetails')
            ->assertSet('selectedSale', null)
            ->assertSet('selectedPaymentId', null);
    }
}
