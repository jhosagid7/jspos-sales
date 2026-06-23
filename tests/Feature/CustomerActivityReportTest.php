<?php
 
namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Configuration;
use Livewire\Livewire;
use App\Livewire\Reports\CustomerActivityReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerActivityReportTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $customer1;
    protected $customer2;

    protected function setUp(): void
    {
        parent::setUp();

        Configuration::create([
            'business_name' => 'JSPOS Sales Test',
            'taxpayer_id' => 'V-12345678-9',
            'address' => 'Main Street 123',
            'phone' => '1234567',
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->givePermissionTo('reports.sales');

        $this->customer1 = Customer::create([
            'name' => 'Cliente Alfa',
            'taxpayer_id' => '111',
            'address' => 'Direccion A',
            'city' => 'Caracas',
            'type' => 'Consumidor Final',
        ]);

        $this->customer2 = Customer::create([
            'name' => 'Cliente Beta',
            'taxpayer_id' => '222',
            'address' => 'Direccion B',
            'city' => 'Valencia',
            'type' => 'Consumidor Final',
        ]);
    }

    public function test_customer_activity_report_component_renders_for_authorized_user()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CustomerActivityReport::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.reports.customer-activity-report')
            ->assertSee('Análisis de Compras del Cliente');
    }

    public function test_customer_activity_report_component_dispatches_chart_events()
    {
        $this->actingAs($this->adminUser);

        // Create a sale for customer1
        \App\Models\Sale::create([
            'total' => 150,
            'total_usd' => 150,
            'items' => 2,
            'customer_id' => $this->customer1->id,
            'user_id' => $this->adminUser->id,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => \Carbon\Carbon::now(),
        ]);

        Livewire::test(CustomerActivityReport::class)
            ->set('selectedCustomers', [$this->customer1->id, $this->customer2->id])
            ->set('periodType', 'monthly')
            ->call('searchData')
            ->assertSet('showReport', true)
            ->assertDispatched('updateChart');
    }

    public function test_customer_activity_pdf_returns_200_and_pdf_type()
    {
        $this->actingAs($this->adminUser);

        // Create a sale for customer 1
        \App\Models\Sale::create([
            'total' => 200,
            'total_usd' => 200,
            'items' => 3,
            'customer_id' => $this->customer1->id,
            'user_id' => $this->adminUser->id,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => \Carbon\Carbon::now(),
        ]);

        $response = $this->get(route('reports.customer.activity.pdf', [
            'selectedCustomers' => $this->customer1->id . ',' . $this->customer2->id,
            'periodType' => 'monthly',
            'dateFrom' => \Carbon\Carbon::now()->startOfYear()->format('Y-m-d'),
            'dateTo' => \Carbon\Carbon::now()->format('Y-m-d'),
            'metric' => 'amount',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_customer_activity_pdf_view_renders_correctly()
    {
        $this->actingAs($this->adminUser);

        $labels = ['2026-05', '2026-06'];
        $datasets = [
            [
                'label' => 'Cliente Alfa',
                'data' => [100.0, 150.0]
            ],
            [
                'label' => 'Cliente Beta',
                'data' => [0.0, 200.0]
            ]
        ];

        $kpis = [
            $this->customer1->id => [
                'name' => 'Cliente Alfa',
                'total_amount' => 250.0,
                'sales_count' => 2,
                'avg_ticket' => 125.0,
                'last_purchase_at' => '20/06/2026',
                'top_products' => [
                    (object)[
                        'product_name' => 'Producto Estrella',
                        'total_qty' => 10,
                        'total_usd' => 100,
                    ]
                ],
            ],
            $this->customer2->id => [
                'name' => 'Cliente Beta',
                'total_amount' => 200.0,
                'sales_count' => 1,
                'avg_ticket' => 200.0,
                'last_purchase_at' => '23/06/2026',
                'top_products' => [],
            ]
        ];

        $detailedSales = collect([
            (object)[
                'invoice_number' => 'F0001',
                'created_at' => '2026-06-23 10:00:00',
                'customer' => $this->customer2,
                'base_amount' => 200.0,
                'total_freight' => 0.0,
                'total_usd' => 200.0,
                'status' => 'paid'
            ]
        ]);

        $config = Configuration::first();
        $user = $this->adminUser;
        $date = '23/06/2026 11:00';
        $dateFromStr = '2026-01-01';
        $dateToStr = '2026-06-23';
        $metric = 'amount';
        $periodType = 'monthly';

        $view = $this->view('reports.customer-activity-pdf', compact(
            'labels', 'datasets', 'kpis', 'detailedSales', 'metric', 'periodType', 
            'config', 'user', 'date', 'dateFromStr', 'dateToStr'
        ));

        $view->assertSee('Reporte de Actividad y Análisis de Compras');
        $view->assertSee('Cliente Alfa');
        $view->assertSee('Cliente Beta');
        $view->assertSee('$250.00');
        $view->assertSee('$200.00');
        $view->assertSee('Producto Estrella');
        $view->assertSee('(10 uds)');
    }
}
