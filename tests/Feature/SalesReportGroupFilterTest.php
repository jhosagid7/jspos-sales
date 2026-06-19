<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;

class SalesReportGroupFilterTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $seller1;
    protected $seller2;
    protected $customer1;
    protected $customer2;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed currencies
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Reset ConfigurationService static cache
        $ref = new \ReflectionClass(ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        // Create Configuration
        Configuration::create([
            'business_name' => 'Test Business',
            'taxpayer_id' => '12345678',
            'address' => 'Test Address 123',
            'city' => 'Caracas',
            'phone' => '0212-5555555',
            'decimals' => 2,
            'vat' => 16,
            'printer_name' => 'EPSON',
            'credit_days' => 15,
            'sequential_cut_off_date' => '2026-06-03 00:00:00'
        ]);

        $this->adminUser = User::factory()->create();

        // Create sellers (sellers are Users)
        $this->seller1 = User::factory()->create(['name' => 'Seller A']);
        $this->seller2 = User::factory()->create(['name' => 'Seller B']);

        $this->customer1 = Customer::create([
            'name' => 'Customer A',
            'taxpayer_id' => 'A123',
            'address' => 'Addr A',
            'city' => 'City A',
            'type' => 'Consumidor Final',
            'seller_id' => $this->seller1->id,
        ]);

        $this->customer2 = Customer::create([
            'name' => 'Customer B',
            'taxpayer_id' => 'B123',
            'address' => 'Addr B',
            'city' => 'City B',
            'type' => 'Consumidor Final',
            'seller_id' => $this->seller2->id,
        ]);
    }

    public function test_grouping_by_seller_populates_available_and_selected_groups()
    {
        $this->actingAs($this->adminUser);

        // Create Sales
        $sale1 = Sale::create([
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'customer_id' => $this->customer1->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-12 12:00:00',
            'invoice_number' => 'INV-1',
            'status' => 'paid',
            'type' => 'cash',
        ]);

        $sale2 = Sale::create([
            'total' => 150.00,
            'total_usd' => 150.00,
            'items' => 1,
            'customer_id' => $this->customer2->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-12 12:00:00',
            'invoice_number' => 'INV-2',
            'status' => 'paid',
            'type' => 'cash',
        ]);

        Livewire::test(\App\Livewire\SalesReport::class)
            ->set('dateFrom', '2026/06/01')
            ->set('dateTo', '2026/06/30')
            ->set('groupBy', 'seller_id')
            ->call('searchData')
            ->assertSet('availableGroups', [
                $this->seller1->id => 'Seller A',
                $this->seller2->id => 'Seller B'
            ])
            ->assertSet('selectedGroups', [
                (string)$this->seller1->id,
                (string)$this->seller2->id
            ]);
    }

    public function test_unchecking_seller_filters_grouped_sales_and_recalculates_totals()
    {
        $this->actingAs($this->adminUser);

        // Create Sales
        $sale1 = Sale::create([
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'customer_id' => $this->customer1->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-12 12:00:00',
            'invoice_number' => 'INV-1',
            'status' => 'paid',
            'type' => 'cash',
        ]);

        $sale2 = Sale::create([
            'total' => 150.00,
            'total_usd' => 150.00,
            'items' => 1,
            'customer_id' => $this->customer2->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-12 12:00:00',
            'invoice_number' => 'INV-2',
            'status' => 'paid',
            'type' => 'cash',
        ]);

        // Initially we should have both.
        // Let's uncheck Seller B (id = $this->seller2->id) and verify:
        // 1. Grouped sales for Seller B is not rendered (or filtered out of groupedSales)
        // 2. Totals are recalculated to only show $100.00 (Seller A)
        
        $comp = Livewire::test(\App\Livewire\SalesReport::class)
            ->set('dateFrom', '2026/06/01')
            ->set('dateTo', '2026/06/30')
            ->set('groupBy', 'seller_id')
            ->call('searchData')
            ->assertSee('Seller A')
            ->assertSee('Seller B')
            ->assertSet('totales', 250.00);

        // Now simulate unchecking Seller B
        $comp->set('selectedGroups', [(string)$this->seller1->id])
            ->assertSee('Subtotal: $100.00')
            ->assertDontSee('Subtotal: $150.00')
            ->assertSet('totales', 100.00);
    }

    public function test_pdf_preview_modal_opens_and_renders_correctly()
    {
        $this->actingAs($this->adminUser);

        // Create Sales
        $sale1 = Sale::create([
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'customer_id' => $this->customer1->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-12 12:00:00',
            'invoice_number' => 'INV-1',
            'status' => 'paid',
            'type' => 'cash',
        ]);

        $sale2 = Sale::create([
            'total' => 150.00,
            'total_usd' => 150.00,
            'items' => 1,
            'customer_id' => $this->customer2->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-12 12:00:00',
            'invoice_number' => 'INV-2',
            'status' => 'paid',
            'type' => 'cash',
        ]);

        Livewire::test(\App\Livewire\SalesReport::class)
            ->set('dateFrom', '2026/06/01')
            ->set('dateTo', '2026/06/30')
            ->set('groupBy', 'seller_id')
            ->call('searchData')
            ->assertSet('showPdfModal', false)
            ->set('selectedGroups', [(string)$this->seller1->id])
            ->call('openPdfPreview')
            ->assertSet('showPdfModal', true)
            ->assertSee('Vista Previa: Reporte de Ventas General')
            ->assertSee('iframe');
    }
}
