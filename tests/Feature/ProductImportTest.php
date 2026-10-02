<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class ProductImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_render_product_import_component()
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(\App\Livewire\ProductImport::class)
            ->assertStatus(200);
    }

    public function test_can_auto_map_and_import_products_from_csv()
    {
        $user = User::factory()->create();

        // Create sample CSV content
        $csvHeader = "Codigo,Producto,Precio,Costo,Stock,Categoria\n";
        $csvRow1 = "PROD-001,Coca Cola 2L,2.50,1.80,50,Bebidas\n";
        $csvRow2 = "PROD-002,Pepsi 1.5L,2.00,1.40,30,Bebidas\n";
        $csvContent = $csvHeader . $csvRow1 . $csvRow2;

        $file = UploadedFile::fake()->createWithContent('productos.csv', $csvContent);

        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\ProductImport::class)
            ->set('file', $file);

        $component->assertSet('step', 2);
        
        // Call import
        $component->call('import')
            ->assertSet('step', 3)
            ->assertSet('successCount', 2);

        $this->assertDatabaseHas('products', [
            'sku' => 'PROD-001',
            'name' => 'Coca Cola 2L',
            'price' => 2.50,
            'cost' => 1.80,
            'stock_qty' => 50,
        ]);

        $this->assertDatabaseHas('products', [
            'sku' => 'PROD-002',
            'name' => 'Pepsi 1.5L',
        ]);
    }

    public function test_can_import_products_to_specific_partner_warehouse_without_affecting_main_stock()
    {
        $user = User::factory()->create();

        $defaultWarehouse = \App\Models\Warehouse::create([
            'name' => 'Almacén Central',
            'is_active' => true,
            'is_partner_warehouse' => false,
        ]);

        $partnerWarehouse = \App\Models\Warehouse::create([
            'name' => 'Depósito Socio A',
            'is_active' => true,
            'is_partner_warehouse' => true,
            'partner_name' => 'Socio A',
        ]);

        \App\Models\Configuration::create([
            'business_name' => 'Test Business',
            'default_warehouse_id' => $defaultWarehouse->id,
        ]);

        $csvContent = "Codigo,Producto,Precio,Costo,Stock,Categoria\n"
                    . "SOC-001,Pantalón Jeans,45.00,20.00,100,Ropa\n";

        $file = UploadedFile::fake()->createWithContent('lote_socio.csv', $csvContent);

        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\ProductImport::class)
            ->set('file', $file);

        $component->assertSet('step', 2);
        // By default it should have defaulted to the default warehouse
        $component->assertSet('warehouse_id', $defaultWarehouse->id);

        // Change warehouse to partner warehouse
        $component->set('warehouse_id', $partnerWarehouse->id);

        // Run import
        $component->call('import')
            ->assertSet('step', 3)
            ->assertSet('successCount', 1);

        $product = Product::where('sku', 'SOC-001')->first();
        $this->assertNotNull($product);

        // Product main sales floor stock must remain 0
        $this->assertEquals(0, $product->stock_qty);

        // Partner warehouse must have the 100 units
        $this->assertDatabaseHas('product_warehouse', [
            'product_id' => $product->id,
            'warehouse_id' => $partnerWarehouse->id,
            'stock_qty' => 100,
        ]);

        // Default warehouse should NOT have the 100 units
        $this->assertDatabaseMissing('product_warehouse', [
            'product_id' => $product->id,
            'warehouse_id' => $defaultWarehouse->id,
            'stock_qty' => 100,
        ]);
    }

    public function test_updating_existing_product_in_partner_warehouse_preserves_main_stock()
    {
        $user = User::factory()->create();

        $defaultWarehouse = \App\Models\Warehouse::create([
            'name' => 'Almacén Central',
            'is_active' => true,
            'is_partner_warehouse' => false,
        ]);

        $partnerWarehouse = \App\Models\Warehouse::create([
            'name' => 'Depósito Socio B',
            'is_active' => true,
            'is_partner_warehouse' => true,
            'partner_name' => 'Socio B',
        ]);

        \App\Models\Configuration::create([
            'business_name' => 'Test Business',
            'default_warehouse_id' => $defaultWarehouse->id,
        ]);

        $supplier = Supplier::create([
            'name' => 'Proveedor Textil',
            'address' => 'Local',
            'phone' => '123456',
            'email' => 'textil@example.com'
        ]);

        $category = Category::create(['name' => 'Ropa']);

        $existingProduct = Product::create([
            'name' => 'Camisa Polo',
            'sku' => 'CAM-001',
            'price' => 25.00,
            'cost' => 10.00,
            'stock_qty' => 15,
            'low_stock' => 5,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
        ]);

        \App\Models\ProductWarehouse::create([
            'product_id' => $existingProduct->id,
            'warehouse_id' => $defaultWarehouse->id,
            'stock_qty' => 15,
        ]);

        $csvContent = "Codigo,Producto,Precio,Costo,Stock,Categoria\n"
                    . "CAM-001,Camisa Polo,25.00,10.00,40,Ropa\n";

        $file = UploadedFile::fake()->createWithContent('lote_socio_b.csv', $csvContent);

        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\ProductImport::class)
            ->set('file', $file)
            ->set('warehouse_id', $partnerWarehouse->id)
            ->call('import');

        $component->assertSet('step', 3);

        $existingProduct->refresh();
        // Main stock should still be 15, not overwritten to 40
        $this->assertEquals(15, $existingProduct->stock_qty);

        // Partner warehouse receives 40
        $this->assertDatabaseHas('product_warehouse', [
            'product_id' => $existingProduct->id,
            'warehouse_id' => $partnerWarehouse->id,
            'stock_qty' => 40,
        ]);

        // Default warehouse still has 15
        $this->assertDatabaseHas('product_warehouse', [
            'product_id' => $existingProduct->id,
            'warehouse_id' => $defaultWarehouse->id,
            'stock_qty' => 15,
        ]);
    }
}

