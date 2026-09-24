<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Configuration;
use App\Services\GeminiAiService;
use Illuminate\Support\Facades\Http;
use Illuminate\Foundation\Testing\RefreshDatabase;

class GeminiAiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function service_reports_not_configured_when_no_api_key()
    {
        Configuration::create([
            'business_name' => 'Empresa Test',
            'gemini_api_key' => null,
        ]);

        $service = new GeminiAiService();
        $this->assertFalse($service->isConfigured());

        $result = $service->testConnection();
        $this->assertFalse($result['success']);
        $this->assertStringContainsString('No se ha configurado', $result['message']);
    }

    /** @test */
    public function test_connection_succeeds_when_gemini_responds_ok()
    {
        Configuration::create([
            'business_name' => 'Empresa Test',
            'gemini_api_key' => 'AIzaSyFakeTestKey123456789',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => 'OK']
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $service = new GeminiAiService();
        $this->assertTrue($service->isConfigured());

        $result = $service->testConnection();
        $this->assertTrue($result['success']);
        $this->assertStringContainsString('exitosa', $result['message']);
    }

    /** @test */
    public function test_connection_handles_api_errors_gracefully()
    {
        Configuration::create([
            'business_name' => 'Empresa Test',
            'gemini_api_key' => 'AIzaSyInvalidKey',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'error' => [
                    'code' => 400,
                    'message' => 'API_KEY_INVALID',
                    'status' => 'INVALID_ARGUMENT'
                ]
            ], 400),
        ]);

        $service = new GeminiAiService();
        $result = $service->testConnection();

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('API_KEY_INVALID', $result['message']);
    }

    /** @test */
    public function analyze_purchase_invoice_parses_json_items_successfully()
    {
        Configuration::create([
            'business_name' => 'Empresa Test',
            'gemini_api_key' => 'AIzaSyFakeTestKey123456789',
        ]);

        $mockInvoiceData = [
            'supplier_name' => 'DISTRIBUIDORA POLAR C.A.',
            'supplier_tax_id' => 'J-00000000-0',
            'invoice_number' => 'FAC-9988',
            'invoice_date' => '2026-09-24',
            'currency' => 'USD',
            'items' => [
                [
                    'name' => 'HARINA PAN 1KG',
                    'sku' => '7591000100',
                    'quantity' => 10,
                    'unit_cost' => 1.10,
                    'total_cost' => 11.00,
                ],
                [
                    'name' => 'ARROZ PRIMOR 1KG',
                    'sku' => '7591000200',
                    'quantity' => 5,
                    'unit_cost' => 1.25,
                    'total_cost' => 6.25,
                ]
            ],
            'subtotal' => 17.25,
            'tax_amount' => 0.00,
            'total_amount' => 17.25,
        ];

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => Http::response([
                'candidates' => [
                    [
                        'content' => [
                            'parts' => [
                                ['text' => json_encode($mockInvoiceData)]
                            ]
                        ]
                    ]
                ]
            ], 200),
        ]);

        $service = new GeminiAiService();
        $fakeBase64 = base64_encode('fake image content');
        $result = $service->analyzePurchaseInvoice($fakeBase64, 'image/jpeg');

        $this->assertTrue($result['success']);
        $this->assertEquals('DISTRIBUIDORA POLAR C.A.', $result['data']['supplier_name']);
        $this->assertEquals('FAC-9988', $result['data']['invoice_number']);
        $this->assertCount(2, $result['data']['items']);
        $this->assertEquals('HARINA PAN 1KG', $result['data']['items'][0]['name']);
        $this->assertEquals(10, $result['data']['items'][0]['quantity']);
        $this->assertEquals(1.10, $result['data']['items'][0]['unit_cost']);
    }

    /** @test */
    public function analyze_purchase_invoice_handles_network_timeout_without_crashing()
    {
        Configuration::create([
            'business_name' => 'Empresa Test',
            'gemini_api_key' => 'AIzaSyFakeTestKey123456789',
        ]);

        Http::fake([
            'https://generativelanguage.googleapis.com/*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out after 25000 milliseconds');
            },
        ]);

        $service = new GeminiAiService();
        $result = $service->analyzePurchaseInvoice('fakebase64', 'image/jpeg');

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Fallo de conexión o timeout', $result['error']);
    }
}
