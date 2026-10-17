<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Vehicle;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Configuration;
use App\Services\ConfigurationService;
use App\Services\WorkshopPrintService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Settings;
use App\Livewire\Sales;
use App\Livewire\SalesReport;
use Spatie\Permission\Models\Permission;

class FormaLibreConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CurrencySeeder::class);

        $this->resetConfigCache();

        Configuration::create([
            'business_name' => 'Automotriz Bucaros C.A.',
            'taxpayer_id' => 'J-12345678-9',
            'address' => 'Caracas, Venezuela',
            'city' => 'Caracas',
            'phone' => '0212-1234567',
            'decimals' => 2,
            'vat' => 16,
            'printer_name' => 'EPSON',
            'credit_days' => 15,
            'enable_forma_libre' => false,
            'forma_libre_template' => 'bucaros',
        ]);

        $this->adminUser = User::factory()->create();
        Permission::findOrCreate('reports.sales');
        Permission::findOrCreate('sales.view_all');
        Permission::findOrCreate('sales.pdf');
        $this->adminUser->givePermissionTo(['reports.sales', 'sales.view_all', 'sales.pdf']);
    }

    private function resetConfigCache(): void
    {
        $ref = new \ReflectionClass(ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);
    }

    public function test_forma_libre_is_disabled_by_default_and_hidden_in_pos(): void
    {
        $this->resetConfigCache();
        $this->assertFalse(ConfigurationService::isFormaLibreEnabled());

        Livewire::actingAs($this->adminUser)
            ->test(Sales::class)
            ->assertSet('enableFormaLibre', false)
            ->assertDontSee('Tipo de Comprobante')
            ->assertDontSee('N° de Factura del Talonario / Forma Libre');
    }

    public function test_settings_can_enable_forma_libre_and_choose_template(): void
    {
        $this->resetConfigCache();

        Livewire::actingAs($this->adminUser)
            ->test(Settings::class)
            ->set('enableFormaLibre', true)
            ->set('formaLibreTemplate', 'bucaros')
            ->call('saveConfig')
            ->assertDispatched('noty', msg: 'Configuración General Actualizada');

        $this->resetConfigCache();
        $config = Configuration::first();
        $this->assertTrue((bool)$config->enable_forma_libre);
        $this->assertEquals('bucaros', $config->forma_libre_template);
        $this->assertTrue(ConfigurationService::isFormaLibreEnabled());
        $this->assertEquals('bucaros', ConfigurationService::getFormaLibreTemplate());
    }

    public function test_pos_displays_forma_libre_selector_when_enabled(): void
    {
        $config = Configuration::first();
        $config->update([
            'enable_forma_libre' => true,
            'forma_libre_template' => 'bucaros',
        ]);
        $this->resetConfigCache();

        Livewire::actingAs($this->adminUser)
            ->test(Sales::class)
            ->assertSet('enableFormaLibre', true)
            ->assertSee('Tipo de Comprobante')
            ->assertSee('Factura Fiscal (Forma Libre)')
            ->assertSee('Nota de Entrega (Interna)');
    }

    public function test_forma_libre_pdf_uses_dynamic_system_vat_rate(): void
    {
        $config = Configuration::first();
        // Configure VAT to 12% instead of 16% to test dynamism
        $config->update([
            'vat' => 12,
            'enable_forma_libre' => true,
        ]);
        $this->resetConfigCache();

        $customer = Customer::create([
            'name' => 'Cliente IVA Dinamico',
            'document_type' => 'J',
            'document_number' => '998877665',
        ]);

        $category = Category::firstOrCreate(['name' => 'REPUESTOS']);
        $supplier = Supplier::firstOrCreate(['name' => 'PROVEEDOR GENERAL']);

        $product = Product::create([
            'name' => 'Repuesto Test',
            'sku' => 'REP-TEST-01',
            'cost' => 50,
            'price' => 100,
            'stock_qty' => 10,
            'low_stock' => 0,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'type' => 'physical',
        ]);

        $sale = Sale::create([
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'total' => 112.00,
            'total_usd' => 112.00,
            'items' => 1,
            'discount' => 0,
            'status' => 'paid',
            'type' => 'cash',
            'document_type' => 'invoice',
            'fiscal_number' => '009999',
            'is_fiscal' => true,
            'invoice_number' => 'F00000099',
        ]);

        $sale->details()->create([
            'product_id' => $product->id,
            'quantity' => 1,
            'sale_price' => 100.00,
            'regular_price' => 100.00,
            'discount' => 0,
            'price_usd' => 100.00,
            'exchange_rate' => 1,
            'custom_name' => 'Repuesto Test Dinamico',
        ]);

        $printService = app(WorkshopPrintService::class);
        $pdf = $printService->generateFormaLibrePdf($sale);
        $output = $pdf->output();

        $this->assertNotEmpty($output);
        // Render html view directly to check that the text contains the dynamic 12%
        $viewHtml = view('pdf.forma-libre-bucaros', [
            'sale' => $sale,
            'config' => $config,
        ])->render();

        $this->assertStringContainsString('IVA 12 %', $viewHtml);
        $this->assertStringContainsString('12,00', $viewHtml); // 12% of 100 is 12.00
    }

    public function test_sales_report_displays_fiscal_badge_and_forma_libre_pdf_button(): void
    {
        $customer = Customer::create([
            'name' => 'AGUAS DE MERIDA C.A.',
            'document_type' => 'J',
            'document_number' => '123456780',
        ]);

        $sale = Sale::create([
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'total' => 116.00,
            'total_usd' => 116.00,
            'items' => 1,
            'discount' => 0,
            'status' => 'paid',
            'type' => 'cash',
            'document_type' => 'invoice',
            'fiscal_number' => '0001',
            'is_fiscal' => true,
            'invoice_number' => 'F00005976',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(SalesReport::class)
            ->set('showReport', true)
            ->assertSee('F00005976')
            ->assertSee('FAC: 0001')
            ->assertSee(route('pos.sales.generatePdfFormaLibre', ['sale' => $sale->id, 'preview' => 1]))
            ->assertSee(route('pos.sales.generatePdfFormaLibre', $sale->id))
            ->assertSee(route('pos.sales.generatePdfInvoice', $sale->id));
    }

    public function test_sales_report_can_search_by_fiscal_number(): void
    {
        $customer = Customer::create([
            'name' => 'AGUAS DE MERIDA C.A.',
            'document_type' => 'J',
            'document_number' => '123456780',
        ]);

        $sale = Sale::create([
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'total' => 116.00,
            'total_usd' => 116.00,
            'items' => 1,
            'discount' => 0,
            'status' => 'paid',
            'type' => 'cash',
            'document_type' => 'invoice',
            'fiscal_number' => '0001',
            'is_fiscal' => true,
            'invoice_number' => 'F00005976',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(SalesReport::class)
            ->set('searchFactura', '0001')
            ->call('searchData')
            ->assertSee('FAC: 0001')
            ->assertSee('F00005976');
    }

    public function test_generate_pdf_forma_libre_route_is_accessible(): void
    {
        $customer = Customer::create([
            'name' => 'AGUAS DE MERIDA C.A.',
            'document_type' => 'J',
            'document_number' => '123456780',
        ]);

        $sale = Sale::create([
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'total' => 116.00,
            'total_usd' => 116.00,
            'items' => 1,
            'discount' => 0,
            'status' => 'paid',
            'type' => 'cash',
            'document_type' => 'invoice',
            'fiscal_number' => '0001',
            'is_fiscal' => true,
            'invoice_number' => 'F00005976',
        ]);

        // Default clean print route without preview parameter
        $response = $this->actingAs($this->adminUser)->get(route('pos.sales.generatePdfFormaLibre', $sale->id));
        $response->assertOk();

        // Preview route with preview=1 query param
        $previewResponse = $this->actingAs($this->adminUser)->get(route('pos.sales.generatePdfFormaLibre', ['sale' => $sale->id, 'preview' => 1]));
        $previewResponse->assertOk();
    }

    public function test_forma_libre_distinguishes_preview_with_background_from_clean_print(): void
    {
        $customer = Customer::create([
            'name' => 'CLIENTE PRUEBA FONDO',
            'document_type' => 'J',
            'document_number' => '112233445',
        ]);

        $sale = Sale::create([
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'total' => 116.00,
            'total_usd' => 116.00,
            'items' => 1,
            'discount' => 0,
            'status' => 'paid',
            'type' => 'cash',
            'document_type' => 'invoice',
            'fiscal_number' => '0002',
            'is_fiscal' => true,
            'invoice_number' => 'F00005977',
        ]);

        $printService = app(WorkshopPrintService::class);

        // 1. Clean print mode (for printer): NO background image
        $cleanPdf = $printService->generateFormaLibrePdf($sale, false);
        $cleanHtml = view('pdf.forma-libre-bucaros', [
            'sale' => $sale,
            'config' => Configuration::first(),
            'showBackground' => false,
            'backgroundImage' => null,
        ])->render();
        $this->assertStringNotContainsString('<img src="data:image', $cleanHtml);
        $this->assertStringNotContainsString('data:image', $cleanHtml);

        // 2. Preview mode (on screen): includes background image
        $previewPdf = $printService->generateFormaLibrePdf($sale, true);
        $previewHtml = view('pdf.forma-libre-bucaros', [
            'sale' => $sale,
            'config' => Configuration::first(),
            'showBackground' => true,
            'backgroundImage' => 'data:image/jpeg;base64,fakeimagedata',
        ])->render();
        $this->assertStringContainsString('<img src="data:image/jpeg;base64,fakeimagedata', $previewHtml);
        $this->assertStringContainsString('@media print', $previewHtml);
    }
}
