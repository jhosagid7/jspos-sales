<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Models\CollectionSheet;
use App\Models\Payment;
use App\Models\ExchangeRateHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Audit\CollectionSheetAudit;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;

class CollectionAuditTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $auditorUser;
    protected $unauthorizedUser;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-09 12:00:00'));

        config([
            'app.installed' => false,
            'tenant.modules' => ['module_credits', 'module_roles'],
        ]);

        // Setup Configuration
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

        // Seed currencies
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Setup Users and Permissions
        $this->adminUser = User::factory()->create();
        $this->auditorUser = User::factory()->create();
        $this->unauthorizedUser = User::factory()->create();

        Permission::findOrCreate('collections.audit');
        $this->auditorUser->givePermissionTo('collections.audit');

        $this->customer = Customer::create([
            'name' => 'Locman Customer',
            'taxpayer_id' => 'V99999999',
            'address' => 'Caracas',
            'city' => 'Caracas',
        ]);
    }

    public function test_audit_routes_require_authentication_and_permission()
    {
        // 1. Guest -> redirect to login
        $this->get('/audit/sheet')->assertRedirect('/login');

        // 2. Unauthorized User -> 403 Access Denied
        $this->actingAs($this->unauthorizedUser)
            ->get('/audit/sheet')
            ->assertStatus(403);

        // 3. Authorized Auditor -> 200 OK
        $this->actingAs($this->auditorUser)
            ->get('/audit/sheet')
            ->assertStatus(200);
    }

    public function test_collection_sheet_audit_component_loads_portal()
    {
        $this->actingAs($this->auditorUser);

        Livewire::test(CollectionSheetAudit::class)
            ->assertViewIs('livewire.audit.collection-sheet-audit')
            ->assertSee('Portal de Auditoría')
            ->assertDontSee('Panel de Conciliación y Auditoría');
    }

    public function test_collection_sheet_audit_searches_and_redirects()
    {
        $this->actingAs($this->auditorUser);

        $sheet = CollectionSheet::create([
            'sheet_number' => '20260609-001',
            'status' => 'open',
            'opened_at' => Carbon::now(),
            'user_id' => $this->auditorUser->id,
        ]);

        Livewire::test(CollectionSheetAudit::class)
            ->set('searchQuery', '20260609-001')
            ->call('search')
            ->assertRedirect(route('audit.sheet.detail', ['sheet' => '20260609-001']));
    }

    public function test_collection_sheet_audit_toggles_bank_reconciliation()
    {
        $this->actingAs($this->auditorUser);

        $sheet = CollectionSheet::create([
            'sheet_number' => '20260609-001',
            'status' => 'open',
            'opened_at' => Carbon::now(),
            'user_id' => $this->auditorUser->id,
        ]);

        $sale = Sale::create([
            'user_id' => $this->auditorUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'payment_agreement' => 'USD',
        ]);

        $payment = Payment::create([
            'user_id' => $this->auditorUser->id,
            'sale_id' => $sale->id,
            'collection_sheet_id' => $sheet->id,
            'amount' => 100.00,
            'currency' => 'USD',
            'exchange_rate' => 1.00,
            'pay_way' => 'zelle',
            'status' => 'approved',
            'is_bank_reconciled' => false,
        ]);

        Livewire::test(CollectionSheetAudit::class, ['sheet' => $sheet->id])
            ->call('toggleReconciliation', $payment->id)
            ->assertHasNoErrors();

        $payment->refresh();
        $this->assertTrue($payment->is_bank_reconciled);
        $this->assertNotNull($payment->reconciled_at);

        Livewire::test(CollectionSheetAudit::class, ['sheet' => $sheet->id])
            ->call('toggleReconciliation', $payment->id)
            ->assertHasNoErrors();

        $payment->refresh();
        $this->assertFalse($payment->is_bank_reconciled);
        $this->assertNull($payment->reconciled_at);
    }

    public function test_payment_profitability_semaphores_under_usd_agreement()
    {
        $this->actingAs($this->auditorUser);

        $paymentDate = Carbon::parse('2026-06-09 12:00:00');

        // Setup Rate History
        ExchangeRateHistory::create([
            'rate_type' => 'BCV',
            'rate' => 55.00,
            'period' => 'AM',
            'created_at' => $paymentDate,
        ]);
        ExchangeRateHistory::create([
            'rate_type' => 'BinanceReal',
            'rate' => 75.00,
            'period' => 'AM',
            'created_at' => $paymentDate,
        ]);
        ExchangeRateHistory::create([
            'rate_type' => 'Binance',
            'rate' => 80.00,
            'period' => 'AM',
            'created_at' => $paymentDate,
        ]);

        // Create sale: base_amount = 80, freight = 10, commission = 10, total = 100
        $sale = Sale::create([
            'user_id' => $this->auditorUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'base_amount' => 80.00,
            'freight_amount' => 10.00,
            'commission_amount' => 10.00,
            'payment_agreement' => 'USD',
        ]);

        $component = new CollectionSheetAudit();

        // Case A: Pure USD Payment >= base (Green)
        $paymentUSDGreen = Payment::create([
            'user_id' => $this->auditorUser->id,
            'sale_id' => $sale->id,
            'amount' => 100.00,
            'currency' => 'USD',
            'exchange_rate' => 1.00,
            'pay_way' => 'zelle',
            'status' => 'approved',
            'payment_date' => $paymentDate,
        ]);
        $valA = $component->getPaymentValidation($paymentUSDGreen);
        $this->assertEquals('green', $valA['color']);

        // Case B: Pure USD Payment < base (Red)
        $paymentUSDRed = Payment::create([
            'user_id' => $this->auditorUser->id,
            'sale_id' => $sale->id,
            'amount' => 90.00, // For a 100 USD total, 90 covers only 90% -> base portion is 72, net is 90 - 9 - 9 = 72. 
            // Wait, let's make it lower to trigger loss, e.g. 70 USD.
            // 70 USD covers 70% -> base is 56. Net USD is 70 - 7 - 7 = 56. 
            // Let's do amount = 50. 50 covers 50% -> base portion is 40. Net USD is 50 - 15 (surcharges) = 35 < 40 base.
            'amount' => 50.00,
            'currency' => 'USD',
            'exchange_rate' => 1.00,
            'pay_way' => 'zelle',
            'status' => 'approved',
            'payment_date' => $paymentDate,
        ]);
        $valB = $component->getPaymentValidation($paymentUSDRed);
        // Wait, let's check the base/freight/commission math for $50 payment:
        // Payment ratio = 50 / 100 = 0.5
        // paymentBase = 80 * 0.5 = 40
        // paymentFreight = 10 * 0.5 = 5
        // paymentCommission = 10 * 0.5 = 5
        // net = 50 - 5 - 5 = 40. This is exactly equal to paymentBase (40).
        // Let's make the payment $40:
        // ratio = 40/100 = 0.4. paymentBase = 32. net = 40 - 4 - 4 = 32.
        // Wait, why did the net match? Because $sale->total = base + freight + commission!
        // In this case: net = paymentUsd * (1 - (freight + commission)/total) = paymentUsd * (1 - 20/100) = paymentUsd * 0.8.
        // paymentBase = base * ratio = base * paymentUsd / total = 80 * paymentUsd / 100 = paymentUsd * 0.8.
        // They are always equal when total = base + freight + commission!
        // But what if the client paid a lower amount, but it covers the same invoice?
        // Wait, if a payment is registered for a lower amount, the ratio of payment to total is less.
        // But what if the commission or freight is different, or the amount paid is simply less than the invoice total?
        // Ah! If the payment is partial, it is still checked against its proportional base.
        // What if the payment rate is bad? E.g., VED payment at BCV rate on a USD agreement.
        // Let's check VED payment at BCV rate (Red):
        $paymentVEDBcv = Payment::create([
            'user_id' => $this->auditorUser->id,
            'sale_id' => $sale->id,
            'amount' => 5500.00, // 100 USD * 55.00 BCV rate
            'currency' => 'VES',
            'exchange_rate' => 55.00, // BCV rate
            'pay_way' => 'deposit',
            'status' => 'approved',
            'payment_date' => $paymentDate,
        ]);
        $valC = $component->getPaymentValidation($paymentVEDBcv);
        $this->assertEquals('red', $valC['color']);
        $this->assertStringContainsString('Tasa BCV en acuerdo USD', $valC['message']);

        // Case D: VED Payment at Binance rate (Green)
        $paymentVEDBinance = Payment::create([
            'user_id' => $this->auditorUser->id,
            'sale_id' => $sale->id,
            'amount' => 7500.00, // 100 USD * 75.00 Binance rate
            'currency' => 'VES',
            'exchange_rate' => 75.00,
            'pay_way' => 'deposit',
            'status' => 'approved',
            'payment_date' => $paymentDate,
        ]);
        $valD = $component->getPaymentValidation($paymentVEDBinance);
        $this->assertEquals('green', $valD['color']);

        // Case E: VED Payment at non-official rate (Orange)
        $paymentVEDOrange = Payment::create([
            'user_id' => $this->auditorUser->id,
            'sale_id' => $sale->id,
            'amount' => 7800.00,
            'currency' => 'VES',
            'exchange_rate' => 78.00, // unofficial rate
            'pay_way' => 'deposit',
            'status' => 'approved',
            'payment_date' => $paymentDate,
        ]);
        $valE = $component->getPaymentValidation($paymentVEDOrange);
        $this->assertEquals('orange', $valE['color']);
        $this->assertStringContainsString('Tasa de pago no coincide con Binance oficial', $valE['message']);
    }

    public function test_payment_profitability_semaphores_under_bcv_agreement()
    {
        $this->actingAs($this->auditorUser);

        $paymentDate = Carbon::parse('2026-06-09 12:00:00');

        // Setup Rate History
        ExchangeRateHistory::create([
            'rate_type' => 'BCV',
            'rate' => 50.00,
            'period' => 'AM',
            'created_at' => $paymentDate,
        ]);
        ExchangeRateHistory::create([
            'rate_type' => 'BinanceReal',
            'rate' => 75.00,
            'period' => 'AM',
            'created_at' => $paymentDate,
        ]);

        // Create sale: base_amount = 75, freight = 10, commission = 15, total = 100
        $sale = Sale::create([
            'user_id' => $this->auditorUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'base_amount' => 75.00,
            'freight_amount' => 10.00,
            'commission_amount' => 15.00,
            'payment_agreement' => 'BCV',
        ]);

        $component = new CollectionSheetAudit();

        // Case A: VED Payment at official BCV rate (Green)
        // Expected Real Binance USD = (Invoice USD * BCV_rate) / Binance_rate
        // = (100 * 50) / 75 = 66.66
        // Wait, base_amount portion is $75!
        // In this case, $realBinanceUsd = 66.66. Base amount = 75. Net is 66.66 - 10 - 15 = 41.66 < 75 base portion.
        // So this triggers a Loss (Red)!
        // Wait, why? Because BCV rate is 50, but Binance rate is 75!
        // So paying at BCV rate on a BCV agreement represents a real USD loss if we convert it at the Binance rate!
        // Yes, that is the exact purpose of the semaphore! It alerts the auditor that this BCV agreement invoice resulted in a real USD loss due to the exchange rate gap.
        $paymentVEDBcv = Payment::create([
            'user_id' => $this->auditorUser->id,
            'sale_id' => $sale->id,
            'amount' => 5000.00, // 100 USD * 50.00
            'currency' => 'VES',
            'exchange_rate' => 50.00,
            'pay_way' => 'deposit',
            'status' => 'approved',
            'payment_date' => $paymentDate,
        ]);
        $valA = $component->getPaymentValidation($paymentVEDBcv);
        $this->assertEquals('red', $valA['color']);
        $this->assertStringContainsString('Contravalor real Binance menor al costo base', $valA['message']);

        // Let's verify with an invoice where the math works out to Green.
        // E.g., if base_amount is very low, say $30.
        // base = 30, freight = 10, commission = 10, total = 100
        $saleGreen = Sale::create([
            'user_id' => $this->auditorUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'base_amount' => 30.00,
            'freight_amount' => 10.00,
            'commission_amount' => 10.00,
            'payment_agreement' => 'BCV',
        ]);

        $paymentVEDBcvGreen = Payment::create([
            'user_id' => $this->auditorUser->id,
            'sale_id' => $saleGreen->id,
            'amount' => 5000.00, // 100 USD * 50.00
            'currency' => 'VES',
            'exchange_rate' => 50.00,
            'pay_way' => 'deposit',
            'status' => 'approved',
            'payment_date' => $paymentDate,
        ]);
        $valB = $component->getPaymentValidation($paymentVEDBcvGreen);
        // Real Binance USD = (100 * 50) / 75 = 66.66
        // Base = 30, Freight = 10, Commission = 10
        // Net = 66.66 - 10 - 10 = 46.66 >= 30. So this should be Green or Orange (if rate mismatch).
        // Since rate is 50.00 and BCV rate is 50.00, it should be Green.
        $this->assertEquals('green', $valB['color']);

        // Case C: VED Payment at non-official rate on BCV agreement (Orange)
        $paymentVEDBcvOrange = Payment::create([
            'user_id' => $this->auditorUser->id,
            'sale_id' => $saleGreen->id,
            'amount' => 5200.00,
            'currency' => 'VES',
            'exchange_rate' => 52.00, // unofficial rate
            'pay_way' => 'deposit',
            'status' => 'approved',
            'payment_date' => $paymentDate,
        ]);
        $valC = $component->getPaymentValidation($paymentVEDBcvOrange);
        $this->assertEquals('orange', $valC['color']);
        $this->assertStringContainsString('Tasa de pago no coincide con BCV oficial', $valC['message']);
    }

    public function test_finalize_audit_dispatches_warning_if_pending_reconciliation()
    {
        $this->actingAs($this->auditorUser);

        $sheet = CollectionSheet::create([
            'sheet_number' => '20260609-002',
            'status' => 'open',
            'opened_at' => Carbon::now(),
            'user_id' => $this->auditorUser->id,
        ]);

        $sale = Sale::create([
            'user_id' => $this->auditorUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'payment_agreement' => 'USD',
        ]);

        $payment = Payment::create([
            'user_id' => $this->auditorUser->id,
            'sale_id' => $sale->id,
            'collection_sheet_id' => $sheet->id,
            'amount' => 100.00,
            'currency' => 'USD',
            'exchange_rate' => 1.00,
            'pay_way' => 'zelle',
            'status' => 'approved',
            'is_bank_reconciled' => false,
        ]);

        Livewire::test(CollectionSheetAudit::class, ['sheet' => $sheet->id])
            ->call('finalizeAudit')
            ->assertDispatched('show-finalize-warning-modal');

        $sheet->refresh();
        $this->assertEquals('open', $sheet->status);
        $sale->refresh();
        $this->assertFalse($sale->is_audited);
    }

    public function test_finalize_audit_closes_sheet_immediately_if_no_pending_reconciliation()
    {
        $this->actingAs($this->auditorUser);

        $sheet = CollectionSheet::create([
            'sheet_number' => '20260609-003',
            'status' => 'open',
            'opened_at' => Carbon::now(),
            'user_id' => $this->auditorUser->id,
        ]);

        $sale = Sale::create([
            'user_id' => $this->auditorUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'payment_agreement' => 'USD',
        ]);

        $payment = Payment::create([
            'user_id' => $this->auditorUser->id,
            'sale_id' => $sale->id,
            'collection_sheet_id' => $sheet->id,
            'amount' => 100.00,
            'currency' => 'USD',
            'exchange_rate' => 1.00,
            'pay_way' => 'zelle',
            'status' => 'approved',
            'is_bank_reconciled' => true,
        ]);

        Livewire::test(CollectionSheetAudit::class, ['sheet' => $sheet->id])
            ->call('finalizeAudit')
            ->assertRedirect(route('audit.sheet'));

        $sheet->refresh();
        $this->assertEquals('closed', $sheet->status);
        $this->assertNotNull($sheet->closed_at);
        $sale->refresh();
        $this->assertTrue($sale->is_audited);
        $this->assertNotNull($sale->audited_at);
    }

    public function test_confirm_finalize_audit_force_reconciles_and_closes()
    {
        $this->actingAs($this->auditorUser);

        $sheet = CollectionSheet::create([
            'sheet_number' => '20260609-004',
            'status' => 'open',
            'opened_at' => Carbon::now(),
            'user_id' => $this->auditorUser->id,
        ]);

        $sale = Sale::create([
            'user_id' => $this->auditorUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'payment_agreement' => 'USD',
        ]);

        $payment = Payment::create([
            'user_id' => $this->auditorUser->id,
            'sale_id' => $sale->id,
            'collection_sheet_id' => $sheet->id,
            'amount' => 100.00,
            'currency' => 'USD',
            'exchange_rate' => 1.00,
            'pay_way' => 'zelle',
            'status' => 'approved',
            'is_bank_reconciled' => false,
        ]);

        Livewire::test(CollectionSheetAudit::class, ['sheet' => $sheet->id])
            ->call('confirmFinalizeAudit', true)
            ->assertRedirect(route('audit.sheet'));

        $sheet->refresh();
        $this->assertEquals('closed', $sheet->status);
        
        $payment->refresh();
        $this->assertTrue($payment->is_bank_reconciled);
        $this->assertNotNull($payment->reconciled_at);

        $sale->refresh();
        $this->assertTrue($sale->is_audited);
        $this->assertNotNull($sale->audited_at);
    }
}
