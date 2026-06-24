<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Livewire\Reports\SalesAnalysisReport;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class SalesAnalysisReportTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $unauthorizedUser;
    protected $seller;
    protected $customer;

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

        // Create a Seller
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'system.is_foreign_seller']);
        $vendedorRole = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Vendedor Foraneo']);
        $vendedorRole->givePermissionTo('system.is_foreign_seller');
        
        $this->seller = User::factory()->create(['name' => 'Vendedor Test']);
        $this->seller->assignRole('Vendedor Foraneo');

        // Create OFICINA seller just in case fallback logic is hit
        User::factory()->create(['name' => 'OFICINA', 'email' => 'oficina@gmail.com']);

        // Create a Customer assigned to this seller
        $this->customer = Customer::create([
            'name' => 'Cliente Test',
            'taxpayer_id' => '12345',
            'address' => 'Test Address',
            'city' => 'Test City',
            'seller_id' => $this->seller->id,
            'type' => 'Consumidor Final',
        ]);
    }

    public function test_sales_analysis_report_component_renders_for_authorized_user()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.sales.analysis'));
        $response->assertStatus(200);
        $response->assertSeeLivewire(SalesAnalysisReport::class);
    }

    public function test_sales_analysis_report_component_denies_unauthorized_user()
    {
        $this->actingAs($this->unauthorizedUser);

        $response = $this->get(route('reports.sales.analysis'));
        $response->assertStatus(403);
    }

    public function test_sales_analysis_report_calculates_kpis_and_growth_correctly()
    {
        $this->actingAs($this->adminUser);

        // Create sale in previous period (e.g. 8 days ago)
        $prevSaleDate = Carbon::now()->subDays(8);
        $prevSale = Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->adminUser->id,
            'total' => 100,
            'total_usd' => 100,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => $prevSaleDate,
            'final_commission_amount' => 10
        ]);

        // Create sale in current period (today)
        $currentSale = Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->adminUser->id,
            'total' => 150,
            'total_usd' => 150,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now(),
            'final_commission_amount' => 15
        ]);

        // Test Livewire Component
        Livewire::test(SalesAnalysisReport::class)
            ->set('dateFrom', Carbon::now()->subDays(5)->format('Y-m-d'))
            ->set('dateTo', Carbon::now()->format('Y-m-d'))
            ->set('periodType', 'daily')
            ->set('metric', 'amount')
            ->call('searchData')
            ->assertSet('showReport', true)
            ->assertViewHas('reportData', function ($data) {
                // Assert KPIs are calculated correctly
                $kpis = $data['kpis'];
                $this->assertEquals(150, $kpis['total_sales']);
                $this->assertEquals(1, $kpis['sales_count']);
                $this->assertEquals(15, $kpis['total_commission']);
                $this->assertEquals(135, $kpis['net_sales']);
                $this->assertEquals(150, $kpis['avg_ticket']);
                // Previous period had 100, so growth = ((150 - 100) / 100) * 100 = 50%
                $this->assertEquals(50, $kpis['growth_percent']);
                return true;
            });
    }

    public function test_sales_analysis_pdf_generation_endpoint()
    {
        $this->actingAs($this->adminUser);

        // Create a test sale
        Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->adminUser->id,
            'total' => 200,
            'total_usd' => 200,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now()
        ]);

        $params = [
            'selectedSellers' => $this->seller->id,
            'periodType' => 'monthly',
            'dateFrom' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'dateTo' => Carbon::now()->endOfMonth()->format('Y-m-d'),
            'metric' => 'amount'
        ];

        $response = $this->get(route('reports.sales.analysis.pdf', $params));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
