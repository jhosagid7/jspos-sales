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

    public function test_can_import_products_with_multiple_prices_and_decimal_stock()
    {
        $user = User::factory()->create();

        $defaultWarehouse = \App\Models\Warehouse::create([
            'name' => 'Almacén Central',
            'is_active' => true,
            'is_partner_warehouse' => false,
        ]);

        \App\Models\Configuration::create([
            'business_name' => 'Test Multi Price',
            'default_warehouse_id' => $defaultWarehouse->id,
        ]);

        $csvHeader = "Codigo,Producto,Precio de Venta,Precio 2,Precio 3,Costo,Stock,Categoria,Descripcion\n";
        $csvRow = "PROD-MP-1,Harina Especial,10.00,9.50,9.00,7.00,25.75,Alimentos,Harina de primera calidad\n";
        $csvContent = $csvHeader . $csvRow;

        $file = UploadedFile::fake()->createWithContent('multiprecio.csv', $csvContent);

        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\ProductImport::class)
            ->set('file', $file);

        $component->assertSet('step', 2);

        // Verify autoMap mapped description and additional prices
        $mapping = $component->get('mapping');
        $this->assertArrayHasKey('price', $mapping);
        $this->assertArrayHasKey('price_2', $mapping);
        $this->assertArrayHasKey('price_3', $mapping);
        $this->assertArrayHasKey('description', $mapping);

        $component->call('import')
            ->assertSet('step', 3)
            ->assertSet('successCount', 1);

        $product = Product::where('sku', 'PROD-MP-1')->first();
        $this->assertNotNull($product);
        $this->assertEquals('HARINA ESPECIAL', $product->name);
        $this->assertEquals(10.00, (float)$product->price);
        $this->assertEquals(25.75, (float)$product->stock_qty); // Decimal stock preserved!
        $this->assertEquals('Harina de primera calidad', $product->description);

        // Verify PriceList entries
        $priceLists = \App\Models\PriceList::where('product_id', $product->id)->orderBy('price', 'desc')->get();
        $this->assertCount(2, $priceLists);
        $this->assertEquals(9.50, (float)$priceLists[0]->price);
        $this->assertEquals(9.00, (float)$priceLists[1]->price);
    }

    public function test_old_template_with_single_price_is_backward_compatible_and_preserves_existing_prices()
    {
        $user = User::factory()->create();

        $supplier = Supplier::create(['name' => 'General', 'address' => 'Local', 'phone' => '123', 'email' => 'g@g.com']);
        $category = Category::create(['name' => 'General']);

        // Existing product that already has Price 2 and Price 3 configured in the system
        $product = Product::create([
            'name' => 'Producto Antiguo',
            'sku' => 'OLD-SKU-1',
            'price' => 5.00,
            'cost' => 3.00,
            'stock_qty' => 10,
            'low_stock' => 5,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
        ]);

        \App\Models\PriceList::create(['product_id' => $product->id, 'price' => 4.50]);
        \App\Models\PriceList::create(['product_id' => $product->id, 'price' => 4.00]);

        // Uploading an old format CSV with only 1 price column
        $csvHeader = "Codigo,Producto,Precio de Venta,Costo,Stock,Categoria,Descripcion\n";
        $csvRow = "OLD-SKU-1,Producto Antiguo,6.00,3.50,20,General,Actualizado con planilla vieja\n";
        $csvContent = $csvHeader . $csvRow;

        $file = UploadedFile::fake()->createWithContent('planilla_vieja.csv', $csvContent);

        $component = Livewire::actingAs($user)
            ->test(\App\Livewire\ProductImport::class)
            ->set('file', $file)
            ->call('import')
            ->assertSet('step', 3);

        $product->refresh();
        $this->assertEquals(6.00, (float)$product->price);
        $this->assertEquals(20, (float)$product->stock_qty);
        $this->assertEquals('Actualizado con planilla vieja', $product->description);

        // Existing PriceList MUST be preserved because old template didn't map multiple prices
        $priceLists = \App\Models\PriceList::where('product_id', $product->id)->get();
        $this->assertCount(2, $priceLists);
        $this->assertEquals([4.50, 4.00], $priceLists->pluck('price')->map(fn($p) => (float)$p)->toArray());
    }

    public function test_parses_numbers_with_currency_symbols_and_formats_cleanly()
    {
        $user = User::factory()->create();

        $csvHeader = "Codigo,Producto,Precio,Costo,Stock,Categoria\n";
        $csvRow = "PROD-CURR-1,Producto Formatos,\"$ 1,500.50\",\"Bs. 1.200,50\",15,General\n";
        $csvContent = $csvHeader . $csvRow;

        $file = UploadedFile::fake()->createWithContent('formatos.csv', $csvContent);

        Livewire::actingAs($user)
            ->test(\App\Livewire\ProductImport::class)
            ->set('file', $file)
            ->call('import')
            ->assertSet('step', 3);

        $product = Product::where('sku', 'PROD-CURR-1')->first();
        $this->assertNotNull($product);
        $this->assertEquals(1500.50, (float)$product->price);
        $this->assertEquals(1200.50, (float)$product->cost);
    }
}

