<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Models\SaleHistoryLog;
use App\Models\SaleReturn;
use App\Livewire\Reports\BillingOperatorsReport;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class BillingOperatorsReportTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $unauthorizedUser;
    protected $operator1;
    protected $operator2;
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

        // Create Operators (Users)
        $this->operator1 = User::factory()->create(['name' => 'Operador A']);
        $this->operator2 = User::factory()->create(['name' => 'Operador B']);

        // Create Customer
        $this->customer = Customer::create([
            'name' => 'Cliente Test',
            'taxpayer_id' => '999',
            'address' => 'Test Address',
            'city' => 'Test City',
            'type' => 'Consumidor Final',
        ]);

        // Enable advanced reports module
        config(['tenant.modules' => ['module_advanced_reports']]);
    }

    public function test_billing_operators_report_component_renders_for_authorized_user()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.operators.precision'));
        $response->assertStatus(200);
        $response->assertSeeLivewire(BillingOperatorsReport::class);
    }

    public function test_billing_operators_report_component_denies_unauthorized_user()
    {
        $this->actingAs($this->unauthorizedUser);

        $response = $this->get(route('reports.operators.precision'));
        $response->assertStatus(403);
    }

    public function test_billing_operators_report_computes_precision_and_efficiency_correctly()
    {
        $this->actingAs($this->adminUser);

        // Operator A: 2 sales on 2 different days. No errors.
        // Sale 1: Today
        Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'total' => 100,
            'total_usd' => 100,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now(),
        ]);
        // Sale 2: Yesterday
        Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'total' => 120,
            'total_usd' => 120,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now()->subDay(),
        ]);

        // Operator B: 10 sales in total (let's put them on today to test single active day)
        // 7 clean sales
        for ($i = 0; $i < 7; $i++) {
            Sale::create([
                'customer_id' => $this->customer->id,
                'user_id' => $this->operator2->id,
                'total' => 50,
                'total_usd' => 50,
                'items' => 1,
                'status' => 'paid',
                'type' => 'cash',
                'created_at' => Carbon::now(),
            ]);
        }

        // 1 modified sale
        $modifiedSale = Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator2->id,
            'total' => 80,
            'total_usd' => 80,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now(),
        ]);
        SaleHistoryLog::create([
            'sale_id' => $modifiedSale->id,
            'user_id' => $this->adminUser->id,
            'old_data' => [],
            'new_data' => [],
            'reason' => 'Corrige precio',
        ]);

        // 1 voided sale
        Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator2->id,
            'total' => 90,
            'total_usd' => 90,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now(),
            'deletion_approved_at' => Carbon::now(),
        ]);

        // 1 sale with return
        $returnedSale = Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator2->id,
            'total' => 110,
            'total_usd' => 110,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now(),
        ]);
        SaleReturn::create([
            'sale_id' => $returnedSale->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator2->id,
            'total_returned' => 110,
            'status' => 'approved',
            'return_type' => 'full',
            'refund_method' => 'cash',
            'return_number' => 'RET-001',
        ]);

        // Test Livewire component calculations
        Livewire::test(BillingOperatorsReport::class)
            ->set('dateFrom', Carbon::now()->subDays(5)->format('Y-m-d'))
            ->set('dateTo', Carbon::now()->format('Y-m-d'))
            ->set('periodType', 'monthly')
            ->set('metric', 'precision_score')
            ->call('searchData')
            ->assertSet('showReport', true)
            ->assertViewHas('reportData', function ($data) {
                $kpis = $data['kpis'];
                $operators = $data['operators'];

                // Overall KPIs: 2 from Op A + 10 from Op B = 12 total
                // Voided = 1. Modified = 1. Returned = 1. Errors = 3.
                $this->assertEquals(12, $kpis['total_sales']);
                $this->assertEquals(3, $kpis['total_errors']);
                $this->assertEquals(1, $kpis['total_voided']);
                $this->assertEquals(1, $kpis['total_modified']);
                $this->assertEquals(1, $kpis['total_returned']);

                // Ponderated average score:
                // Penalty = 1*1.5 + 1 + 1*1.2 = 3.7
                // Score = 100 - (3.7 / 12 * 100) = 100 - 30.833 = 69.17%
                $this->assertEquals(69.17, $kpis['avg_precision_score']);

                // Operator A breakdown
                $opA = collect($operators)->firstWhere('name', 'Operador A');
                $this->assertNotNull($opA);
                $this->assertEquals(2, $opA['total_sales']);
                $this->assertEquals(2, $opA['active_days']);
                $this->assertEquals(1.0, $opA['efficiency']); // 2 sales / 2 active days = 1.0
                $this->assertEquals(100.0, $opA['precision_score']); // 0 errors

                // Operator B breakdown
                $opB = collect($operators)->firstWhere('name', 'Operador B');
                $this->assertNotNull($opB);
                $this->assertEquals(10, $opB['total_sales']);
                $this->assertEquals(1, $opB['active_days']);
                $this->assertEquals(10.0, $opB['efficiency']); // 10 sales / 1 active day = 10.0
                $this->assertEquals(63.0, $opB['precision_score']); // 100 - (3.7/10*100) = 63.0%

                return true;
            });
    }

    public function test_billing_operators_pdf_generation_endpoint()
    {
        $this->actingAs($this->adminUser);

        // Create a test sale
        Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'total' => 100,
            'total_usd' => 100,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now()
        ]);

        $params = [
            'selectedOperators' => $this->operator1->id . ',' . $this->operator2->id,
            'periodType' => 'monthly',
            'dateFrom' => Carbon::now()->startOfMonth()->format('Y-m-d'),
            'dateTo' => Carbon::now()->endOfMonth()->format('Y-m-d'),
            'metric' => 'precision_score'
        ];

        $response = $this->get(route('reports.operators.precision.pdf', $params));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
