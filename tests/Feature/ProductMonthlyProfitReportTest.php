<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Configuration;
use Livewire\Livewire;
use App\Livewire\Reports\ProductMonthlyProfitReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ProductMonthlyProfitReportTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $customer;
    protected $warehouse;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        Configuration::create([
            'business_name' => 'JSPOS Sales Test',
            'taxpayer_id' => 'V-12345678-9',
            'address' => 'Main Street 123',
            'phone' => '1234567',
        ]);

        $this->adminUser = User::factory()->create(['name' => 'Admin User']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'reports.sales']);
        $this->adminUser->givePermissionTo('reports.sales');

        $this->warehouse = Warehouse::create([
            'name' => 'Almacén Principal',
            'is_active' => 1,
        ]);

        $this->customer = Customer::create([
            'name' => 'Cliente General',
            'taxpayer_id' => '12345',
            'address' => 'Dirección Test',
            'city' => 'Ciudad Test',
        ]);

        $category = Category::create(['name' => 'VIVERES']);
        $supplier = Supplier::create(['name' => 'POLAR']);

        $this->product = Product::create([
            'name' => 'HARINA PAN BLANCA 1KG',
            'sku' => 'HP-001',
            'cost' => 5.00,
            'price' => 10.00,
            'stock_qty' => 100,
            'low_stock' => 10,
            'manage_stock' => 1,
            'status' => 'available',
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_component_can_render_for_authorized_user()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.product.monthly.profit'));
        $response->assertStatus(200);

        Livewire::test(ProductMonthlyProfitReport::class)
            ->assertStatus(200)
            ->assertSee('RENTABILIDAD Y PRECIOS MENSUALES POR PRODUCTO');
    }

    public function test_search_and_select_product()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(ProductMonthlyProfitReport::class)
            ->set('search', 'HARINA')
            ->assertCount('products_results', 1)
            ->call('selectProduct', $this->product->id)
            ->assertSet('product_id', $this->product->id)
            ->assertSet('selected_product_name', $this->product->name)
            ->assertCount('products_results', 0);
    }

    public function test_calculates_monthly_data_and_detects_multiple_prices_in_same_month()
    {
        $this->actingAs($this->adminUser);

        // May: 2 sales with different prices ($10 and $12)
        $dateMay1 = Carbon::create(2026, 5, 5, 10, 0, 0);
        $dateMay2 = Carbon::create(2026, 5, 20, 14, 0, 0);

        $saleMay1 = Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->adminUser->id,
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => $dateMay1,
        ]);
        SaleDetail::create([
            'sale_id' => $saleMay1->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 10,
            'regular_price' => 10.00,
            'sale_price' => 10.00,
            'discount' => 0.00,
            'created_at' => $dateMay1,
        ]);

        $saleMay2 = Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->adminUser->id,
            'total' => 60.00,
            'total_usd' => 60.00,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => $dateMay2,
        ]);
        SaleDetail::create([
            'sale_id' => $saleMay2->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 5,
            'regular_price' => 12.00,
            'sale_price' => 12.00,
            'discount' => 0.00,
            'created_at' => $dateMay2,
        ]);

        // June: 1 sale with price $12
        $dateJun = Carbon::create(2026, 6, 10, 11, 0, 0);
        $saleJun = Sale::create([
            'customer_id' => $this->customer->id,
            'user_id' => $this->adminUser->id,
            'total' => 240.00,
            'total_usd' => 240.00,
            'items' => 1,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => $dateJun,
        ]);
        SaleDetail::create([
            'sale_id' => $saleJun->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 20,
            'regular_price' => 12.00,
            'sale_price' => 12.00,
            'discount' => 0.00,
            'created_at' => $dateJun,
        ]);

        $component = Livewire::test(ProductMonthlyProfitReport::class)
            ->set('product_id', $this->product->id)
            ->set('dateFrom', '2026-05-01')
            ->set('dateTo', '2026-06-30');

        $monthlyData = $component->get('monthlyData');
        $totals = $component->get('totals');

        // Check 2 months exist
        $this->assertCount(2, $monthlyData);

        // May checks
        $mayData = $monthlyData[0];
        $this->assertEquals(15.0, (float)$mayData['sold_qty']);
        $this->assertEquals(160.0, (float)$mayData['sold_amount']);
        $this->assertEquals(75.0, (float)$mayData['cost_amount']);
        $this->assertEquals(85.0, (float)$mayData['profit_amount']);
        $this->assertEquals(53.13, round($mayData['margin_percent'], 2));
        $this->assertTrue($mayData['has_price_changes']);
        $this->assertCount(2, $mayData['price_breakdown']);

        // June checks
        $junData = $monthlyData[1];
        $this->assertEquals(20.0, (float)$junData['sold_qty']);
        $this->assertEquals(240.0, (float)$junData['sold_amount']);
        $this->assertEquals(100.0, (float)$junData['cost_amount']);
        $this->assertEquals(140.0, (float)$junData['profit_amount']);
        $this->assertEquals(58.33, round($junData['margin_percent'], 2));
        $this->assertFalse($junData['has_price_changes']);

        // Totals checks
        $this->assertEquals(35.0, (float)$totals['total_qty']);
        $this->assertEquals(400.0, (float)$totals['total_sold']);
        $this->assertEquals(175.0, (float)$totals['total_cost']);
        $this->assertEquals(225.0, (float)$totals['total_profit']);
        $this->assertEquals(56.25, round($totals['margin_percent'], 2));
    }

    public function test_pdf_endpoint_returns_pdf_response()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.product.monthly.profit.pdf', [
            'product_id' => $this->product->id,
            'dateFrom' => '2026-05-01',
            'dateTo' => '2026-06-30',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
