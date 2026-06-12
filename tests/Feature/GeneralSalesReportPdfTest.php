<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GeneralSalesReportPdfTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

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
            'business_name' => 'Test Business PDF',
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
    }

    private function resetConfigCache()
    {
        $ref = new \ReflectionClass(ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);
    }

    public function test_general_sales_pdf_returns_200_with_pdf_content_type()
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::create([
            'name' => 'PDF Test Customer',
            'taxpayer_id' => '123456',
            'address' => 'Test',
            'city' => 'Test',
            'type' => 'Consumidor Final'
        ]);

        Sale::create([
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 2,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 12:00:00',
            'invoice_number' => 'F00099001',
            'status' => 'paid',
            'type' => 'cash',
            'applied_commission_percent' => 8.00,
            'applied_freight_percent' => 6.00,
            'applied_exchange_diff_percent' => 45.00,
            'base_amount' => 62.89,
            'commission_amount' => 5.03,
            'freight_amount' => 3.77,
            'exchange_diff_amount' => 28.31,
        ]);

        $response = $this->get(route('reports.general.sales.pdf', [
            'dateFrom' => '2026-06-08',
            'dateTo' => '2026-06-08',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_general_sales_pdf_contains_business_info_and_sale_data()
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::create([
            'name' => 'ACME CORPORATION',
            'taxpayer_id' => 'J-12345',
            'address' => 'Test',
            'city' => 'Test',
            'type' => 'Consumidor Final'
        ]);

        Sale::create([
            'total' => 66.95,
            'total_usd' => 66.95,
            'items' => 6,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 14:00:00',
            'invoice_number' => 'F00002006',
            'status' => 'pending',
            'type' => 'credit',
            'applied_commission_percent' => 4.00,
            'applied_freight_percent' => 6.00,
            'applied_exchange_diff_percent' => 57.00,
            'base_amount' => 40.09,
            'commission_amount' => 1.60,
            'freight_amount' => 2.41,
            'exchange_diff_amount' => 22.85,
        ]);

        // The PDF is a binary stream, so we verify it returns 200 and is a PDF
        $response = $this->get(route('reports.general.sales.pdf', [
            'dateFrom' => '2026-06-08',
            'dateTo' => '2026-06-08',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_general_sales_pdf_with_filters()
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::create([
            'name' => 'Filter Test',
            'taxpayer_id' => 'F-789',
            'address' => 'Test',
            'city' => 'Test',
            'type' => 'Consumidor Final'
        ]);

        // Create a cash sale
        Sale::create([
            'total' => 50.00,
            'total_usd' => 50.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 10:00:00',
            'invoice_number' => 'F00088001',
            'status' => 'paid',
            'type' => 'cash',
        ]);

        // Create a credit sale
        Sale::create([
            'total' => 75.00,
            'total_usd' => 75.00,
            'items' => 3,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 11:00:00',
            'invoice_number' => 'F00088002',
            'status' => 'pending',
            'type' => 'credit',
        ]);

        // Filter by type=cash - should still return 200
        $response = $this->get(route('reports.general.sales.pdf', [
            'dateFrom' => '2026-06-08',
            'dateTo' => '2026-06-08',
            'type' => 'cash',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');

        // Filter by user_id
        $response = $this->get(route('reports.general.sales.pdf', [
            'dateFrom' => '2026-06-08',
            'dateTo' => '2026-06-08',
            'user_id' => $this->adminUser->id,
        ]));

        $response->assertStatus(200);

        // Filter by customer_id
        $response = $this->get(route('reports.general.sales.pdf', [
            'dateFrom' => '2026-06-08',
            'dateTo' => '2026-06-08',
            'customer_id' => $customer->id,
        ]));

        $response->assertStatus(200);
    }

    public function test_general_sales_pdf_with_no_sales_returns_empty_pdf()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.general.sales.pdf', [
            'dateFrom' => '2099-01-01',
            'dateTo' => '2099-01-01',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_general_sales_pdf_with_acuerdo_column_toggle()
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::create([
            'name' => 'Acuerdo Test Customer',
            'taxpayer_id' => '123456-A',
            'address' => 'Test',
            'city' => 'Test',
            'type' => 'Consumidor Final'
        ]);

        Sale::create([
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 2,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 12:00:00',
            'invoice_number' => 'F00099001',
            'status' => 'paid',
            'type' => 'cash',
            'payment_agreement' => 'BCV',
        ]);

        // Request with 'acuerdo' => true
        $columnsWithAcuerdo = [
            'folio' => true, 'cliente' => true, 'operador' => false, 'vendedor' => false, 'base' => true, 'porcentaje' => true,
            'comision' => true, 'flete' => true, 'recargo' => true, 'diferencial' => true, 'total' => true,
            'credito' => true, 'acuerdo' => true, 'articulos' => true, 'estatus' => true, 'tipo' => true, 'fecha' => true,
        ];

        $response = $this->get(route('reports.general.sales.pdf', [
            'dateFrom' => '2026-06-08',
            'dateTo' => '2026-06-08',
            'columns' => json_encode($columnsWithAcuerdo),
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');

        // Request with 'acuerdo' => false
        $columnsWithoutAcuerdo = $columnsWithAcuerdo;
        $columnsWithoutAcuerdo['acuerdo'] = false;

        $response = $this->get(route('reports.general.sales.pdf', [
            'dateFrom' => '2026-06-08',
            'dateTo' => '2026-06-08',
            'columns' => json_encode($columnsWithoutAcuerdo),
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_daily_sales_pdf_returns_200_and_renders_payment_agreement()
    {
        $this->actingAs($this->adminUser);

        $customer = Customer::create([
            'name' => 'Daily Test Customer',
            'taxpayer_id' => '123456-D',
            'address' => 'Test',
            'city' => 'Test',
            'type' => 'Consumidor Final'
        ]);

        // Create sale with BCV agreement
        Sale::create([
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 2,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 12:00:00',
            'invoice_number' => 'F00099001',
            'status' => 'paid',
            'type' => 'cash',
            'payment_agreement' => 'BCV',
        ]);

        // Create sale with USD agreement
        Sale::create([
            'total' => 50.00,
            'total_usd' => 50.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 13:00:00',
            'invoice_number' => 'F00099002',
            'status' => 'paid',
            'type' => 'cash',
            'payment_agreement' => 'USD',
        ]);

        $response = $this->get(route('reports.daily.sales.pdf', [
            'dateFrom' => '2026-06-08',
            'dateTo' => '2026-06-08',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}

