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

class WeeklyIncomeReportTest extends TestCase
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
            'business_name' => 'Weekly Income Business',
            'taxpayer_id' => 'V12345678',
            'address' => 'Weekly Income St.',
            'city' => 'Caracas',
            'phone' => '0212-0000000',
            'decimals' => 2,
            'vat' => 16,
            'printer_name' => 'PDF',
            'credit_days' => 15,
            'module_weekly_income' => true,
            'module_monthly_income' => true,
        ]);

        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($role);
        $this->adminUser->givePermissionTo('reports.sales');
        config(['tenant.modules' => ['module_weekly_income', 'module_monthly_income']]);
    }

    public function test_weekly_income_report_component_can_be_accessed()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.weekly.income'));
        $response->assertStatus(200);
    }

    public function test_weekly_income_report_calculations()
    {
        $this->actingAs($this->adminUser);

        // Use a fixed date: Monday 2026-06-08
        $monday = '2026-06-08';
        Carbon::setTestNow(Carbon::parse($monday));

        $customer = Customer::create([
            'name' => 'Test Customer Weekly',
            'taxpayer_id' => '123456',
            'address' => 'Test',
            'city' => 'Test',
            'type' => 'Consumidor Final'
        ]);

        // 1. Contado Cash Sale (USD) on Monday
        $saleCash = Sale::create([
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 10:00:00',
            'invoice_number' => 'F0001',
            'status' => 'paid',
            'type' => 'cash',
        ]);

        SalePaymentDetail::create([
            'sale_id' => $saleCash->id,
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 100.00,
            'exchange_rate' => 1.00,
            'amount_in_primary_currency' => 100.00
        ]);

        // 2. Contado Cash Sale with change (COP) on Monday
        $saleChange = Sale::create([
            'total' => 50.00,
            'total_usd' => 50.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 11:00:00',
            'invoice_number' => 'F0002',
            'status' => 'paid',
            'type' => 'cash',
        ]);

        SalePaymentDetail::create([
            'sale_id' => $saleChange->id,
            'payment_method' => 'cash',
            'currency_code' => 'COP',
            'amount' => 240000.00, // Equiv USD at rate 4000
            'exchange_rate' => 4000.00,
            'amount_in_primary_currency' => 60.00
        ]);

        SaleChangeDetail::create([
            'sale_id' => $saleChange->id,
            'currency_code' => 'COP',
            'amount' => 40000.00, // 10 USD change
            'exchange_rate' => 4000.00,
            'amount_in_primary_currency' => 10.00
        ]);

        // 3. Collection (Cobranza) on Tuesday 2026-06-09 via CollectionSheet
        $tuesday = '2026-06-09';
        $sheet = CollectionSheet::create([
            'sheet_number' => '20260609-001',
            'opened_at' => $tuesday . ' 08:00:00',
            'opened_by' => $this->adminUser->id,
            'status' => 'open'
        ]);

        Payment::create([
            'collection_sheet_id' => $sheet->id,
            'sale_id' => $saleCash->id, // just reference any sale
            'user_id' => $this->adminUser->id,
            'pay_way' => 'zelle',
            'currency' => 'USD',
            'amount' => 80.00,
            'exchange_rate' => 1.00,
            'status' => 'approved',
            'pay_date' => $tuesday
        ]);

        // 4. Credit Sale on Monday 2026-06-08 (Ventas a Crédito)
        $saleCredit = Sale::create([
            'total' => 150.00,
            'total_usd' => 150.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 14:00:00',
            'invoice_number' => 'F0003',
            'status' => 'pending',
            'type' => 'credit',
        ]);

        // If it had a payment detail at creation: say 30 USD
        SalePaymentDetail::create([
            'sale_id' => $saleCredit->id,
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 30.00,
            'exchange_rate' => 1.00,
            'amount_in_primary_currency' => 30.00
        ]);
        // Net credit should be 150 - 30 = 120 USD.

        // 5. Contado USDT Sale on Wednesday 2026-06-10
        $wednesday = '2026-06-10';
        $saleUsdt = Sale::create([
            'total' => 50.00,
            'total_usd' => 50.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => $wednesday . ' 10:00:00',
            'invoice_number' => 'F0004',
            'status' => 'paid',
            'type' => 'usdt',
        ]);

        SalePaymentDetail::create([
            'sale_id' => $saleUsdt->id,
            'payment_method' => 'usdt',
            'currency_code' => 'USD',
            'amount' => 50.00,
            'exchange_rate' => 1.00,
            'amount_in_primary_currency' => 50.00,
            'bank_name' => 'USDT BINANCE'
        ]);

        // 6. Cobranza USDT on Thursday 2026-06-11 via CollectionSheet
        $thursday = '2026-06-11';
        $sheetThu = CollectionSheet::create([
            'sheet_number' => '20260611-001',
            'opened_at' => $thursday . ' 08:00:00',
            'opened_by' => $this->adminUser->id,
            'status' => 'open'
        ]);

        Payment::create([
            'collection_sheet_id' => $sheetThu->id,
            'sale_id' => $saleCredit->id,
            'user_id' => $this->adminUser->id,
            'pay_way' => 'usdt',
            'currency' => 'USD',
            'amount' => 45.00,
            'exchange_rate' => 1.00,
            'status' => 'approved',
            'pay_date' => $thursday,
            'bank' => 'USDT'
        ]);

        // Test Livewire Component calculations
        Livewire::test(\App\Livewire\Reports\WeeklyIncomeReport::class)
            ->set('selectedDate', $monday)
            ->assertSet('mondayDate', $monday)
            ->assertSet('saturdayDate', '2026-06-13')
            ->assertViewHas('report', function ($report) {
                // LUNES calculations
                // Contado: 100 USD (Dolares) + (60 - 10) COP + 30 USD (pago inicial crédito) = 180 USD
                // Credit sales net of payment details: 150 - 30 = 120 USD
                $lunes = $report['LUNES'];
                $this->assertEquals(180.00, $lunes['subtotal_contado']);
                $this->assertEquals(120.00, $lunes['ventas_credito']);
                $this->assertEquals(300.00, $lunes['ventas_mas_credito']);
                $this->assertEquals(300.00, $lunes['total_general']);
                $this->assertEquals(180.00, $lunes['total_recibido']);

                // MARTES calculations
                // Zelle Payment: 80 USD
                $martes = $report['MARTES'];
                $this->assertEquals(0.00, $martes['subtotal_contado']);
                $this->assertEquals(80.00, $martes['subtotal_cobranza']);
                $this->assertEquals(80.00, $martes['total_general']);
                $this->assertEquals(80.00, $martes['total_recibido']);

                // MIERCOLES calculations
                // USDT Contado: 50 USD
                $miercoles = $report['MIERCOLES'];
                $this->assertEquals(50.00, $miercoles['subtotal_contado']);
                $this->assertEquals(50.00, $miercoles['data']['USDT']['contado']);
                $this->assertEquals(50.00, $miercoles['total_general']);
                $this->assertEquals(50.00, $miercoles['total_recibido']);

                // JUEVES calculations
                // USDT Cobranza: 45 USD
                $jueves = $report['JUEVES'];
                $this->assertEquals(45.00, $jueves['subtotal_cobranza']);
                $this->assertEquals(45.00, $jueves['data']['USDT']['cobranza']);
                $this->assertEquals(45.00, $jueves['total_general']);
                $this->assertEquals(45.00, $jueves['total_recibido']);

                return true;
            })
            ->assertViewHas('weeklyTotalGeneral', 475.00) // 300 Lunes + 80 Martes + 50 Miercoles + 45 Jueves
            ->assertViewHas('weeklyTotalRecibido', 355.00); // 180 Lunes + 80 Martes + 50 Miercoles + 45 Jueves

        Carbon::setTestNow(); // Reset test time
    }

    public function test_weekly_income_report_pdf_export()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.weekly.income.pdf', [
            'date' => '2026-06-08'
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
