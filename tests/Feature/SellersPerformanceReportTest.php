<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Livewire\Reports\SellersPerformanceReport;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class SellersPerformanceReportTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $unauthorizedUser;
    protected $seller1;
    protected $seller2;
    protected $customer1;
    protected $customer2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Configuration
        Configuration::create([
            'business_name' => 'JSPOS Sales Test',
            'taxpayer_id' => 'V-12345678-9',
            'address' => 'Main Street 123',
            'phone' => '1234567',
        ]);

        // Create Admin user with permission
        $this->adminUser = User::factory()->create(['name' => 'Admin User']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'reports.sales']);
        $this->adminUser->givePermissionTo('reports.sales');

        // Create Unauthorized user
        $this->unauthorizedUser = User::factory()->create(['name' => 'Unauthorized User']);

        // Create Vendedores
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'system.is_foreign_seller']);
        $vendedorRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Vendedor Foraneo']);
        $vendedorRole->givePermissionTo('system.is_foreign_seller');
        
        $this->seller1 = User::factory()->create(['name' => 'Vendedor A']);
        $this->seller1->assignRole('Vendedor Foraneo');

        $this->seller2 = User::factory()->create(['name' => 'Vendedor B']);
        $this->seller2->assignRole('Vendedor Foraneo');

        // Create Customers assigned to sellers
        $this->customer1 = Customer::create([
            'name' => 'Cliente Alfa',
            'taxpayer_id' => '111',
            'address' => 'Test Address A',
            'city' => 'Test City A',
            'seller_id' => $this->seller1->id,
            'type' => 'Consumidor Final',
        ]);

        $this->customer2 = Customer::create([
            'name' => 'Cliente Beta',
            'taxpayer_id' => '222',
            'address' => 'Test Address B',
            'city' => 'Test City B',
            'seller_id' => $this->seller2->id,
            'type' => 'Consumidor Final',
        ]);

        // Enable advanced reports module
        config(['tenant.modules' => ['module_advanced_reports']]);
    }

    public function test_sellers_performance_report_component_renders_for_authorized_user()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.sellers.performance'));
        $response->assertStatus(200);
        $response->assertSeeLivewire(SellersPerformanceReport::class);
    }

    public function test_sellers_performance_report_component_denies_unauthorized_user()
    {
        $this->actingAs($this->unauthorizedUser);

        $response = $this->get(route('reports.sellers.performance'));
        $response->assertStatus(403);
    }

    public function test_sellers_performance_report_calculates_kpis_and_debt_correctly()
    {
        $this->actingAs($this->adminUser);

        // 1. Create a sale for Customer 1 (Seller 1)
        // Let's make it a credit sale of 200 USD with 20 USD commission
        $sale1 = Sale::create([
            'customer_id' => $this->customer1->id,
            'user_id' => $this->adminUser->id,
            'total' => 200,
            'total_usd' => 200,
            'items' => 2,
            'status' => 'pending',
            'type' => 'credit',
            'created_at' => Carbon::now(),
            'final_commission_amount' => 20,
            'credit_days' => 15,
        ]);

        // Add a subsequent payment of 50 USD on sale1 to test debt calculation
        $payment = \App\Models\Payment::create([
            'sale_id' => $sale1->id,
            'user_id' => $this->adminUser->id,
            'amount' => 50,
            'currency_code' => 'USD',
            'exchange_rate' => 1,
            'status' => 'approved',
            'payment_method' => 'cash',
        ]);

        // 2. Create a sale for Customer 2 (Seller 2)
        // Let's make it a cash sale of 150 USD with 15 USD commission
        $sale2 = Sale::create([
            'customer_id' => $this->customer2->id,
            'user_id' => $this->adminUser->id,
            'total' => 150,
            'total_usd' => 150,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now(),
            'final_commission_amount' => 15,
        ]);

        // Test Livewire Component calculations
        Livewire::test(SellersPerformanceReport::class)
            ->set('dateFrom', Carbon::now()->subDays(5)->format('Y-m-d'))
            ->set('dateTo', Carbon::now()->format('Y-m-d'))
            ->set('periodType', 'monthly')
            ->set('metric', 'amount')
            ->call('searchData')
            ->assertSet('showReport', true)
            ->assertViewHas('reportData', function ($data) {
                $kpis = $data['kpis'];
                $sellers = $data['sellers'];

                // Overall KPIs
                $this->assertEquals(350, $kpis['total_sales']);
                $this->assertEquals(35, $kpis['total_commission']);
                $this->assertEquals(315, $kpis['net_sales']);
                $this->assertEquals(90.0, $kpis['margin_percent']);
                // Debt: sale1 has 200 total - 50 paid = 150 debt. sale2 has 0 debt.
                $this->assertEquals(150, $kpis['total_debt']);

                // Seller 1 breakdown
                $s1 = collect($sellers)->firstWhere('name', 'Vendedor A');
                $this->assertNotNull($s1);
                $this->assertEquals(200, $s1['gross_sales']);
                $this->assertEquals(20, $s1['commissions']);
                $this->assertEquals(180, $s1['net_sales']);
                $this->assertEquals(150, $s1['pending_debt']);
                $this->assertEquals(1, $s1['active_customers']);

                // Seller 2 breakdown
                $s2 = collect($sellers)->firstWhere('name', 'Vendedor B');
                $this->assertNotNull($s2);
                $this->assertEquals(150, $s2['gross_sales']);
                $this->assertEquals(15, $s2['commissions']);
                $this->assertEquals(135, $s2['net_sales']);
                $this->assertEquals(0, $s2['pending_debt']);
                $this->assertEquals(1, $s2['active_customers']);

                return true;
            });
    }

    public function test_sellers_performance_pdf_generation_endpoint()
    {
        $this->actingAs($this->adminUser);

        // Create a test sale
        Sale::create([
            'customer_id' => $this->customer1->id,
            'user_id' => $this->adminUser->id,
            'total' => 200,
            'total_usd' => 200,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now()
        ]);

        $params = [
            'selectedSellers' => $this->seller1->id . ',' . $this->seller2->id,
            'periodType' => 'monthly',
            'dateFrom' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'dateTo' => Carbon::now()->endOfMonth()->format('Y-m-d'),
            'metric' => 'amount'
        ];

        $response = $this->get(route('reports.sellers.performance.pdf', $params));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
