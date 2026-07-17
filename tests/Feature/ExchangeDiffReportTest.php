<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\SalePaymentDetail;
use App\Models\Payment;
use App\Models\ExchangeRateHistory;
use App\Livewire\Reports\ExchangeDiffReport;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;

class ExchangeDiffReportTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $unauthorizedUser;
    protected $customer;
    protected $sale;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.installed' => false,
        ]);

        // Create Configuration
        Configuration::create([
            'business_name' => 'Exchange Audit Test Business',
            'taxpayer_id' => 'J-12345678-9',
            'address' => 'Test Address',
            'phone' => '0212-0000000',
            'email' => 'business@test.com',
            'decimals' => 2,
            'bcv_rate' => 50.00,
            'binance_rate' => 70.00,
            'binance_markup_points' => 5.00,
        ]);

        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Create users
        $this->adminUser = User::factory()->create(['name' => 'Finance Admin']);
        Permission::firstOrCreate(['name' => 'reports.sales']);
        $this->adminUser->givePermissionTo('reports.sales');

        $this->unauthorizedUser = User::factory()->create(['name' => 'Standard User']);

        // Create Customer
        $this->customer = Customer::create([
            'name' => 'Exchange Customer',
            'type' => 'Consumidor Final',
            'taxpayer_id' => 'V12345678',
            'address' => 'Caracas',
            'phone' => '0412-0000000',
        ]);
    }

    public function test_exchange_diff_report_component_renders_for_authorized_user()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ExchangeDiffReport::class)
            ->assertStatus(200)
            ->assertSee('Opciones de Análisis y Filtros');
    }

    public function test_exchange_diff_report_component_denies_unauthorized_user()
    {
        $this->actingAs($this->unauthorizedUser);

        // Component should return 403 since it has middleware can:reports.sales or is checked in layout
        // Let's verify by requesting the route directly
        $response = $this->get(route('reports.exchange.diff'));
        $response->assertStatus(403);
    }

    public function test_exchange_diff_report_calculates_correct_kpis_and_differentials()
    {
        $this->actingAs($this->adminUser);

        $today = Carbon::now()->format('Y-m-d');

        // Create historical rates for today
        ExchangeRateHistory::create([
            'rate_type' => 'BinanceReal',
            'rate' => 52.00,
            'user_id' => $this->adminUser->id,
            'created_at' => Carbon::now()->startOfDay()->addHours(8) // 8 AM
        ]);

        // Create Sale with $100 total, agreement BCV, and $8 surcharge
        $sale = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 108.00,
            'total_usd' => 108.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_currency_code' => 'USD',
            'primary_exchange_rate' => 50.00,
            'payment_agreement' => 'BCV',
            'exchange_diff_amount' => 8.00,
            'invoice_number' => 1234
        ]);

        // 1. Initial payment (checkout) - 2,000 VED at rate 50.00 (expected $40 USD)
        SalePaymentDetail::create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'currency_code' => 'VED',
            'amount' => 2000.00,
            'exchange_rate' => 50.00,
            'amount_in_primary_currency' => 40.00
        ]);

        // 2. Subsequent payment - 3,300 VED at rate 55.00 (expected $60 USD)
        Payment::create([
            'user_id' => $this->adminUser->id,
            'sale_id' => $sale->id,
            'amount' => 3300.00,
            'currency' => 'VED',
            'exchange_rate' => 55.00,
            'pay_way' => 'cash',
            'type' => 'pay',
            'payment_date' => $today,
            'status' => 'approved'
        ]);

        Livewire::test(ExchangeDiffReport::class)
            ->set('dateFrom', $today)
            ->set('dateTo', $today)
            ->call('searchData')
            ->assertSet('showReport', true)
            ->assertViewHas('payments', function ($payments) {
                return count($payments) === 2;
            })
            ->assertViewHas('kpis', function ($kpis) {
                return abs($kpis['totalCreditedUSD'] - 100.00) < 0.01
                    && abs($kpis['totalRealUSD'] - 101.92) < 0.05
                    && abs($kpis['netExchangeDifferenceUSD'] - 1.92) < 0.05
                    && abs($kpis['totalSurchargesBilledUSD'] - 8.00) < 0.01
                    && abs($kpis['netCambiaryResultUSD'] - 9.92) < 0.05;
            });
    }

    public function test_exchange_diff_report_pdf_generation_endpoint()
    {
        $this->actingAs($this->adminUser);

        $today = Carbon::now()->format('Y-m-d');
        $now = Carbon::now();
        Carbon::setTestNow($now);

        // Create Sale with VED payment
        $sale = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_currency_code' => 'USD',
            'primary_exchange_rate' => 50.00,
            'invoice_number' => 1234
        ]);

        SalePaymentDetail::create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'currency_code' => 'VED',
            'amount' => 1000.00,
            'exchange_rate' => 50.00,
            'amount_in_primary_currency' => 20.00
        ]);

        $expectedFilename = 'Auditoria_Diferencial_Cambiario_' . $now->format('YmdHis') . '.pdf';

        Livewire::test(ExchangeDiffReport::class)
            ->set('dateFrom', $today)
            ->set('dateTo', $today)
            ->set('showReport', true)
            ->call('generatePdf')
            ->assertFileDownloaded($expectedFilename);

        Carbon::setTestNow(); // Reset Carbon mock time
    }

    public function test_exchange_diff_report_toggles_interpretation_modal_and_generates_analysis()
    {
        $this->actingAs($this->adminUser);

        $today = Carbon::now()->format('Y-m-d');

        // Create historical rate for today
        ExchangeRateHistory::create([
            'rate_type' => 'BinanceReal',
            'rate' => 50.00,
            'user_id' => $this->adminUser->id,
            'created_at' => Carbon::now()->startOfDay()->addHours(8)
        ]);

        // Create Sale with VED payment
        $sale = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_currency_code' => 'USD',
            'primary_exchange_rate' => 50.00,
            'invoice_number' => 1234
        ]);

        SalePaymentDetail::create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'currency_code' => 'VED',
            'amount' => 1000.00,
            'exchange_rate' => 50.00,
            'amount_in_primary_currency' => 20.00
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(ExchangeDiffReport::class)
            ->assertSet('showInterpretationModal', false)
            ->call('toggleInterpretationModal')
            ->assertSet('showInterpretationModal', true)
            ->call('toggleInterpretationModal')
            ->assertSet('showInterpretationModal', false);

        // Check text generation without customer when report is not loaded
        $component = Livewire::actingAs($this->adminUser)->test(ExchangeDiffReport::class);
        $this->assertEquals('', $component->instance()->getInterpretation());

        // Check text generation when report is loaded
        $component = Livewire::actingAs($this->adminUser)
            ->test(ExchangeDiffReport::class)
            ->set('dateFrom', $today)
            ->set('dateTo', $today)
            ->call('searchData');

        $htmlGeneral = $component->instance()->getInterpretation();
        $this->assertStringContainsString('Análisis Cambiario de Caja General', $htmlGeneral);
        $this->assertStringContainsString('Flujo Cambiario General', $htmlGeneral);
        $this->assertStringContainsString('Monto Facturado (USD)', $htmlGeneral);
        $this->assertStringContainsString('Abonos Descontados (USD)', $htmlGeneral);

        // Check text generation with specific customer
        $componentWithCustomer = Livewire::actingAs($this->adminUser)
            ->test(ExchangeDiffReport::class)
            ->set('dateFrom', $today)
            ->set('dateTo', $today)
            ->set('customer_id', $this->customer->id)
            ->call('searchData');

        $htmlCustomer = $componentWithCustomer->instance()->getInterpretation();
        $this->assertStringContainsString('Análisis Cambiario del Cliente: Exchange Customer', $htmlCustomer);
    }

    public function test_exchange_diff_report_option_a_net_cushion_evaluation()
    {
        $this->actingAs($this->adminUser);
        $today = Carbon::now()->format('Y-m-d');

        ExchangeRateHistory::create([
            'rate_type' => 'BinanceReal',
            'rate' => 60.00,
            'user_id' => $this->adminUser->id,
            'created_at' => Carbon::now()->startOfDay()->addHours(8)
        ]);

        // Sale 1: BCV agreement with sufficient surcharge cushion ($20 surcharge on $120 total)
        $saleSufficient = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 120.00,
            'total_usd' => 120.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_currency_code' => 'USD',
            'primary_exchange_rate' => 50.00,
            'payment_agreement' => 'BCV',
            'exchange_diff_amount' => 20.00,
            'invoice_number' => 2001
        ]);

        // Payment for Sale 1: pays full 6000 VED at rate 50.00 ($120 credited). Real USD = 6000 / 60 = $100.
        // Direct diff = -20. Surcharge portion = 20. Net diff = 0. Should be green / Cojín Eficiente.
        SalePaymentDetail::create([
            'sale_id' => $saleSufficient->id,
            'payment_method' => 'cash',
            'currency_code' => 'VED',
            'amount' => 6000.00,
            'exchange_rate' => 50.00,
            'amount_in_primary_currency' => 120.00
        ]);

        // Sale 2: BCV agreement with insufficient surcharge cushion ($10 surcharge on $120 total)
        $saleInsufficient = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 120.00,
            'total_usd' => 120.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_currency_code' => 'USD',
            'primary_exchange_rate' => 50.00,
            'payment_agreement' => 'BCV',
            'exchange_diff_amount' => 10.00,
            'invoice_number' => 2002
        ]);

        // Payment for Sale 2: pays full 6000 VED at rate 50.00 ($120 credited). Real USD = 6000 / 60 = $100.
        // Direct diff = -20. Surcharge portion = 10. Net diff = -10. Should be red / Fuga Real (Cojín Insuficiente).
        SalePaymentDetail::create([
            'sale_id' => $saleInsufficient->id,
            'payment_method' => 'cash',
            'currency_code' => 'VED',
            'amount' => 6000.00,
            'exchange_rate' => 50.00,
            'amount_in_primary_currency' => 120.00
        ]);

        Livewire::test(ExchangeDiffReport::class)
            ->set('dateFrom', $today)
            ->set('dateTo', $today)
            ->call('searchData')
            ->assertViewHas('payments', function ($payments) {
                if (count($payments) !== 2) return false;
                $p1 = collect($payments->items())->firstWhere('invoice_number', 2001);
                $p2 = collect($payments->items())->firstWhere('invoice_number', 2002);
                return $p1['status'] === 'green'
                    && $p1['msg'] === 'Cojín Eficiente'
                    && abs($p1['net_diff'] - 0.0) < 0.01
                    && $p2['status'] === 'red'
                    && $p2['msg'] === 'Fuga Real (Cojín Insuficiente)'
                    && abs($p2['net_diff'] - (-10.0)) < 0.01;
            });
    }
}
