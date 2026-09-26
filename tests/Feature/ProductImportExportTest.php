<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Livewire\ProductImport;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class ProductImportExportTest extends TestCase
{
    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::first() ?? User::factory()->create([
            'email' => 'admin_test@test.com',
        ]);

        // Asegurar que el usuario tenga rol y permisos necesarios
        if (Role::where('name', 'Admin')->exists()) {
            if (!$this->adminUser->hasRole('Admin')) {
                $this->adminUser->assignRole('Admin');
            }
        }
    }

    public function test_products_export_downloads_excel_file()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('products.export'));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('content-disposition'), 'productos_') &&
            str_contains($response->headers->get('content-disposition'), '.xlsx')
        );
    }

    public function test_products_template_downloads_template_file()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('products.template'));

        $response->assertStatus(200);
        $this->assertTrue(
            str_contains($response->headers->get('content-disposition'), 'plantilla_productos_jspos.xlsx')
        );
    }

    public function test_customers_export_and_template_download()
    {
        $this->actingAs($this->adminUser);

        $responseExport = $this->get(route('customers.export'));
        $responseExport->assertStatus(200);

        $responseTemplate = $this->get(route('customers.template'));
        $responseTemplate->assertStatus(200);
        $this->assertTrue(
            str_contains($responseTemplate->headers->get('content-disposition'), 'plantilla_clientes_jspos.xlsx')
        );
    }

    public function test_product_import_deduplicates_by_name_and_updates_without_creating_duplicates()
    {
        $this->actingAs($this->adminUser);

        $category = Category::firstOrCreate(['name' => 'Comestibles']);
        $supplier = Supplier::firstOrCreate(['name' => 'Proveedor Test'], ['address' => 'Local', 'phone' => '123', 'email' => 't@t.com']);
        $uniqueName = 'Harina Test Deduplicacion ' . uniqid();

        // Creamos un producto inicial con precio 10 y stock 5
        $product = Product::create([
            'name' => $uniqueName,
            'sku' => 'TEST-SKU-' . uniqid(),
            'price' => 10.00,
            'cost' => 8.00,
            'stock_qty' => 5,
            'low_stock' => 10,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'type' => 'physical',
            'status' => 'available',
            'manage_stock' => 1,
        ]);

        $initialCount = Product::where('name', $uniqueName)->count();
        $this->assertEquals(1, $initialCount);

        // Simulamos importación del mismo producto con precio 15 y stock 20 (sin código de barras)
        $component = new ProductImport();
        $component->mapping = [
            'name' => '0',
            'price' => '1',
            'cost' => '2',
            'stock_qty' => '3',
            'category' => '4',
        ];

        // Simulamos el paso de importación con los datos en memoria
        $rows = [
            ['Nombre', 'Precio', 'Costo', 'Stock', 'Categoria'],
            [$uniqueName, '15.50', '9.00', '20', 'Comestibles'],
        ];

        // Ejecutamos la lógica de inserción/actualización con el array simulado
        $tmpFile = tempnam(sys_get_temp_dir(), 'test_import') . '.xlsx';
        Excel::store(new class($rows) implements \Maatwebsite\Excel\Concerns\FromArray {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public function array(): array { return $this->data; }
        }, 'test_import.xlsx', 'local');

        $fullPath = storage_path('app/test_import.xlsx');
        $uploadedFile = UploadedFile::fake()->createWithContent('test_import.xlsx', file_get_contents($fullPath));

        Livewire::test(ProductImport::class)
            ->set('file', $uploadedFile)
            ->call('readHeaders')
            ->set('mapping', [
                'name' => '0',
                'price' => '1',
                'cost' => '2',
                'stock_qty' => '3',
                'category' => '4',
            ])
            ->call('import')
            ->assertSet('step', 3);

        // Verificamos que NO se creó un duplicado: sigue habiendo exactamente 1 producto con ese nombre
        $finalCount = Product::where('name', $uniqueName)->count();
        $this->assertEquals(1, $finalCount, 'El producto no debe duplicarse al importar de nuevo el mismo nombre');

        // Y verificamos que sus datos se actualizaron
        $product->refresh();
        $this->assertEquals(15.50, (float)$product->price);
        $this->assertEquals(20, (int)$product->stock_qty);

        // Limpieza
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        $product->forceDelete();
    }

    public function test_product_import_deduplicates_by_sku_and_updates_without_creating_duplicates()
    {
        $this->actingAs($this->adminUser);

        $category = Category::firstOrCreate(['name' => 'Comestibles']);
        $supplier = Supplier::firstOrCreate(['name' => 'Proveedor Test'], ['address' => 'Local', 'phone' => '123', 'email' => 't@t.com']);
        $barcode = 'BARCODE-770' . rand(100000, 999999);

        $product = Product::create([
            'name' => 'Producto Barcode Original ' . uniqid(),
            'sku' => $barcode,
            'price' => 5.00,
            'cost' => 3.00,
            'stock_qty' => 10,
            'low_stock' => 5,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'type' => 'physical',
            'status' => 'available',
            'manage_stock' => 1,
        ]);

        $rows = [
            ['Nombre', 'Codigo', 'Precio', 'Costo', 'Stock'],
            ['Producto Nombre Actualizado', $barcode, '8.50', '4.00', '35'],
        ];

        Excel::store(new class($rows) implements \Maatwebsite\Excel\Concerns\FromArray {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public function array(): array { return $this->data; }
        }, 'test_import_sku.xlsx', 'local');

        $fullPath = storage_path('app/test_import_sku.xlsx');
        $uploadedFile = UploadedFile::fake()->createWithContent('test_import_sku.xlsx', file_get_contents($fullPath));

        Livewire::test(ProductImport::class)
            ->set('file', $uploadedFile)
            ->call('readHeaders')
            ->set('mapping', [
                'name' => '0',
                'barcode' => '1',
                'price' => '2',
                'cost' => '3',
                'stock_qty' => '4',
            ])
            ->call('import')
            ->assertSet('step', 3);

        // Verificamos que el producto con ese código de barras sigue siendo 1 solo (no se duplicó)
        $this->assertEquals(1, Product::where('sku', $barcode)->count());

        $product->refresh();
        $this->assertEquals(mb_strtoupper('Producto Nombre Actualizado'), $product->name);
        $this->assertEquals(8.50, (float)$product->price);
        $this->assertEquals(35, (int)$product->stock_qty);

        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        $product->forceDelete();
    }
}
