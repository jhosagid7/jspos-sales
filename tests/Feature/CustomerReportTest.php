<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Configuration;
use Livewire\Livewire;
use App\Livewire\Reports\CustomerReport;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerReportTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $seller1;
    protected $seller2;

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

        // Create an admin user who has permissions to access the report
        $this->adminUser = User::factory()->create();
        // Give permission 'reports.sales' (which is checked in routes/web.php)
        $this->adminUser->givePermissionTo('reports.sales');

        // Create sellers
        // To be considered a seller, the user scope 'sellers' checks for 'system.is_seller' or 'system.is_foreign_seller' permissions
        $this->seller1 = User::factory()->create(['name' => 'Vendedor Uno']);
        $this->seller1->givePermissionTo('system.is_seller');

        $this->seller2 = User::factory()->create(['name' => 'Vendedor Dos']);
        $this->seller2->givePermissionTo('system.is_seller');
    }

    public function test_customer_report_component_renders_for_authorized_user()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CustomerReport::class)
            ->assertStatus(200)
            ->assertViewIs('livewire.reports.customer-report')
            ->assertSee('Vendedor Uno')
            ->assertSee('Vendedor Dos');
    }

    public function test_customer_report_filters_by_selected_sellers()
    {
        $this->actingAs($this->adminUser);

        $customer1 = Customer::create([
            'name' => 'Cliente A',
            'taxpayer_id' => '1',
            'address' => 'Address 1',
            'city' => 'City 1',
            'seller_id' => $this->seller1->id,
            'type' => 'Consumidor Final',
        ]);

        $customer2 = Customer::create([
            'name' => 'Cliente B',
            'taxpayer_id' => '2',
            'address' => 'Address 2',
            'city' => 'City 2',
            'seller_id' => $this->seller2->id,
            'type' => 'Consumidor Final',
        ]);

        // Livewire test without query
        Livewire::test(CustomerReport::class)
            ->assertSet('showReport', false)
            // Select seller 1
            ->set('selectedSellers', [$this->seller1->id])
            ->call('searchData')
            ->assertSet('showReport', true)
            ->assertViewHas('customers', function ($customers) use ($customer1, $customer2) {
                return $customers->contains($customer1) && !$customers->contains($customer2);
            });
    }

    public function test_customer_report_groups_by_seller()
    {
        $this->actingAs($this->adminUser);

        $customer1 = Customer::create([
            'name' => 'Cliente A',
            'taxpayer_id' => '1',
            'address' => 'Address 1',
            'city' => 'City 1',
            'seller_id' => $this->seller1->id,
            'type' => 'Consumidor Final',
        ]);

        $customer2 = Customer::create([
            'name' => 'Cliente B',
            'taxpayer_id' => '2',
            'address' => 'Address 2',
            'city' => 'City 2',
            'seller_id' => $this->seller2->id,
            'type' => 'Consumidor Final',
        ]);

        Livewire::test(CustomerReport::class)
            ->set('groupBy', 'seller_id')
            ->call('searchData')
            ->assertViewHas('customers', function ($customers) {
                return isset($customers['Vendedor Uno']) && isset($customers['Vendedor Dos']);
            });
    }

    public function test_customer_report_filters_soft_deleted_customers()
    {
        $this->actingAs($this->adminUser);

        $customerActive = Customer::create([
            'name' => 'Cliente Activo',
            'taxpayer_id' => '1',
            'address' => 'Address 1',
            'city' => 'City 1',
            'seller_id' => $this->seller1->id,
            'type' => 'Consumidor Final',
        ]);

        $customerDeleted = Customer::create([
            'name' => 'Cliente Inactivo',
            'taxpayer_id' => '2',
            'address' => 'Address 2',
            'city' => 'City 2',
            'seller_id' => $this->seller1->id,
            'type' => 'Consumidor Final',
        ]);
        $customerDeleted->delete(); // Soft delete

        // Show only active
        Livewire::test(CustomerReport::class)
            ->set('showDeleted', false)
            ->call('searchData')
            ->assertViewHas('customers', function ($customers) use ($customerActive, $customerDeleted) {
                return $customers->contains($customerActive) && !$customers->contains($customerDeleted);
            });

        // Show with deleted
        Livewire::test(CustomerReport::class)
            ->set('showDeleted', true)
            ->call('searchData')
            ->assertViewHas('customers', function ($customers) use ($customerActive, $customerDeleted) {
                return $customers->contains($customerActive) && $customers->contains($customerDeleted);
            });
    }

    public function test_customer_pdf_returns_200_and_pdf_type()
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::create([
            'name' => 'PDF Customer',
            'taxpayer_id' => '123',
            'address' => 'PDF Address',
            'city' => 'PDF City',
            'seller_id' => $this->seller1->id,
            'type' => 'Consumidor Final',
        ]);

        $response = $this->get(route('reports.customers.pdf', [
            'selectedSellers' => $this->seller1->id,
            'groupBy' => 'none',
            'showDeleted' => 0,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_customer_tracking_pdf_returns_200_and_pdf_type()
    {
        $this->actingAs($this->adminUser);

        Customer::create([
            'name' => 'Tracking PDF Customer',
            'taxpayer_id' => '123-T',
            'address' => 'Tracking PDF Address',
            'city' => 'Tracking PDF City',
            'seller_id' => $this->seller1->id,
            'type' => 'Consumidor Final',
        ]);

        $response = $this->get(route('reports.customers.tracking.pdf', [
            'selectedSellers' => $this->seller1->id,
            'groupBy' => 'none',
            'showDeleted' => 0,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_customer_report_component_can_trigger_tracking_pdf()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CustomerReport::class)
            ->set('selectedSellers', [$this->seller1->id])
            ->call('openTrackingPdfPreview')
            ->assertSet('showTrackingPdfModal', true)
            ->assertSet('trackingPdfUrl', route('reports.customers.tracking.pdf', [
                'selectedSellers' => $this->seller1->id,
                'groupBy' => 'none',
                'showDeleted' => 0,
            ]))
            ->call('closeTrackingPdfPreview')
            ->assertSet('showTrackingPdfModal', false)
            ->assertSet('trackingPdfUrl', '');
    }

    public function test_customer_report_filters_by_inactivity_days()
    {
        $this->actingAs($this->adminUser);

        // Customer A: bought 45 days ago
        $customerA = Customer::create([
            'name' => 'Cliente Inactivo 45d',
            'taxpayer_id' => '1',
            'address' => 'Addr 1',
            'city' => 'City 1',
            'seller_id' => $this->seller1->id,
            'type' => 'Consumidor Final',
        ]);

        $saleA = \App\Models\Sale::create([
            'total' => 100,
            'total_usd' => 100,
            'items' => 1,
            'customer_id' => $customerA->id,
            'user_id' => $this->adminUser->id,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => \Carbon\Carbon::now()->subDays(45),
        ]);

        // Customer B: bought 10 days ago
        $customerB = Customer::create([
            'name' => 'Cliente Activo 10d',
            'taxpayer_id' => '2',
            'address' => 'Addr 2',
            'city' => 'City 2',
            'seller_id' => $this->seller1->id,
            'type' => 'Consumidor Final',
        ]);

        $saleB = \App\Models\Sale::create([
            'total' => 50,
            'total_usd' => 50,
            'items' => 1,
            'customer_id' => $customerB->id,
            'user_id' => $this->adminUser->id,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => \Carbon\Carbon::now()->subDays(10),
        ]);

        // Inactivity threshold: 30 days
        Livewire::test(CustomerReport::class)
            ->set('inactivityDays', 30)
            ->call('searchData')
            ->assertViewHas('customers', function ($customers) use ($customerA, $customerB) {
                return $customers->contains($customerA) && !$customers->contains($customerB);
            });
    }

    public function test_customer_recovery_pdf_returns_200_and_pdf_type()
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::create([
            'name' => 'Recovery Cust',
            'taxpayer_id' => '123',
            'address' => 'Addr',
            'city' => 'City',
            'seller_id' => $this->seller1->id,
            'type' => 'Consumidor Final',
        ]);

        $response = $this->get(route('reports.customers.recovery.pdf', [
            'selectedSellers' => $this->seller1->id,
            'groupBy' => 'none',
            'showDeleted' => 0,
            'inactivityDays' => 30,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_customer_report_component_can_trigger_recovery_pdf()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CustomerReport::class)
            ->set('selectedSellers', [$this->seller1->id])
            ->set('inactivityDays', 60)
            ->call('openRecoveryPdfPreview')
            ->assertSet('showRecoveryPdfModal', true)
            ->assertSet('recoveryPdfUrl', route('reports.customers.recovery.pdf', [
                'selectedSellers' => $this->seller1->id,
                'groupBy' => 'none',
                'showDeleted' => 0,
                'inactivityDays' => 60,
            ]))
            ->call('closeRecoveryPdfPreview')
            ->assertSet('showRecoveryPdfModal', false)
            ->assertSet('recoveryPdfUrl', '');
    }

    public function test_customer_tracking_pdf_view_renders_totals()
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::create([
            'name' => 'Tracking Test Cust',
            'taxpayer_id' => '123',
            'address' => 'Addr',
            'city' => 'City',
            'seller_id' => $this->seller1->id,
            'type' => 'Consumidor Final',
        ]);

        $customersData = ['' => collect([$customer])];
        $config = Configuration::first();
        $user = $this->adminUser;
        $date = '23/06/2026 11:00';
        $isGrouped = false;
        $showDeleted = false;

        $view = $this->view('reports.customer-tracking-pdf', compact(
            'customersData', 'isGrouped', 'config', 'user', 'date', 'showDeleted'
        ));

        $view->assertSee('TOTAL CLIENTES EN SEGUIMIENTO:');
        $view->assertSee('1');
    }

    public function test_customer_recovery_pdf_view_renders_totals()
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::create([
            'name' => 'Recovery Test Cust',
            'taxpayer_id' => '123',
            'address' => 'Addr',
            'city' => 'City',
            'seller_id' => $this->seller1->id,
            'type' => 'Consumidor Final',
        ]);

        $customersData = ['' => collect([$customer])];
        $config = Configuration::first();
        $user = $this->adminUser;
        $date = '23/06/2026 11:00';
        $isGrouped = false;
        $showDeleted = false;
        $inactivityDays = 30;

        $view = $this->view('reports.customer-recovery-pdf', compact(
            'customersData', 'isGrouped', 'config', 'user', 'date', 'showDeleted', 'inactivityDays'
        ));

        $view->assertSee('TOTAL CLIENTES INACTIVOS PARA RECUPERACIÓN:');
        $view->assertSee('1');
    }
}
