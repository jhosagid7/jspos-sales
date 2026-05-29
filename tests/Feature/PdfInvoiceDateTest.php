<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Cargo;
use App\Models\CargoDetail;
use App\Models\Descargo;
use App\Models\DescargoDetail;
use App\Models\Purchase;
use App\Models\PurchaseDetail;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Category;
use App\Models\Warehouse;
use App\Models\Configuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;

class PdfInvoiceDateTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $supplier;
    protected $warehouse;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Bypass license and device middlewares in test
        config([
            'app.installed' => false,
            'tenant.modules' => [
                'module_purchases',
                'module_multi_warehouse',
                'module_production',
                'module_whatsapp',
                'module_roles',
                'module_commissions',
                'module_labels',
            ],
        ]);

        // Setup Configuration
        Configuration::create([
            'business_name' => 'Test Business',
            'taxpayer_id' => 'V12345678',
            'address' => 'Test Address 123',
            'city' => 'Caracas',
            'phone' => '0212-5555555',
            'email' => 'test@business.com',
            'bcv_rate' => 54.50,
            'binance_rate' => 70.00,
            'binance_markup_points' => 5.00,
        ]);

        // Seed currencies
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Create user and give permissions
        $this->user = User::factory()->create();
        Permission::findOrCreate('sales.pdf');
        Permission::findOrCreate('adjustments.create');
        Permission::findOrCreate('purchases.index');
        
        $this->user->givePermissionTo([
            'sales.pdf',
            'adjustments.create',
            'purchases.index',
        ]);

        // Create Customer, Supplier, Warehouse, Category, Product
        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'taxpayer_id' => 'V99999999',
            'address' => 'Customer Address',
            'city' => 'Caracas',
            'phone' => '0412-1111111',
            'email' => 'customer@email.com',
        ]);

        $this->supplier = Supplier::create([
            'name' => 'Test Supplier',
            'taxpayer_id' => 'J88888888',
            'address' => 'Supplier Address',
            'phone' => '0212-2222222',
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Main Warehouse',
        ]);

        $category = Category::create([
            'name' => 'Test Category',
        ]);

        $this->product = Product::create([
            'name' => 'Test Product',
            'sku' => 'TEST-SKU',
            'cost' => 10.00,
            'price' => 15.00,
            'manage_stock' => false,
            'stock_qty' => 100,
            'low_stock' => 0,
            'supplier_id' => $this->supplier->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_sale_pdf_uses_sale_created_at_date()
    {
        $historicalDate = Carbon::parse('2025-05-20 14:00:00');

        $sale = Sale::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'total' => 15.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'invoice_number' => 'F00000001',
            'created_at' => $historicalDate,
            'updated_at' => $historicalDate,
        ]);

        SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'regular_price' => 15.00,
            'sale_price' => 15.00,
            'quantity' => 1,
            'discount' => 0.00,
            'created_at' => $historicalDate,
            'updated_at' => $historicalDate,
        ]);

        $response = $this->actingAs($this->user)->get(route('pos.sales.generatePdfInvoice', $sale));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_order_pdf_uses_order_created_at_date()
    {
        $historicalDate = Carbon::parse('2025-06-15 10:30:00');

        $order = Order::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'total' => 15.00,
            'items' => 1,
            'status' => 'pending',
            'order_number' => 'ORD-00001',
            'created_at' => $historicalDate,
            'updated_at' => $historicalDate,
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $this->product->id,
            'warehouse_id' => $this->warehouse->id,
            'regular_price' => 15.00,
            'sale_price' => 15.00,
            'quantity' => 1,
            'discount' => 0.00,
            'created_at' => $historicalDate,
            'updated_at' => $historicalDate,
        ]);

        $response = $this->actingAs($this->user)->get(route('pos.orders.generatePdfOrderInvoice', $order));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_cargo_pdf_uses_cargo_created_at_date()
    {
        $historicalDate = Carbon::parse('2025-07-10 11:15:00');

        $cargo = Cargo::create([
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'motive' => 'Test motive',
            'date' => $historicalDate,
            'comments' => 'Test cargo comments',
            'status' => 'approved',
            'created_at' => $historicalDate,
            'updated_at' => $historicalDate,
        ]);

        CargoDetail::create([
            'cargo_id' => $cargo->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'cost' => 10.00,
            'created_at' => $historicalDate,
            'updated_at' => $historicalDate,
        ]);

        $response = $this->actingAs($this->user)->get(route('cargos.pdf', $cargo));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_descargo_pdf_uses_descargo_created_at_date()
    {
        $historicalDate = Carbon::parse('2025-08-05 09:45:00');

        $descargo = Descargo::create([
            'warehouse_id' => $this->warehouse->id,
            'user_id' => $this->user->id,
            'motive' => 'Test motive',
            'date' => $historicalDate,
            'comments' => 'Test descargo comments',
            'status' => 'approved',
            'created_at' => $historicalDate,
            'updated_at' => $historicalDate,
        ]);

        DescargoDetail::create([
            'descargo_id' => $descargo->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
            'cost' => 10.00,
            'created_at' => $historicalDate,
            'updated_at' => $historicalDate,
        ]);

        $response = $this->actingAs($this->user)->get(route('descargos.pdf', $descargo));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_purchase_pdf_uses_purchase_created_at_date()
    {
        $historicalDate = Carbon::parse('2025-09-01 16:20:00');

        $purchase = Purchase::create([
            'user_id' => $this->user->id,
            'supplier_id' => $this->supplier->id,
            'total' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'created_at' => $historicalDate,
            'updated_at' => $historicalDate,
        ]);

        PurchaseDetail::create([
            'purchase_id' => $purchase->id,
            'product_id' => $this->product->id,
            'quantity' => 10,
            'cost' => 10.00,
            'flete_product' => 0.00,
            'flete_total' => 0.00,
            'created_at' => $historicalDate,
            'updated_at' => $historicalDate,
        ]);

        $response = $this->actingAs($this->user)->get(route('purchases.pdf', $purchase));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
    }
}
