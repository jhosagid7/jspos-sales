<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Models\ExchangeRateHistory;
use App\Models\ExchangeRateApproval;
use Livewire\Livewire;
use App\Livewire\Common\PaymentComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ExchangeRateRestrictionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Ensure Configuration exists
        Configuration::create([
            'business_name' => 'Test Business',
            'bcv_rate' => 54.50,
            'binance_rate' => 70.00,
            'binance_markup_points' => 5.00,
        ]);

        // Seed currencies
        $this->seed(\Database\Seeders\CurrencySeeder::class);
    }

    public function test_pure_usd_invoice_restricts_bcv_rate_and_loads_binance_rates()
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'John Doe',
        ]);

        // Pure USD Credit Invoice (applied_exchange_diff_percent = 0)
        $sale = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'total' => 100,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'applied_exchange_diff_percent' => 0.00,
            'primary_exchange_rate' => 1.00
        ]);

        // Create daily histories for today
        $today = Carbon::now()->format('Y-m-d');
        
        ExchangeRateHistory::create([
            'rate_type' => 'BCV',
            'rate' => 54.50,
            'user_id' => $user->id,
        ]);

        ExchangeRateHistory::create([
            'rate_type' => 'BinanceReal',
            'rate' => 70.00,
            'user_id' => $user->id,
            'period' => 'AM'
        ]);

        ExchangeRateHistory::create([
            'rate_type' => 'Binance',
            'rate' => 75.00,
            'user_id' => $user->id,
            'period' => 'AM'
        ]);

        Livewire::actingAs($user)
            ->test(PaymentComponent::class)
            ->call('initPayment', 100, 'USD', $customer->name, true, null, false, 0, 0, true, true, $customer->id, 0, ['sale_id' => $sale->id])
            ->set('paymentCurrency', 'VED')
            ->set('paymentMethod', 'cash')
            ->set('paymentDate', $today)
            ->call('lookupHistoricalRate')
            ->assertSet('isUSDInvoice', true)
            ->assertSet('paymentCurrency', 'VED')
            ->assertSet('customExchangeRate', 70.00) // First BinanceReal option
            ->assertCount('rateOptions', 2); // Only BinanceReal (70.00) and Binance (75.00)

        // Assert that BCV rate (54.50) is NOT in the rateOptions
        $hasBCV = collect(Livewire::actingAs($user)
            ->test(PaymentComponent::class)
            ->call('initPayment', 100, 'USD', $customer->name, true, null, false, 0, 0, true, true, $customer->id, 0, ['sale_id' => $sale->id])
            ->set('paymentCurrency', 'VED')
            ->set('paymentMethod', 'cash')
            ->set('paymentDate', $today)
            ->call('lookupHistoricalRate')
            ->get('rateOptions')
        )->contains('rate', 54.50);

        $this->assertFalse($hasBCV, "Pure USD invoice allowed selecting BCV rate!");
    }

    public function test_invoice_with_differential_allows_bcv_rate()
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'John Doe',
        ]);

        // Invoice with 70% differential
        $sale = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'total' => 170,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'applied_exchange_diff_percent' => 70.00,
            'primary_exchange_rate' => 1.00
        ]);

        $today = Carbon::now()->format('Y-m-d');
        
        ExchangeRateHistory::create([
            'rate_type' => 'BCV',
            'rate' => 54.50,
            'user_id' => $user->id,
        ]);

        Livewire::actingAs($user)
            ->test(PaymentComponent::class)
            ->call('initPayment', 170, 'USD', $customer->name, true, null, false, 0, 0, true, true, $customer->id, 0, ['sale_id' => $sale->id])
            ->set('paymentCurrency', 'VED')
            ->set('paymentMethod', 'cash')
            ->set('paymentDate', $today)
            ->call('lookupHistoricalRate')
            ->assertSet('isUSDInvoice', false)
            ->assertSet('customExchangeRate', 54.50); // Primary option should be BCV
    }

    public function test_custom_rate_approval_loop_on_site_supervisor()
    {
        // Clear Spatie Permissions cache to ensure clean test environment
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'John Doe',
        ]);

        // Create Spatie Admin Role and Permission
        Role::firstOrCreate(['name' => 'Admin']);
        Permission::firstOrCreate(['name' => 'payments.approve_custom_rate']);

        // Supervisor user with Admin role or approve permission
        // Note: we supply password as plain text because of hashed model cast in Laravel 10+
        $supervisor = User::factory()->create([
            'email' => 'supervisor@pos.com',
            'password' => 'password123',
        ]);
        $supervisor->assignRole('Admin'); // Ensure role is assigned
        $supervisor->givePermissionTo('payments.approve_custom_rate'); // Give direct permission as well

        $sale = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'total' => 100,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'applied_exchange_diff_percent' => 0.00,
        ]);

        // Start the test as the operator
        $component = Livewire::actingAs($user)
            ->test(PaymentComponent::class)
            ->call('initPayment', 100, 'USD', $customer->name, true, null, false, 0, 0, true, true, $customer->id, 0, ['sale_id' => $sale->id])
            ->set('paymentCurrency', 'VED')
            ->set('paymentMethod', 'cash')
            ->set('proposedCustomRate', 85.00)
            ->set('customRateReason', 'Cliente prefiere tasa especial acordada.')
            ->call('requestCustomRateApproval');

        // Assert pending request was created in DB
        $this->assertDatabaseHas('exchange_rate_approvals', [
            'sale_id' => $sale->id,
            'user_id' => $user->id,
            'custom_rate' => 85.00,
            'reason' => 'Cliente prefiere tasa especial acordada.',
            'status' => 'pending'
        ]);

        // Local verification by supervisor entering their credentials
        $component->set('supervisorEmail', 'supervisor@pos.com')
            ->set('supervisorPassword', 'password123')
            ->call('approveCustomRateLocally')
            ->assertHasNoErrors()
            ->assertSet('customExchangeRate', 85.00)
            ->assertSet('showCustomRateRequest', false);

        // Fetch the approved request from DB
        $approval = ExchangeRateApproval::where('sale_id', $sale->id)->where('status', 'approved')->first();
        $this->assertNotNull($approval, "Approved request was not created!");
        $this->assertEquals($supervisor->id, $approval->approver_id);

        // Verify that adding a payment consumes the approval or sets used on submit
        $component->set('amount', 7000) // 7000 Bs. at 85.00 rate = 82.35 USD
            ->call('addPayment')
            ->call('submit', 'pay');

        $this->assertDatabaseHas('exchange_rate_approvals', [
            'id' => $approval->id,
            'status' => 'used'
        ]);
    }

    public function test_custom_rate_self_approval_for_authorized_users()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $user = User::factory()->create();
        $customer = Customer::create(['name' => 'John Doe']);

        Permission::firstOrCreate(['name' => 'payments.approve_custom_rate']);
        $user->givePermissionTo('payments.approve_custom_rate');

        $sale = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'total' => 100,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'applied_exchange_diff_percent' => 0.00,
        ]);

        Livewire::actingAs($user)
            ->test(PaymentComponent::class)
            ->call('initPayment', 100, 'USD', $customer->name, true, null, false, 0, 0, true, true, $customer->id, 0, ['sale_id' => $sale->id])
            ->set('paymentCurrency', 'VED')
            ->set('paymentMethod', 'cash')
            ->set('proposedCustomRate', 85.00)
            ->set('customRateReason', 'Auto-aprobado por supervisor.')
            ->call('autoApproveCustomRate')
            ->assertHasNoErrors()
            ->assertSet('customExchangeRate', 85.00)
            ->assertSet('showCustomRateRequest', false);

        $this->assertDatabaseHas('exchange_rate_approvals', [
            'sale_id' => $sale->id,
            'user_id' => $user->id,
            'approver_id' => $user->id,
            'custom_rate' => 85.00,
            'status' => 'approved'
        ]);
    }

    public function test_custom_rate_offline_otp_approval_workflow()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $user = User::factory()->create();
        $customer = Customer::create(['name' => 'John Doe']);

        $sale = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'total' => 100,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'applied_exchange_diff_percent' => 0.00,
        ]);

        $component = Livewire::actingAs($user)
            ->test(PaymentComponent::class)
            ->call('initPayment', 100, 'USD', $customer->name, true, null, false, 0, 0, true, true, $customer->id, 0, ['sale_id' => $sale->id])
            ->set('paymentCurrency', 'VED')
            ->set('paymentMethod', 'cash')
            ->set('proposedCustomRate', 85.00)
            ->set('customRateReason', 'Cliente solicita tasa especial.')
            ->call('requestCustomRateApproval');

        // Retrieve the generated 6-digit OTP code from database
        $approval = ExchangeRateApproval::where('sale_id', $sale->id)->first();
        $otp = $approval->token;
        $this->assertEquals(6, strlen($otp));

        // Submit the OTP code on the operator component
        $component->set('otpCode', $otp)
            ->call('validateOtpCode')
            ->assertHasNoErrors()
            ->assertSet('customExchangeRate', 85.00)
            ->assertSet('showCustomRateRequest', false);

        $this->assertDatabaseHas('exchange_rate_approvals', [
            'id' => $approval->id,
            'status' => 'approved'
        ]);
    }

    public function test_collection_relationship_pdf_separates_approved_and_voided_cash_payments()
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Yeison Montenegro',
            'taxpayer_id' => 'V-11215715',
        ]);

        $sale = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'total' => 160.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_exchange_rate' => 1.00
        ]);

        // Create a voided cash payment
        $voidedPayment = \App\Models\Payment::create([
            'sale_id' => $sale->id,
            'amount' => 80.00,
            'pay_way' => 'cash',
            'currency' => 'USD',
            'exchange_rate' => 1.00,
            'status' => 'voided',
            'payment_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'user_id' => $user->id,
        ]);

        // Create an approved cash payment
        $approvedPayment = \App\Models\Payment::create([
            'sale_id' => $sale->id,
            'amount' => 80.00,
            'pay_way' => 'cash',
            'currency' => 'USD',
            'exchange_rate' => 1.00,
            'status' => 'approved',
            'payment_date' => Carbon::now()->format('Y-m-d H:i:s'),
            'user_id' => $user->id,
        ]);

        $payments = collect([$voidedPayment, $approvedPayment]);

        $config = Configuration::first();

        // Render the view with the template
        $html = view('reports.collection-relationship-new-pdf', [
            'sheet' => new \App\Models\CollectionSheet(['id' => 1, 'sheet_number' => '123']),
            'payments' => $payments,
            'returns' => collect(),
            'config' => $config,
            'user' => $user,
            'date' => Carbon::now()->format('d/m/Y H:i'),
            'totalsByCategory' => [],
            'totalsByCurrency' => [],
            'dateFrom' => null,
            'dateTo' => null
        ])->render();

        // Assert that the HTML contains both the voided description (marked as [ANULADO]) and the approved cash row.
        $this->assertStringContainsString('[ANULADO] CASH', $html);
        $this->assertStringContainsString('CASH (F. Registro:', $html);
        
        // Assert that we have two separate rows (one has voided-row class, one doesn't)
        $this->assertStringContainsString('class=" voided-row"', $html);
        
        // Count how many times the class voided-row appears in tr tags.
        $this->assertEquals(1, substr_count($html, 'class=" voided-row"'));
    }
}
