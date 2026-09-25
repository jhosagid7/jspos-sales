<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\SalesReport;
use App\Livewire\Reports\SalesAnalysisReport;
use App\Livewire\Reports\SellersPerformanceReport;
use App\Livewire\AccountsReceivableReport;
use App\Livewire\Audit\InvoicesAuditList;
use Carbon\Carbon;
use Spatie\Permission\Models\Permission;

class SaleSellerImmutabilityTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $sellerA;
    protected $sellerB;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse('2026-06-10 12:00:00'));

        config([
            'app.installed' => false,
            'tenant.modules' => ['module_credits', 'module_roles', 'module_invoice_audit'],
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
        $this->sellerA = User::factory()->create(['name' => 'Vendedor Javier']);
        $this->sellerB = User::factory()->create(['name' => 'Vendedor Eliecer']);

        Permission::findOrCreate('system.is_seller');
        Permission::findOrCreate('sales.index');
        Permission::findOrCreate('sales.view_all');
        Permission::findOrCreate('collections.audit');

        $this->sellerA->givePermissionTo('system.is_seller');
        $this->sellerB->givePermissionTo('system.is_seller');
        $this->adminUser->givePermissionTo(['sales.index', 'sales.view_all', 'collections.audit']);

        $this->customer = Customer::create([
            'name' => 'Empresa Los Andes',
            'taxpayer_id' => 'J-12345678-0',
            'seller_id' => $this->sellerA->id,
            'address' => 'Av Principal',
            'phone' => '04141234567',
        ]);
    }

    /** @test */
    public function seller_id_is_mass_assignable_on_sale_model()
    {
        $sale = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->sellerA->id,
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
        ]);

        $this->assertDatabaseHas('sales', [
            'id' => $sale->id,
            'seller_id' => $this->sellerA->id,
        ]);

        $this->assertEquals($this->sellerA->id, $sale->fresh()->seller_id);
        $this->assertEquals(strtoupper('Vendedor Javier'), $sale->fresh()->seller->name);
    }

    /** @test */
    public function sales_reports_remain_immutable_when_customer_seller_is_reassigned()
    {
        $this->actingAs($this->adminUser);

        // 1. Sale 1 was created when customer had Seller A
        $sale1 = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->sellerA->id,
            'total' => 200.00,
            'total_usd' => 200.00,
            'items' => 2,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now(),
        ]);

        // 2. Later, the customer is reassigned in the CRM to Seller B
        $this->customer->update(['seller_id' => $this->sellerB->id]);
        $this->assertEquals($this->sellerB->id, $this->customer->fresh()->seller_id);

        // 3. Sale 2 is created under the new seller assignment
        $sale2 = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->sellerB->id,
            'total' => 300.00,
            'total_usd' => 300.00,
            'items' => 3,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now(),
        ]);

        // 4. Test SalesReport filtering by Seller A: MUST only contain Sale 1 ($200)
        Livewire::test(SalesReport::class)
            ->set('showReport', true)
            ->set('seller_id', $this->sellerA->id)
            ->assertViewHas('sales', function ($sales) use ($sale1, $sale2) {
                return $sales->pluck('id')->contains($sale1->id) && !$sales->pluck('id')->contains($sale2->id);
            });

        // 5. Test SalesReport filtering by Seller B: MUST only contain Sale 2 ($300)
        Livewire::test(SalesReport::class)
            ->set('showReport', true)
            ->set('seller_id', $this->sellerB->id)
            ->assertViewHas('sales', function ($sales) use ($sale1, $sale2) {
                return $sales->pluck('id')->contains($sale2->id) && !$sales->pluck('id')->contains($sale1->id);
            });
    }

    /** @test */
    public function sales_analysis_report_preserves_immutable_seller_history()
    {
        $this->actingAs($this->adminUser);

        // Sale 1 belongs to Seller A
        $sale1 = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->sellerA->id,
            'total' => 500.00,
            'total_usd' => 500.00,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now(),
        ]);

        // Change customer to Seller B in CRM
        $this->customer->update(['seller_id' => $this->sellerB->id]);

        $component = Livewire::test(SalesAnalysisReport::class)
            ->set('selectedSellers', [(string)$this->sellerA->id])
            ->call('searchData');

        $kpis = $component->instance()->getSummaryKpis();
        $this->assertEquals(500.00, (float)$kpis['total_sales']);
    }

    /** @test */
    public function accounts_receivable_and_invoices_audit_filter_by_sale_seller()
    {
        $this->actingAs($this->adminUser);

        // Credit Sale 1 made by Seller A
        $sale1 = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'seller_id' => $this->sellerA->id,
            'total' => 450.00,
            'total_usd' => 450.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'credit_days' => 15,
            'created_at' => Carbon::now(),
        ]);

        // Customer reassigned to Seller B
        $this->customer->update(['seller_id' => $this->sellerB->id]);

        // InvoicesAuditList filtering by Seller A must show Sale 1
        Livewire::test(InvoicesAuditList::class)
            ->set('sellerId', (string)$this->sellerA->id)
            ->assertViewHas('sales', function ($sales) use ($sale1) {
                return $sales->pluck('id')->contains($sale1->id);
            });

        // InvoicesAuditList filtering by Seller B must NOT show Sale 1
        Livewire::test(InvoicesAuditList::class)
            ->set('sellerId', (string)$this->sellerB->id)
            ->assertViewHas('sales', function ($sales) use ($sale1) {
                return !$sales->pluck('id')->contains($sale1->id);
            });

        // AccountsReceivableReport filtering by Seller A must include Sale 1
        Livewire::test(AccountsReceivableReport::class)
            ->set('showReport', true)
            ->set('seller_id', $this->sellerA->id)
            ->assertViewHas('sales', function ($sales) use ($sale1) {
                return $sales->pluck('id')->contains($sale1->id);
            });
    }

    /** @test */
    public function fallback_to_customer_seller_works_for_legacy_sales_with_null_seller_id()
    {
        $this->actingAs($this->adminUser);

        // Simulate legacy sale created prior to fix where sales.seller_id was null
        $legacySale = Sale::create([
            'user_id' => $this->adminUser->id,
            'customer_id' => $this->customer->id,
            'seller_id' => null,
            'total' => 150.00,
            'total_usd' => 150.00,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => Carbon::now(),
        ]);

        // Customer currently assigned to Seller A
        $this->customer->update(['seller_id' => $this->sellerA->id]);

        Livewire::test(SalesReport::class)
            ->set('showReport', true)
            ->set('seller_id', $this->sellerA->id)
            ->assertViewHas('sales', function ($sales) use ($legacySale) {
                return $sales->pluck('id')->contains($legacySale->id);
            });
    }
}
