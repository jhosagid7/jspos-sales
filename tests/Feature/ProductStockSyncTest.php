<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\ProductItem;
use App\Models\Configuration;
use App\Models\ProductWarehouse;
use Livewire\Livewire;
use App\Livewire\Products;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ProductStockSyncTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $category;
    protected $supplier;
    protected $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::create([
            'id' => 1,
            'name' => 'TIENDA PRINCIPAL',
            'is_active' => 1,
        ]);

        // Setup Configuration
        Configuration::create([
            'business_name' => 'Test Business',
            'default_warehouse_id' => $this->warehouse->id,
        ]);

        $this->user = User::factory()->create();
        \Spatie\Permission\Models\Permission::findOrCreate('products.edit');
        \Spatie\Permission\Models\Permission::findOrCreate('products.edit.inventory');
        $this->user->givePermissionTo(['products.edit', 'products.edit.inventory']);
        
        $this->category = Category::create(['name' => 'Zanahorias']);
        $this->supplier = Supplier::create(['name' => 'El Proveedor']);
    }

    public function test_variable_weight_product_syncs_stock_from_items_correctly()
    {
        // 1. Create a variable weight product with initial stock 0
        $product = Product::create([
            'name' => 'BOBINA DE ZANAHORIA 1KG',
            'sku' => 'B04BOZHB',
            'price' => 10,
            'cost' => 5,
            'manage_stock' => 1,
            'stock_qty' => 0,
            'low_stock' => 1,
            'category_id' => $this->category->id,
            'supplier_id' => $this->supplier->id,
            'is_variable_quantity' => true,
        ]);

        // 2. Setup warehouse relationship
        ProductWarehouse::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'stock_qty' => 0,
        ]);

        // 3. Create items in the ProductItem table
        // Item 1: Available, 25.50 kg
        ProductItem::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 25.50,
            'original_quantity' => 25.50,
            'status' => 'available',
        ]);

        // Item 2: Available, 20.00 kg
        ProductItem::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 20.00,
            'original_quantity' => 20.00,
            'status' => 'available',
        ]);

        // Item 3: Reserved, 15.00 kg (should not count as available stock)
        ProductItem::create([
            'product_id' => $product->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 15.00,
            'original_quantity' => 15.00,
            'status' => 'reserved',
        ]);

        // 4. Test loading edit form in Livewire Products component
        // It should calculate stock as the sum of available items (25.5 + 20.0 = 45.5)
        Livewire::actingAs($this->user)
            ->test(Products::class)
            ->call('Edit', $product)
            ->assertSet('form.stock_qty', 45.50)
            ->assertSet('form.stock_details.0.stock', 45.50)
            ->assertHasNoErrors()
            // 5. Test saving the form (running Update)
            ->call('Update')
            ->assertHasNoErrors();

        // 6. Verify database records are updated/synchronized to 45.50
        $product->refresh();
        $this->assertEquals(45.50, $product->stock_qty);

        $pw = ProductWarehouse::where('product_id', $product->id)
            ->where('warehouse_id', $this->warehouse->id)
            ->first();
        $this->assertEquals(45.50, $pw->stock_qty);
    }
}
