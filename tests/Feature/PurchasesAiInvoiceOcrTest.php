<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Models\Configuration;
use Livewire\Livewire;
use App\Livewire\Purchases;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PurchasesAiInvoiceOcrTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $warehouse;
    protected $supplier;
    protected $product1;
    protected $product2;

    protected function setUp(): void
    {
        parent::setUp();

        $ref = new \ReflectionClass(\App\Services\ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        // Create base configuration with Gemini API Key
        Configuration::create([
            'business_name' => 'Comercializadora Test',
            'gemini_api_key' => 'AIzaSyFakeKeyForTest12345',
            'ai_settings' => ['model' => 'gemini-1.5-flash'],
            'vat' => 16,
            'plan_type' => 'pro',
            'local_overrides' => ['module_ai_invoice_ocr' => true, 'module_purchases' => true],
        ]);

        $this->warehouse = Warehouse::create([
            'name' => 'Depósito Principal',
            'status' => 'active',
        ]);

        $this->supplier = Supplier::create([
            'name' => 'Distribuidora Polar C.A.',
            'phone' => '04141234567',
            'address' => 'Caracas',
        ]);

        $category = \App\Models\Category::create(['name' => 'Alimentos']);

        $this->product1 = Product::create([
            'name' => 'Harina PAN 1kg',
            'sku' => 'HPAN-01',
            'cost' => 1.00,
            'price' => 1.50,
            'stock_qty' => 10,
            'low_stock' => 2,
            'status' => 'available',
            'supplier_id' => $this->supplier->id,
            'category_id' => $category->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $this->product2 = Product::create([
            'name' => 'Arroz Primor 1kg',
            'sku' => 'APRIM-02',
            'cost' => 1.20,
            'price' => 1.80,
            'stock_qty' => 5,
            'low_stock' => 2,
            'status' => 'available',
            'supplier_id' => $this->supplier->id,
            'category_id' => $category->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    protected function tearDown(): void
    {
        $ref = new \ReflectionClass(\App\Services\ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        parent::tearDown();
    }

    /** @test */
    public function it_warns_when_gemini_is_not_configured()
    {
        Configuration::first()->update(['gemini_api_key' => null]);
        $ref = new \ReflectionClass(\App\Services\ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null, null);

        Storage::fake('tmp-for-tests');
        $file = UploadedFile::fake()->image('factura.jpg');

        Livewire::test(Purchases::class)
            ->set('invoiceFile', $file)
            ->call('processInvoiceWithAi')
            ->assertDispatched('noty-error');
    }

    /** @test */
    public function it_scans_invoice_and_adds_matched_products_to_cart()
    {
        Storage::fake('tmp-for-tests');
        $file = UploadedFile::fake()->image('factura_proveedor.jpg');

        // Fake Google Gemini API response
        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                [
                                    'text' => json_encode([
                                        'supplier_name' => 'Distribuidora Polar C.A.',
                                        'supplier_rif' => 'J-12345678-9',
                                        'invoice_number' => 'FAC-00049281',
                                        'date' => '2026-09-24',
                                        'currency' => 'USD',
                                        'subtotal' => 38.00,
                                        'tax' => 6.08,
                                        'total' => 44.08,
                                        'items' => [
                                            [
                                                'sku' => 'HPAN-01',
                                                'description' => 'Harina PAN 1kg',
                                                'quantity' => 20,
                                                'unit_price' => 1.10,
                                                'total_price' => 22.00,
                                            ],
                                            [
                                                'sku' => 'APRIM-02',
                                                'description' => 'Arroz Primor 1kg',
                                                'quantity' => 10,
                                                'unit_price' => 1.25,
                                                'total_price' => 12.50,
                                            ],
                                            [
                                                'sku' => 'UNKNOWN-99',
                                                'description' => 'Aceite Diana 1L',
                                                'quantity' => 5,
                                                'unit_price' => 2.50,
                                                'total_price' => 12.50,
                                            ]
                                        ]
                                    ])
                                ]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $component = Livewire::test(Purchases::class)
            ->set('invoiceFile', $file)
            ->call('processInvoiceWithAi');

        // Check matched items added to cart
        $cart = $component->get('cart');
        $this->assertCount(2, $cart);

        // Verify product 1 in cart
        $item1 = $cart->firstWhere('pid', $this->product1->id);
        $this->assertNotNull($item1);
        $this->assertEquals(20, $item1['qty']);
        $this->assertEquals(1.10, $item1['cost']);

        // Verify product 2 in cart
        $item2 = $cart->firstWhere('pid', $this->product2->id);
        $this->assertNotNull($item2);
        $this->assertEquals(10, $item2['qty']);
        $this->assertEquals(1.25, $item2['cost']);

        // Verify supplier matched
        $supplierSelected = $component->get('supplier');
        $this->assertNotNull($supplierSelected);
        $this->assertEquals($this->supplier->id, $supplierSelected['id']);

        // Verify notes contain invoice number
        $notes = $component->get('notes');
        $this->assertStringContainsString('FAC-00049281', $notes);

        // Verify unmatched item is captured for review
        $unmatched = $component->get('aiScanUnmatched');
        $this->assertCount(1, $unmatched);
        $this->assertEquals('Aceite Diana 1L', $unmatched[0]['description']);
    }

    /** @test */
    public function it_handles_gemini_api_failures_gracefully()
    {
        Storage::fake('tmp-for-tests');
        $file = UploadedFile::fake()->create('factura.pdf', 500, 'application/pdf');

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'message' => 'Quota exceeded',
                    'code' => 429
                ]
            ], 429),
        ]);

        Livewire::test(Purchases::class)
            ->set('invoiceFile', $file)
            ->call('processInvoiceWithAi')
            ->assertDispatched('noty-error');
    }

    /** @test */
    public function it_matches_product_using_intelligent_search_and_variations()
    {
        $tina = Product::create([
            'name' => 'TINAS #01 OZ OCCIDENTE 1000UND',
            'sku' => 'T02T01OC',
            'cost' => 5.00,
            'price' => 7.50,
            'stock_qty' => 10,
            'low_stock' => 2,
            'status' => 'available',
            'supplier_id' => $this->supplier->id,
            'category_id' => $this->product1->category_id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $component = new Purchases();

        // Variations that must match
        $variations = [
            'TINA # 01 OCCIDENTE',
            'TINA 01 OCCIDENTE',
            'TINA OCCIDENTE',
            'TINA #1 OCCIDENTE',
            'TINAS OCCIDENTE 01',
        ];

        foreach ($variations as $var) {
            $matched = $component->findProductMatch(null, $var);
            $this->assertNotNull($matched, "Failed to match variation: '{$var}'");
            $this->assertEquals($tina->id, $matched->id, "Variation '{$var}' matched wrong product ID");
        }
    }

    /** @test */
    public function it_searches_products_intelligently_via_autocomplete_products_endpoint()
    {
        $tina = Product::create([
            'name' => 'TINAS #01 OZ OCCIDENTE 1000UND',
            'sku' => 'T02T01OC',
            'cost' => 5.00,
            'price' => 7.50,
            'stock_qty' => 10,
            'low_stock' => 2,
            'status' => 'available',
            'supplier_id' => $this->supplier->id,
            'category_id' => $this->product1->category_id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $response = $this->getJson(route('data.products', ['q' => 'TINA OCCIDENTE']));

        $response->assertStatus(200);
        $results = $response->json();
        $this->assertNotEmpty($results);
        $ids = collect($results)->pluck('id')->all();
        $this->assertContains($tina->id, $ids);
    }

    /** @test */
    public function it_allows_user_to_interactively_link_pending_item_and_add_to_cart()
    {
        $component = Livewire::test(Purchases::class);

        // Simulate invoice with a pending item
        $component->set('aiProcessedItems', [
            [
                'index' => 0,
                'sku' => 'UNKNOWN-99',
                'description' => 'Harina Extra Blanca',
                'quantity' => 15,
                'unit_price' => 1.05,
                'status' => 'pending',
                'matched_product_id' => null,
                'matched_product_name' => null,
                'matched_product_sku' => null,
                'suggestions' => [
                    ['id' => $this->product1->id, 'name' => $this->product1->name, 'sku' => $this->product1->sku]
                ],
            ]
        ]);
        $component->set('aiScanStep', 'review');
        $component->set('selectedMatches.0', $this->product1->id);

        // User clicks linkInvoiceItem
        $component->call('linkInvoiceItem', 0);

        // Verify product1 was added to cart with quantity 15 and cost 1.05
        $cart = $component->get('cart');
        $item = $cart->firstWhere('pid', $this->product1->id);
        $this->assertNotNull($item);
        $this->assertEquals(15, $item['qty']);
        $this->assertEquals(1.05, $item['cost']);

        // Verify item status in aiProcessedItems became matched
        $processed = $component->get('aiProcessedItems');
        $this->assertEquals('matched', $processed[0]['status']);
        $this->assertEquals($this->product1->id, $processed[0]['matched_product_id']);
    }
}
