<?php
 
namespace Tests\Unit;
 
use Tests\TestCase;
use App\Services\PriceCalculatorService;
use App\Services\CommissionService;
use App\Models\Product;
use App\Models\Customer;
use App\Models\SellerConfig;
use App\Models\CustomerConfig;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
 
class PriceSequentialCalculationTest extends TestCase
{
    use RefreshDatabase;
 
    protected $calculator;
 
    protected function setUp(): void
    {
        parent::setUp();

        // Bypass license and device middlewares in tests
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
        
        // Create Warehouse
        $warehouse = \App\Models\Warehouse::create([
            'name' => 'Main Warehouse'
        ]);

        // Setup Configuration
        \App\Models\Configuration::create([
            'business_name' => 'Test Business',
            'taxpayer_id' => '12345678',
            'address' => 'Test Address 123',
            'city' => 'Caracas',
            'phone' => '0212-5555555',
            'default_warehouse_id' => $warehouse->id
        ]);

        // Seed currencies
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        $this->calculator = new PriceCalculatorService();
    }
 
    public function test_price_calculator_service_sequential_calculation()
    {
        // 1. Setup Models
        $category = \App\Models\Category::create(['name' => 'Test Category']);
        $supplier = \App\Models\Supplier::create(['name' => 'Test Supplier', 'phone' => '123', 'address' => 'A']);
        
        $product = Product::create([
            'name' => 'Test Product', 
            'sku' => 'TEST-001', 
            'price' => 10, // Base price
            'cost' => 5, 
            'stock_qty' => 100,
            'type' => 'physical',
            'status' => 'available',
            'manage_stock' => 1,
            'low_stock' => 1,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'freight_type' => 'none'
        ]);
 
        $seller = User::factory()->create(['name' => 'Test Seller']);
        $sellerConfig = SellerConfig::create([
            'user_id' => $seller->id,
            'commission_percent' => 8.00,
            'freight_percent' => 6.00,
            'exchange_diff_percent' => 45.00
        ]);
 
        // 2. Perform Calculation
        $pricing = $this->calculator->calculate($product, $sellerConfig, null);
 
        // 3. Assertions
        // Commission = 10 * 8% = 0.8
        // Freight = 10 * 6% = 0.6
        // Intermediate Price = 10 + 0.8 + 0.6 = 11.4
        // Exchange Diff = 11.4 * 45% = 5.13
        // Net Price = 11.4 + 5.13 = 16.53
        $this->assertEquals(10.00, $pricing['base_price']);
        $this->assertEquals(0.80, $pricing['commission']);
        $this->assertEquals(0.60, $pricing['freight']);
        $this->assertEquals(5.13, $pricing['exchange_diff']);
        $this->assertEquals(16.53, $pricing['net_price']);
    }
 
    public function test_commission_service_reversion_sequential_calculation()
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Test Customer',
            'taxpayer_id' => '123',
            'address' => 'Test Address',
            'city' => 'Test City',
            'type' => 'Consumidor Final'
        ]);
 
        // Setup a historic sale (before cutoff date: e.g. May 15, 2026)
        $historicSale = Sale::create([
            'total' => 15.90, // old calculation: 10 * 1.59
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'created_at' => '2026-05-15 12:00:00',
            'invoice_number' => 'HIST-001',
            'status' => 'paid',
            'type' => 'cash',
            'applied_commission_percent' => 8.00,
            'applied_freight_percent' => 6.00,
            'applied_exchange_diff_percent' => 45.00
        ]);
 
        // Setup a new sale (after cutoff date: e.g. June 15, 2026)
        $newSale = Sale::create([
            'total' => 16.53, // new calculation: (10 + 0.8 + 0.6) * 1.45
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'created_at' => '2026-06-15 12:00:00',
            'invoice_number' => 'NEW-001',
            'status' => 'paid',
            'type' => 'cash',
            'applied_commission_percent' => 8.00,
            'applied_freight_percent' => 6.00,
            'applied_exchange_diff_percent' => 45.00
        ]);
 
        // 1. Verify Historic Sale Reversion (should use old math: total / 1.59 = 10.00 base)
        $historicPercentage = CommissionService::calculateCommission($historicSale, '2026-05-15 12:00:00');
        $this->assertEquals(0.80, round($historicSale->final_commission_amount, 2));
 
        // 2. Verify New Sale Reversion (should use new math: (total / 1.45) / 1.14 = 10.00 base)
        $newPercentage = CommissionService::calculateCommission($newSale, '2026-06-15 12:00:00');
        $this->assertEquals(0.80, round($newSale->final_commission_amount, 2));
    }
 
    public function test_api_product_controller_sequential_pricing()
    {
        $category = \App\Models\Category::create(['name' => 'Test Category']);
        $supplier = \App\Models\Supplier::create(['name' => 'Test Supplier', 'phone' => '123', 'address' => 'A']);
        $product = Product::create([
            'name' => 'Test Product', 
            'sku' => 'TEST-001', 
            'price' => 10.00, // Base price
            'cost' => 5, 
            'stock_qty' => 100,
            'type' => 'physical',
            'status' => 'available',
            'manage_stock' => 1,
            'low_stock' => 1,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'freight_type' => 'none'
        ]);
 
        $seller = User::factory()->create(['name' => 'Test Seller']);
        $sellerConfig = SellerConfig::create([
            'user_id' => $seller->id,
            'commission_percent' => 8.00,
            'freight_percent' => 6.00,
            'exchange_diff_percent' => 45.00
        ]);
 
        $response = $this->actingAs($seller)
            ->getJson('/api/products');
 
        $response->assertStatus(200);
        $data = $response->json();
        
        // Find product price
        $found = collect($data)->firstWhere('sku', 'TEST-001');
        $this->assertNotNull($found);
        // Sequential calculation: (10 + 0.8 + 0.6) * 1.45 = 16.53
        $this->assertEquals(16.53, $found['price']);
    }
 
    public function test_api_vip_product_controller_sequential_pricing()
    {
        $category = \App\Models\Category::create(['name' => 'Test Category']);
        $supplier = \App\Models\Supplier::create(['name' => 'Test Supplier', 'phone' => '123', 'address' => 'A']);
        $product = Product::create([
            'name' => 'Test Product', 
            'sku' => 'TEST-001', 
            'price' => 10.00, // Base price
            'cost' => 5, 
            'stock_qty' => 100,
            'type' => 'physical',
            'status' => 'available',
            'manage_stock' => 1,
            'low_stock' => 1,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'freight_type' => 'none'
        ]);
 
        $seller = User::factory()->create(['name' => 'Test Seller']);
        
        $customer = Customer::create([
            'name' => 'VIP Customer',
            'taxpayer_id' => 'V123',
            'address' => 'Address',
            'phone' => '123',
            'email' => 'vip@customer.com',
            'password' => bcrypt('password'),
            'seller_id' => $seller->id
        ]);
        
        $customerConfig = CustomerConfig::create([
            'customer_id' => $customer->id,
            'commission_percent' => 8.00,
            'freight_percent' => 6.00,
            'exchange_diff_percent' => 45.00
        ]);
 
        $response = $this->actingAs($customer, 'sanctum')
            ->getJson('/api/vip/products');
 
        $response->assertStatus(200);
        $data = $response->json();
        
        // Find product price
        $found = collect($data)->firstWhere('sku', 'TEST-001');
        $this->assertNotNull($found);
        // Sequential calculation: (10 + 0.8 + 0.6) * 1.45 = 16.53
        $this->assertEquals(16.53, $found['price']);
    }

    public function test_resolved_percentage_accessors_and_fallbacks()
    {
        $seller = User::factory()->create(['name' => 'Test Seller']);
        $sellerConfig = SellerConfig::create([
            'user_id' => $seller->id,
            'commission_percent' => 5.00,
            'freight_percent' => 4.00,
            'exchange_diff_percent' => 3.00
        ]);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'taxpayer_id' => '12345',
            'address' => 'Addr',
            'city' => 'City',
            'seller_id' => $seller->id
        ]);

        // 1. Sale with NULL values (historical fallback)
        $saleNull = Sale::create([
            'total' => 100.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $seller->id,
            'type' => 'cash',
            'status' => 'paid',
            'applied_commission_percent' => null,
            'applied_freight_percent' => null,
            'applied_exchange_diff_percent' => null,
        ]);

        $this->assertEquals(5.00, $saleNull->resolved_commission_percent);
        $this->assertEquals(4.00, $saleNull->resolved_freight_percent);
        $this->assertEquals(3.00, $saleNull->resolved_exchange_diff_percent);

        // 2. Sale with explicit 0.00 values
        $saleZero = Sale::create([
            'total' => 100.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $seller->id,
            'type' => 'cash',
            'status' => 'paid',
            'applied_commission_percent' => 0.00,
            'applied_freight_percent' => 0.00,
            'applied_exchange_diff_percent' => 0.00,
        ]);

        $this->assertEquals(0.00, $saleZero->resolved_commission_percent);
        $this->assertEquals(0.00, $saleZero->resolved_freight_percent);
        $this->assertEquals(0.00, $saleZero->resolved_exchange_diff_percent);

        // 3. Sale with custom values
        $saleCustom = Sale::create([
            'total' => 100.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $seller->id,
            'type' => 'cash',
            'status' => 'paid',
            'applied_commission_percent' => 2.00,
            'applied_freight_percent' => 1.00,
            'applied_exchange_diff_percent' => 7.00,
        ]);

        $this->assertEquals(2.00, $saleCustom->resolved_commission_percent);
        $this->assertEquals(1.00, $saleCustom->resolved_freight_percent);
        $this->assertEquals(7.00, $saleCustom->resolved_exchange_diff_percent);

        // 4. Order with apply flags ON
        $orderOn = \App\Models\Order::create([
            'total' => 100.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $seller->id,
            'apply_commissions' => true,
            'apply_freight' => true,
        ]);

        $this->assertEquals(5.00, $orderOn->resolved_commission_percent);
        $this->assertEquals(4.00, $orderOn->resolved_freight_percent);
        $this->assertEquals(3.00, $orderOn->resolved_exchange_diff_percent);

        // 5. Order with apply flags OFF
        $orderOff = \App\Models\Order::create([
            'total' => 100.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $seller->id,
            'apply_commissions' => false,
            'apply_freight' => false,
        ]);

        $this->assertEquals(0.00, $orderOff->resolved_commission_percent);
        $this->assertEquals(0.00, $orderOff->resolved_freight_percent);
        $this->assertEquals(0.00, $orderOff->resolved_exchange_diff_percent);
    }
}
