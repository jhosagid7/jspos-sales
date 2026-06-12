<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Models\CollectionSheet;
use App\Models\Payment;
use App\Models\SalePaymentDetail;
use App\Models\SaleChangeDetail;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;

class MonthlyIncomeReportTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed currencies if needed
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Reset ConfigurationService static cache
        $ref = new \ReflectionClass(ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        // Create Configuration
        Configuration::create([
            'business_name' => 'Monthly Income Business',
            'taxpayer_id' => 'V12345678',
            'address' => 'Monthly Income St.',
            'city' => 'Caracas',
            'phone' => '0212-0000000',
            'decimals' => 2,
            'vat' => 16,
            'printer_name' => 'PDF',
            'credit_days' => 15
        ]);

        $this->adminUser = User::factory()->create();
        $this->adminUser->syncRoles([]);
        $this->adminUser->givePermissionTo('reports.sales');
    }

    public function test_monthly_income_report_component_can_be_accessed()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.monthly.income'));
        $response->assertStatus(200);
    }

    public function test_monthly_income_report_calculations()
    {
        $this->actingAs($this->adminUser);

        // Set Month: June 2026
        $month = '2026-06';
        Carbon::setTestNow(Carbon::parse('2026-06-15')); // Mid-June

        $customer = Customer::create([
            'name' => 'Test Customer Monthly',
            'taxpayer_id' => '123456',
            'address' => 'Test',
            'city' => 'Test',
            'type' => 'Consumidor Final'
        ]);

        // Week 1: Mon 01/06 - Sat 06/06 (ISO Week 2026-23)
        // Sale of 100 USD cash
        $saleW1 = Sale::create([
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-03 10:00:00', // Wednesday
            'invoice_number' => 'FM0001',
            'status' => 'paid',
            'type' => 'cash',
        ]);

        SalePaymentDetail::create([
            'sale_id' => $saleW1->id,
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 100.00,
            'exchange_rate' => 1.00,
            'amount_in_primary_currency' => 100.00
        ]);

        // Week 2: Mon 08/06 - Sat 13/06 (ISO Week 2026-24)
        // Zelle Payment of 80 USD via Collection Sheet
        $sheetW2 = CollectionSheet::create([
            'sheet_number' => '20260609-001',
            'opened_at' => '2026-06-09 08:00:00', // Tuesday
            'opened_by' => $this->adminUser->id,
            'status' => 'open'
        ]);

        Payment::create([
            'collection_sheet_id' => $sheetW2->id,
            'sale_id' => $saleW1->id,
            'user_id' => $this->adminUser->id,
            'pay_way' => 'zelle',
            'currency' => 'USD',
            'amount' => 80.00,
            'exchange_rate' => 1.00,
            'status' => 'approved',
            'pay_date' => '2026-06-09'
        ]);

        // Week 3: Mon 15/06 - Sat 20/06 (ISO Week 2026-25)
        // Credit Sale of 200 USD (net)
        $saleW3 = Sale::create([
            'total' => 200.00,
            'total_usd' => 200.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-17 14:00:00', // Wednesday
            'invoice_number' => 'FM0002',
            'status' => 'pending',
            'type' => 'credit',
        ]);

        // Test Livewire component calculations
        Livewire::test(\App\Livewire\Reports\MonthlyIncomeReport::class)
            ->set('selectedMonth', $month)
            ->assertSet('selectedMonth', $month)
            ->assertViewHas('report', function ($report) {
                // Category 'DOLARES' should have:
                // Week 2026-23: Contado = 100
                $this->assertEquals(100.00, $report['DOLARES']['2026-23']['contado']);
                return true;
            })
            ->assertViewHas('weeklyMetrics', function ($metrics) {
                // ISO Week 2026-23 (Week 1): subtotal_contado = 100, total_general = 100
                $this->assertEquals(100.00, $metrics['2026-23']['subtotal_contado']);
                // ISO Week 2026-24 (Week 2): subtotal_cobranza = 80, total_general = 80
                $this->assertEquals(80.00, $metrics['2026-24']['subtotal_cobranza']);
                // ISO Week 2026-25 (Week 3): ventas_credito = 200, total_general = 200
                $this->assertEquals(200.00, $metrics['2026-25']['ventas_credito']);
                return true;
            })
            ->assertViewHas('monthlyTotalGeneral', 380.00) // 100 (W1) + 80 (W2) + 200 (W3)
            ->assertViewHas('monthlyTotalRecibido', 180.00); // 100 (W1) + 80 (W2)

        Carbon::setTestNow(); // Reset
    }

    public function test_monthly_income_report_pdf_export()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.monthly.income.pdf', [
            'month' => '2026-06'
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
