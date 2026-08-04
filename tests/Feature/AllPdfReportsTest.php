<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalePaymentDetail;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AllPdfReportsTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $sale;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenant.modules' => [
            'module_seller_performance',
            'module_seller_grouped',
            'module_operator_efficiency',
            'module_delivery',
            'module_purchases',
            'module_customer_report',
            'module_customer_activity',
            'module_sales_analysis',
            'module_collection_audit',
            'module_weekly_income',
            'module_monthly_income',
            'module_cash_flow',
            'module_treasury',
            'module_labels',
            'module_advanced_payments',
            'module_production',
            'module_multi_warehouse',
            'module_credits',
        ]]);

        Configuration::create([
            'business_name' => 'Test Store',
            'taxpayer_id' => 'J-12345678-0',
            'address' => 'Main St',
            'city' => 'Caracas',
            'phone' => '04121234567',
        ]);

        $role = Role::firstOrCreate(['name' => 'Super Admin']);
        $this->user = User::factory()->create(['name' => 'Admin User']);
        $this->user->assignRole($role);

        $seller = User::factory()->create(['name' => 'Seller One']);

        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'taxpayer_id' => 'V-87654321',
            'seller_id' => $seller->id,
            'address' => 'Test Address',
        ]);

        $supplier = Supplier::create([
            'name' => 'Supplier One',
            'taxpayer_id' => 'J-999999999',
        ]);

        $warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
            'address' => 'Central',
        ]);

        $category = Category::create(['name' => 'General']);

        $product = Product::create([
            'name' => 'Test Product',
            'code' => 'P100',
            'barcode' => '123456',
            'cost' => 10,
            'price' => 15,
            'stock' => 100,
            'stock_qty' => 100,
            'stock_min' => 5,
            'low_stock' => 5,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
        ]);

        $this->sale = Sale::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'total' => 150.00,
            'total_usd' => 150.00,
            'items' => 1,
            'cashier_id' => $this->user->id,
            'type' => 'cash',
            'status' => 'paid',
            'created_at' => now(),
        ]);

        SalePaymentDetail::create([
            'sale_id' => $this->sale->id,
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 150.00,
            'exchange_rate' => 1.0,
            'amount_in_primary_currency' => 150.00,
        ]);
    }

    public function test_sellers_performance_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.sellers.performance.pdf'));
        $this->assertNotEquals(500, $response->status(), 'sellers.performance.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_seller_grouped_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.seller_grouped.pdf'));
        $this->assertNotEquals(500, $response->status(), 'seller_grouped.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_billing_operators_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.operators.precision.pdf'));
        $this->assertNotEquals(500, $response->status(), 'operators.precision.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_sales_analysis_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.sales.analysis.pdf'));
        $this->assertNotEquals(500, $response->status(), 'sales.analysis.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_customer_activity_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.customer.activity.pdf'));
        $this->assertNotEquals(500, $response->status(), 'customer.activity.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_customers_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.customers.pdf'));
        $this->assertNotEquals(500, $response->status(), 'customers.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_customers_tracking_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.customers.tracking.pdf'));
        $this->assertNotEquals(500, $response->status(), 'customers.tracking.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_customers_recovery_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.customers.recovery.pdf'));
        $this->assertNotEquals(500, $response->status(), 'customers.recovery.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_general_sales_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.general.sales.pdf'));
        $this->assertNotEquals(500, $response->status(), 'general.sales.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_daily_sales_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.daily.sales.pdf'));
        $this->assertNotEquals(500, $response->status(), 'daily.sales.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_accounts_receivable_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.accounts.receivable.pdf'));
        $this->assertNotEquals(500, $response->status(), 'accounts.receivable.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_customer_statement_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.customer.statement.pdf', ['customer_id' => $this->customer->id]));
        $this->assertNotEquals(500, $response->status(), 'customer.statement.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_inventory_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.inventory.pdf'));
        $this->assertNotEquals(500, $response->status(), 'inventory.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_customer_payment_relationship_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.customer.payment.relationship.pdf'));
        $this->assertNotEquals(500, $response->status(), 'customer.payment.relationship.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_weekly_income_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.weekly.income.pdf'));
        $this->assertNotEquals(500, $response->status(), 'weekly.income.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_monthly_income_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.monthly.income.pdf'));
        $this->assertNotEquals(500, $response->status(), 'monthly.income.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_cash_flow_forecast_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.cash.flow.forecast.pdf'));
        $this->assertNotEquals(500, $response->status(), 'cash.flow.forecast.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_cash_count_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.cash.count.pdf'));
        $this->assertNotEquals(500, $response->status(), 'cash.count.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_cash_count_detailed_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.cash.count.detailed.pdf'));
        $this->assertNotEquals(500, $response->status(), 'cash.count.detailed.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }

    public function test_bank_treasury_pdf()
    {
        $response = $this->actingAs($this->user)->get(route('reports.bank.treasury.pdf'));
        $this->assertNotEquals(500, $response->status(), 'bank.treasury.pdf returned 500: ' . substr($response->getContent(), 0, 500));
    }
}
