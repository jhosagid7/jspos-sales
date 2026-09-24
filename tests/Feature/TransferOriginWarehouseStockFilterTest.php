<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\ProductWarehouse;
use App\Livewire\Transfers;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class TransferOriginWarehouseStockFilterTest extends TestCase
{
    use RefreshDatabase;

    protected $category;
    protected $supplier;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['name' => 'Admin']);
        $user = User::factory()->create();
        $user->assignRole('Admin');
        $this->actingAs($user);

        $this->category = Category::create(['name' => 'General']);
        $this->supplier = Supplier::create([
            'name' => 'Proveedor Test',
            'taxpayer_id' => 'J-12345678-9',
        ]);
    }

    protected function createProduct($name, $sku, $stockQty = 0)
    {
        return Product::create([
            'name' => $name,
            'sku' => $sku,
            'cost' => 1.0,
            'price' => 2.0,
            'stock_qty' => $stockQty,
            'low_stock' => 0,
            'manage_stock' => true,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'status' => 'available'
        ]);
    }

    public function test_product_search_only_returns_products_with_stock_in_selected_origin_warehouse()
    {
        $warehouseA = Warehouse::create(['name' => 'Depósito Socio 1', 'is_active' => true]);
        $warehouseB = Warehouse::create(['name' => 'Tienda Principal', 'is_active' => true]);

        $product1 = $this->createProduct('Bolsa 27x58', 'B2758', 10);
        $product2 = $this->createProduct('Bolsa 19x27', 'B1927', 5);

        // Product 1 has stock in Warehouse A, Product 2 has stock in Warehouse B
        ProductWarehouse::create(['product_id' => $product1->id, 'warehouse_id' => $warehouseA->id, 'stock_qty' => 10]);
        ProductWarehouse::create(['product_id' => $product2->id, 'warehouse_id' => $warehouseB->id, 'stock_qty' => 5]);

        // When origin is Warehouse A
        Livewire::test(Transfers::class)
            ->set('from_warehouse_id', $warehouseA->id)
            ->set('product_search', 'Bolsa')
            ->assertViewHas('products_search_result', function ($products) use ($product1, $product2) {
                return $products->contains('id', $product1->id) && !$products->contains('id', $product2->id);
            });

        // When origin is Warehouse B
        Livewire::test(Transfers::class)
            ->set('from_warehouse_id', $warehouseB->id)
            ->set('product_search', 'Bolsa')
            ->assertViewHas('products_search_result', function ($products) use ($product1, $product2) {
                return !$products->contains('id', $product1->id) && $products->contains('id', $product2->id);
            });
    }

    public function test_cannot_add_product_to_cart_without_stock_in_origin_warehouse()
    {
        $warehouseA = Warehouse::create(['name' => 'Depósito Socio 1', 'is_active' => true]);
        $product = $this->createProduct('Bolsa 27x58', 'B2758', 0);
        // Stock 0 in Warehouse A
        ProductWarehouse::create(['product_id' => $product->id, 'warehouse_id' => $warehouseA->id, 'stock_qty' => 0]);

        Livewire::test(Transfers::class)
            ->set('from_warehouse_id', $warehouseA->id)
            ->call('addToCart', $product->id)
            ->assertDispatched('error')
            ->assertSet('cart', []);
    }

    public function test_quantity_cannot_exceed_available_stock_in_origin_warehouse()
    {
        $warehouseA = Warehouse::create(['name' => 'Depósito Socio 1', 'is_active' => true]);
        $product = $this->createProduct('Bolsa 27x58', 'B2758', 3);
        ProductWarehouse::create(['product_id' => $product->id, 'warehouse_id' => $warehouseA->id, 'stock_qty' => 3]);

        Livewire::test(Transfers::class)
            ->set('from_warehouse_id', $warehouseA->id)
            ->call('addToCart', $product->id)
            ->assertCount('cart', 1)
            ->call('updateQty', 0, 10) // Try to request 10 when stock is 3
            ->assertDispatched('error')
            ->assertSet('cart.0.qty', 3); // Must be capped at 3
    }

    public function test_switching_origin_warehouse_resets_cart()
    {
        $warehouseA = Warehouse::create(['name' => 'Depósito Socio 1', 'is_active' => true]);
        $warehouseB = Warehouse::create(['name' => 'Tienda Principal', 'is_active' => true]);

        $product = $this->createProduct('Bolsa 27x58', 'B2758', 10);
        ProductWarehouse::create(['product_id' => $product->id, 'warehouse_id' => $warehouseA->id, 'stock_qty' => 10]);

        Livewire::test(Transfers::class)
            ->set('from_warehouse_id', $warehouseA->id)
            ->call('addToCart', $product->id)
            ->assertCount('cart', 1)
            ->set('from_warehouse_id', $warehouseB->id)
            ->assertSet('cart', []); // Cart must be cleared when origin warehouse changes
    }
}
