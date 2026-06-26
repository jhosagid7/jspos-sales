<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Configuration;
use App\Models\SalePaymentDetail;
use App\Livewire\Reports\CashFlowForecastReport;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;

class CashFlowForecastReportTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $unauthorizedUser;
    protected $customer;
    protected $seller;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.installed' => false,
        ]);

        // Create Configuration
        Configuration::create([
            'business_name' => 'Cash Flow Forecast Test Business',
            'taxpayer_id' => 'J-12345678-9',
            'address' => 'Test Address',
            'phone' => '0212-0000000',
            'email' => 'business@test.com',
            'decimals' => 2,
            'bcv_rate' => 50.00,
            'binance_rate' => 70.00,
            'binance_markup_points' => 5.00,
        ]);

        // Create Roles and Permissions
        Permission::firstOrCreate(['name' => 'reports.sales']);

        // Create Admin User
        $this->adminUser = User::factory()->create(['name' => 'Finance Admin']);
        $this->adminUser->givePermissionTo('reports.sales');

        // Create Unauthorized User
        $this->unauthorizedUser = User::factory()->create(['name' => 'Standard User']);

        // Create Seller
        $this->seller = User::factory()->create(['name' => 'Vendedor Test']);

        // Create Customer
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'type' => 'Consumidor Final',
            'taxpayer_id' => 'V12345678',
            'address' => 'Caracas',
            'phone' => '0412-0000000',
            'seller_id' => $this->seller->id,
        ]);
    }

    public function test_cash_flow_forecast_component_renders_for_authorized_user()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CashFlowForecastReport::class)
            ->assertStatus(200)
            ->assertSee('Opciones de Consulta y Filtros');
    }

    public function test_cash_flow_forecast_component_denies_unauthorized_user()
    {
        $this->actingAs($this->unauthorizedUser);

        $response = $this->get(route('reports.cash.flow.forecast'));
        $response->assertStatus(403);
    }

    public function test_cash_flow_forecast_calculates_correct_kpis_and_ageing_buckets()
    {
        $this->actingAs($this->adminUser);

        $today = Carbon::now();
        $dateFrom = $today->copy()->startOfMonth()->format('Y-m-d');
        $dateTo = $today->copy()->endOfMonth()->format('Y-m-d');

        // 1. Credit Sale 1: Overdue by 5 days ($300 total, $100 paid -> $200 debt)
        // credit days = 10, delivered_at = 15 days ago. Due date = 5 days ago.
        $sale1 = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 300.00,
            'total_usd' => 300.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_currency_code' => 'USD',
            'primary_exchange_rate' => 1.00,
            'credit_days' => 10,
            'created_at' => $today->copy()->subDays(15),
            'delivered_at' => $today->copy()->subDays(15)->format('Y-m-d H:i:s'),
            'invoice_number' => 1001
        ]);

        SalePaymentDetail::create([
            'sale_id' => $sale1->id,
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 100.00,
            'exchange_rate' => 1.00,
            'amount_in_primary_currency' => 100.00,
            'created_at' => $today->copy()->subDays(15),
        ]);

        // 2. Credit Sale 2: Corriente / Por Vence in 5 days ($500 debt)
        // credit days = 10, delivered_at = 5 days ago. Due date = 5 days from now.
        $sale2 = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 500.00,
            'total_usd' => 500.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_currency_code' => 'USD',
            'primary_exchange_rate' => 1.00,
            'credit_days' => 10,
            'created_at' => $today->copy()->subDays(5),
            'delivered_at' => $today->copy()->subDays(5)->format('Y-m-d H:i:s'),
            'invoice_number' => 1002
        ]);

        // 3. Credit Sale 3: Overdue by 25 days ($400 debt)
        // credit days = 5, delivered_at = 30 days ago. Due date = 25 days ago.
        $sale3 = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 400.00,
            'total_usd' => 400.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_currency_code' => 'USD',
            'primary_exchange_rate' => 1.00,
            'credit_days' => 5,
            'created_at' => $today->copy()->subDays(30),
            'delivered_at' => $today->copy()->subDays(30)->format('Y-m-d H:i:s'),
            'invoice_number' => 1003
        ]);

        // 4. Create subsequent payment in the period to test cobrado ($100)
        Payment::create([
            'user_id' => $this->adminUser->id,
            'sale_id' => $sale1->id,
            'amount' => 100.00,
            'currency' => 'USD',
            'exchange_rate' => 1.00,
            'pay_way' => 'cash',
            'type' => 'pay',
            'payment_date' => $today->format('Y-m-d'),
            'status' => 'approved',
            'created_at' => $today
        ]);

        // Expected outstanding debts:
        // sale1: 300 - 100 (initial) - 100 (subsequent) = 100 USD (Overdue by 5 days -> vencido_1_7)
        // sale2: 500 - 0 = 500 USD (Current, 5 days remaining -> corriente_1_7)
        // sale3: 400 - 0 = 400 USD (Overdue by 25 days -> vencido_critico)
        // Total Outstanding Debt = 1000 USD
        // Overdue Debt = 100 + 400 = 500 USD
        // Current Debt = 500 USD
        // Total Collected = 100 USD (subsequent payment)
        // CEI = 100 / (100 + 500) * 100 = 16.67%
        // DSO = ((100 * 5) + (400 * 25)) / 500 = (500 + 10000) / 500 = 10500 / 500 = 21.0 days

        Livewire::test(CashFlowForecastReport::class)
            ->set('dateFrom', $dateFrom)
            ->set('dateTo', $dateTo)
            ->call('searchData')
            ->assertSet('showReport', true)
            ->assertViewHas('metrics', function ($metrics) {
                $buckets = $metrics['buckets'];

                // Expected outstanding debts:
                // sale1: 300 - 100 (initial) - 100 (subsequent) = 100 USD (Overdue by 5 days)
                // sale2: 500
                // sale3: 400
                // Total debt = 1000
                $this->assertEquals(1000.0, $metrics['totalDebt']);
                $this->assertEquals(500.0, $metrics['currentDebt']);
                $this->assertEquals(500.0, $metrics['overdueDebt']);
                $this->assertEquals(200.0, $metrics['totalCollected']);

                // CEI: 200 / (200 + 500) * 100 = 28.57%
                $this->assertEquals(200 / (200 + 500) * 100, $metrics['cei']);

                // DSO: ((100 * 5) + (400 * 25)) / 500 = 21.0
                $this->assertEquals(21.0, $metrics['dso']);

                // Buckets
                $this->assertEquals(400.0, $buckets['vencido_critico']);
                $this->assertEquals(0.0, $buckets['vencido_8_15']);
                $this->assertEquals(100.0, $buckets['vencido_1_7']);
                $this->assertEquals(500.0, $buckets['corriente_1_7']);
                $this->assertEquals(0.0, $buckets['corriente_8_14']);
                $this->assertEquals(0.0, $buckets['corriente_largo']);

                return true;
            });
    }

    public function test_cash_flow_forecast_pdf_endpoint()
    {
        $this->actingAs($this->adminUser);

        $today = Carbon::now();
        $dateFrom = $today->copy()->startOfMonth()->format('Y-m-d');
        $dateTo = $today->copy()->endOfMonth()->format('Y-m-d');

        // Create a basic credit sale to ensure there's data
        Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_currency_code' => 'USD',
            'primary_exchange_rate' => 1.00,
            'credit_days' => 15,
            'created_at' => $today->copy()->subDays(5),
            'delivered_at' => $today->copy()->subDays(5)->format('Y-m-d H:i:s'),
            'invoice_number' => 1004
        ]);

        $params = [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->seller->id,
        ];

        $response = $this->get(route('reports.cash.flow.forecast.pdf', $params));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_cash_flow_forecast_pdf_endpoint_with_filtered_bucket()
    {
        $this->actingAs($this->adminUser);

        $today = Carbon::now();
        $dateFrom = $today->copy()->startOfMonth()->format('Y-m-d');
        $dateTo = $today->copy()->endOfMonth()->format('Y-m-d');

        // Create a basic credit sale to ensure there's data
        Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_currency_code' => 'USD',
            'primary_exchange_rate' => 1.00,
            'credit_days' => 15,
            'created_at' => $today->copy()->subDays(5),
            'delivered_at' => $today->copy()->subDays(5)->format('Y-m-d H:i:s'),
            'invoice_number' => 1004
        ]);

        $params = [
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->seller->id,
            'selectedBucket' => 'vencido_critico'
        ];

        $response = $this->get(route('reports.cash.flow.forecast.pdf', $params));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_cash_flow_forecast_toggles_interpretation_modal()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CashFlowForecastReport::class)
            ->assertSet('showInterpretationModal', false)
            ->call('toggleInterpretationModal')
            ->assertSet('showInterpretationModal', true)
            ->call('toggleInterpretationModal')
            ->assertSet('showInterpretationModal', false);
    }

    public function test_cash_flow_forecast_filters_by_selected_bucket()
    {
        $this->actingAs($this->adminUser);

        $today = Carbon::now();
        $dateFrom = $today->copy()->startOfMonth()->format('Y-m-d');
        $dateTo = $today->copy()->endOfMonth()->format('Y-m-d');

        // Create Sale in vencido_critico
        Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 300.00,
            'total_usd' => 300.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_currency_code' => 'USD',
            'primary_exchange_rate' => 1.00,
            'credit_days' => 5,
            'created_at' => $today->copy()->subDays(30),
            'delivered_at' => $today->copy()->subDays(30)->format('Y-m-d H:i:s'),
            'invoice_number' => 1005
        ]);

        // Create Sale in corriente_1_7
        Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'total' => 500.00,
            'total_usd' => 500.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'primary_currency_code' => 'USD',
            'primary_exchange_rate' => 1.00,
            'credit_days' => 10,
            'created_at' => $today->copy()->subDays(5),
            'delivered_at' => $today->copy()->subDays(5)->format('Y-m-d H:i:s'),
            'invoice_number' => 1006
        ]);

        Livewire::test(CashFlowForecastReport::class)
            ->set('dateFrom', $dateFrom)
            ->set('dateTo', $dateTo)
            ->call('searchData')
            ->assertSet('selectedBucket', 'all')
            ->assertViewHas('sales', function ($sales) {
                return $sales->count() === 2;
            })
            // Select vencido_critico
            ->call('selectBucket', 'vencido_critico')
            ->assertSet('selectedBucket', 'vencido_critico')
            ->assertViewHas('sales', function ($sales) {
                return $sales->count() === 1 && $sales->first()['bucket'] === 'vencido_critico';
            })
            // Toggle / deselect bucket
            ->call('selectBucket', 'vencido_critico')
            ->assertSet('selectedBucket', 'all')
            ->assertViewHas('sales', function ($sales) {
                return $sales->count() === 2;
            });
    }
}

