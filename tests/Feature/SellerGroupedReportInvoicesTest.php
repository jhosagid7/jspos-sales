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
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SellerGroupedReportInvoicesTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $operator1;
    protected $operator2;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Reset configuration cache
        $ref = new \ReflectionClass(ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        Configuration::create([
            'business_name' => 'Test POS Store',
            'taxpayer_id' => 'J-12345678-0',
            'address' => 'Av. Principal',
            'city' => 'Caracas',
            'phone' => '0414-1234567',
            'decimals' => 2,
            'vat' => 16,
            'printer_name' => 'EPSON',
            'credit_days' => 15,
        ]);

        config(['tenant.modules' => ['module_seller_grouped']]);

        // Permissions & Roles
        Permission::firstOrCreate(['name' => 'reports.sales']);
        $role = Role::firstOrCreate(['name' => 'Admin']);
        $role->givePermissionTo('reports.sales');

        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Admin');

        $this->operator1 = User::factory()->create(['name' => 'Yuliana Padilla']);
        $this->operator2 = User::factory()->create(['name' => 'Jennifer Neira']);
        $this->customer = Customer::create([
            'name' => 'Cliente VIP',
            'taxpayer_id' => 'V-99999999',
        ]);
    }

    public function test_seller_grouped_report_calculates_invoice_counts_correctly()
    {
        $today = Carbon::today()->format('Y-m-d');

        // Create 2 sales for operator 1
        Sale::create([
            'total' => 50,
            'total_usd' => 50,
            'items' => 1,
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'status' => 'paid',
            'created_at' => Carbon::today()->hour(10),
        ]);
        Sale::create([
            'total' => 80,
            'total_usd' => 80,
            'items' => 1,
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'status' => 'paid',
            'created_at' => Carbon::today()->hour(12),
        ]);

        // Create 1 sale for operator 2
        Sale::create([
            'total' => 120,
            'total_usd' => 120,
            'items' => 1,
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator2->id,
            'status' => 'paid',
            'created_at' => Carbon::today()->hour(14),
        ]);

        // Create 1 returned sale for operator 1 (should not be counted)
        Sale::create([
            'total' => 30,
            'total_usd' => 30,
            'items' => 1,
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'status' => 'returned',
            'created_at' => Carbon::today()->hour(9),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Reports\SellerGroupedReport::class)
            ->set('dateFrom', $today)
            ->set('dateTo', $today)
            ->call('searchData');

        $invoicesData = $component->instance()->getInvoicesData();

        $this->assertEquals(2, $invoicesData['counts']['Yuliana Padilla'] ?? 0);
        $this->assertEquals(1, $invoicesData['counts']['Jennifer Neira'] ?? 0);
    }

    public function test_operator_invoices_modal_loads_data()
    {
        $today = Carbon::today()->format('Y-m-d');

        Sale::create([
            'total' => 100,
            'total_usd' => 100,
            'items' => 1,
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'invoice_number' => 'FAC-001',
            'status' => 'paid',
            'created_at' => Carbon::today()->hour(10),
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Reports\SellerGroupedReport::class)
            ->set('dateFrom', $today)
            ->set('dateTo', $today)
            ->call('searchData')
            ->call('openOperatorInvoicesModal', 'Yuliana Padilla')
            ->assertSet('showOperatorInvoicesModal', true)
            ->assertSet('selectedOperatorName', 'Yuliana Padilla')
            ->assertCount('selectedOperatorInvoices', 1)
            ->call('closeOperatorInvoicesModal')
            ->assertSet('showOperatorInvoicesModal', false);
    }

    public function test_pdf_endpoint_with_invoice_breakdown()
    {
        $today = Carbon::today()->format('Y-m-d');

        Sale::create([
            'total' => 100,
            'total_usd' => 100,
            'items' => 1,
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'invoice_number' => 'FAC-001',
            'status' => 'paid',
            'created_at' => Carbon::today()->hour(10),
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('reports.seller_grouped.pdf', [
            'dateFrom' => $today,
            'dateTo' => $today,
            'showInvoiceBreakdown' => 1,
        ]));

        $this->assertEquals(200, $response->status());
    }

    public function test_print_invoice_ticket_method()
    {
        $sale = Sale::create([
            'total' => 100,
            'total_usd' => 100,
            'items' => 1,
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'invoice_number' => 'FAC-001',
            'status' => 'paid',
            'created_at' => Carbon::today()->hour(10),
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Reports\SellerGroupedReport::class)
            ->call('printInvoiceTicket', $sale->id)
            ->assertDispatched('noty');
    }

    public function test_deleted_and_voided_and_cancelled_sales_are_excluded_from_operator_collections()
    {
        $today = Carbon::today()->format('Y-m-d');

        // Active sale with payment
        $saleActive = Sale::create([
            'total' => 100,
            'total_usd' => 100,
            'items' => 1,
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'invoice_number' => 'FAC-ACTIVE',
            'status' => 'paid',
            'created_at' => Carbon::today()->hour(10),
        ]);
        \App\Models\SalePaymentDetail::create([
            'sale_id' => $saleActive->id,
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 100,
            'exchange_rate' => 1,
            'amount_in_primary_currency' => 100,
            'created_at' => Carbon::today()->hour(10),
        ]);

        // Returned sale with payment (should be excluded)
        $saleReturned = Sale::create([
            'total' => 50,
            'total_usd' => 50,
            'items' => 1,
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'invoice_number' => 'FAC-RETURNED',
            'status' => 'returned',
            'created_at' => Carbon::today()->hour(11),
        ]);
        \App\Models\SalePaymentDetail::create([
            'sale_id' => $saleReturned->id,
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 50,
            'exchange_rate' => 1,
            'amount_in_primary_currency' => 50,
            'created_at' => Carbon::today()->hour(11),
        ]);

        // Sale with deletion_approved_at (eliminated invoice)
        $saleApprovedDeletion = Sale::create([
            'total' => 70,
            'total_usd' => 70,
            'items' => 1,
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'invoice_number' => 'FAC-DEL-APPROVED',
            'status' => 'paid',
            'deletion_approved_at' => Carbon::now(),
            'created_at' => Carbon::today()->hour(11),
        ]);
        \App\Models\SalePaymentDetail::create([
            'sale_id' => $saleApprovedDeletion->id,
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 70,
            'exchange_rate' => 1,
            'amount_in_primary_currency' => 70,
            'created_at' => Carbon::today()->hour(11),
        ]);

        // Soft-deleted sale
        $saleSoftDeleted = Sale::create([
            'total' => 90,
            'total_usd' => 90,
            'items' => 1,
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'invoice_number' => 'FAC-DELETED',
            'status' => 'paid',
            'deleted_at' => Carbon::now(),
            'deletion_approved_at' => Carbon::now(),
            'created_at' => Carbon::today()->hour(12),
        ]);
        \App\Models\SalePaymentDetail::create([
            'sale_id' => $saleSoftDeleted->id,
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 90,
            'exchange_rate' => 1,
            'amount_in_primary_currency' => 90,
            'created_at' => Carbon::today()->hour(12),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Reports\SellerGroupedReport::class)
            ->set('dateFrom', $today)
            ->set('dateTo', $today)
            ->call('searchData');

        $reportData = $component->instance()->getReportData();
        $invoicesData = $component->instance()->getInvoicesData();

        // Should ONLY count the 1 active sale of 100 USD
        $this->assertEquals(1, $invoicesData['counts']['Yuliana Padilla'] ?? 0);
        $operatorPayments = $reportData['Yuliana Padilla'] ?? collect();
        $this->assertNotEmpty($operatorPayments);
        $this->assertEquals(100.00, $operatorPayments->sum('total_usd'));
    }

    public function test_approved_sale_returns_deduct_from_operator_collected_amount()
    {
        $today = Carbon::today()->format('Y-m-d');

        // Sale of $100
        $sale = Sale::create([
            'total' => 100,
            'total_usd' => 100,
            'items' => 2,
            'type' => 1,
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'invoice_number' => 'FAC-DEV',
            'status' => 'paid',
            'created_at' => Carbon::today()->hour(10),
        ]);
        \App\Models\SalePaymentDetail::create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 100,
            'exchange_rate' => 1,
            'amount_in_primary_currency' => 100,
            'created_at' => Carbon::today()->hour(10),
        ]);

        // Approved SaleReturn of $30 cash
        \App\Models\SaleReturn::create([
            'sale_id' => $sale->id,
            'customer_id' => $this->customer->id,
            'user_id' => $this->operator1->id,
            'return_number' => 'DEV-001',
            'total_returned' => 30,
            'refund_method' => 'cash',
            'status' => 'approved',
            'created_at' => Carbon::today()->hour(11),
        ]);

        $component = Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Reports\SellerGroupedReport::class)
            ->set('dateFrom', $today)
            ->set('dateTo', $today)
            ->call('searchData');

        $reportData = $component->instance()->getReportData();
        $invoicesData = $component->instance()->getInvoicesData();

        // 100 - 30 = 70 Net USD
        $operatorPayments = $reportData['Yuliana Padilla'] ?? collect();
        $this->assertEquals(70.00, $operatorPayments->sum('total_usd'));

        // Net amount on invoice in data list
        $inv = $invoicesData['all']->firstWhere('id', $sale->id);
        $this->assertNotNull($inv);
        $this->assertEquals(70.00, (float)$inv->net_total_usd);
    }
}
