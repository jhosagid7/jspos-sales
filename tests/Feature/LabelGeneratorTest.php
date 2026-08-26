<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use Livewire\Livewire;
use App\Livewire\LabelGenerator;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class LabelGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        config(['tenant.modules' => [
            'module_labels',
        ]]);

        \App\Models\Configuration::create([
            'business_name' => 'Test Store',
            'taxpayer_id' => 'J-12345678-0',
            'address' => 'Main St',
            'city' => 'Caracas',
            'phone' => '04121234567',
        ]);

        // Create permission and role for products.labels
        Permission::firstOrCreate(['name' => 'products.labels', 'guard_name' => 'web']);
        Permission::firstOrCreate(['name' => 'module_labels', 'guard_name' => 'web']);

        $role = Role::findOrCreate('Admin', 'web');
        $role->givePermissionTo(['products.labels', 'module_labels']);

        $this->user = User::factory()->create();
        $this->user->assignRole($role);

        $category = Category::firstOrCreate(['name' => 'General']);
        $supplier = \App\Models\Supplier::firstOrCreate(['name' => 'General Supplier']);

        $this->product = Product::create([
            'name' => 'BOLSA ALTA PLANA 1½ KG 20X30 C-1.8 1 MILLAR',
            'sku' => 'B042030CFA',
            'barcode' => 'B042030CFA',
            'price' => 10.00,
            'cost' => 5.00,
            'stock' => 100,
            'stock_qty' => 100,
            'low_stock' => 5,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
        ]);
    }

    public function test_label_generator_renders_component_and_template_selector()
    {
        $this->actingAs($this->user);

        Livewire::test(LabelGenerator::class)
            ->assertSee('Generador de Etiquetas')
            ->assertSee('Diseño de Salida')
            ->assertSee('Estándar (Código de Barras - 4x7)')
            ->assertSee('Grande (Código QR - 3x6)');
    }

    public function test_can_select_products_and_generate_standard_barcode_pdf()
    {
        $this->actingAs($this->user);

        Livewire::test(LabelGenerator::class)
            ->set('labelTemplate', 'standard')
            ->call('addProduct', $this->product->id)
            ->call('updateQuantity', $this->product->id, 5)
            ->call('generatePdf')
            ->assertDispatched('open-new-tab');

        $this->assertEquals('standard', session('label_template'));
        $this->assertCount(1, session('label_products'));

        $response = $this->withSession([
            'label_products' => session('label_products'),
            'label_template' => 'standard'
        ])->get(route('labels.pdf'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_can_select_products_and_generate_large_qr_pdf()
    {
        $this->actingAs($this->user);

        Livewire::test(LabelGenerator::class)
            ->set('labelTemplate', 'large_qr')
            ->call('addProduct', $this->product->id)
            ->call('updateQuantity', $this->product->id, 3)
            ->call('generatePdf')
            ->assertDispatched('open-new-tab');

        $this->assertEquals('large_qr', session('label_template'));
        $this->assertCount(1, session('label_products'));

        $response = $this->withSession([
            'label_products' => session('label_products'),
            'label_template' => 'large_qr'
        ])->get(route('labels.pdf'));
        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_label_generator_uses_configured_default_template()
    {
        $this->actingAs($this->user);

        $config = \App\Models\Configuration::first();
        $config->update(['default_label_template' => 'large_qr']);

        Livewire::test(LabelGenerator::class)
            ->assertSet('labelTemplate', 'large_qr');
    }

    public function test_can_generate_large_quantity_of_labels_without_memory_exhaustion()
    {
        $this->actingAs($this->user);

        // Create dummy logo image in public directory
        if (!file_exists(public_path('logo'))) {
            mkdir(public_path('logo'), 0777, true);
        }
        $dummyLogo = public_path('logo/logo.jpg');
        if (!file_exists($dummyLogo)) {
            // Create a small 100x100 white image
            $img = imagecreatetruecolor(200, 200);
            $white = imagecolorallocate($img, 255, 255, 255);
            imagefill($img, 0, 0, $white);
            imagejpeg($img, $dummyLogo);
            imagedestroy($img);
        }

        $selectedProducts = [
            $this->product->id => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'barcode' => $this->product->sku,
                'qty' => 500
            ]
        ];

        // Test large QR template with 500 labels and logo
        $responseQr = $this->withSession([
            'label_products' => $selectedProducts,
            'label_template' => 'large_qr'
        ])->get(route('labels.pdf'));

        $responseQr->assertStatus(200);
        $responseQr->assertHeader('content-type', 'application/pdf');

        // Test standard barcode template with 500 labels and logo
        $responseStandard = $this->withSession([
            'label_products' => $selectedProducts,
            'label_template' => 'standard'
        ])->get(route('labels.pdf'));

        $responseStandard->assertStatus(200);
        $responseStandard->assertHeader('content-type', 'application/pdf');
    }
}
