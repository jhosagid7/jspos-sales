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
}
