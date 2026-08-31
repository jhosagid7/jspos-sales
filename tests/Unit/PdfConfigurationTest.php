<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Configuration;
use App\Models\User;
use Livewire\Livewire;
use App\Livewire\Settings;

class PdfConfigurationTest extends TestCase
{
    /** @test */
    public function it_returns_default_pdf_settings_structure_with_all_true()
    {
        $defaults = Configuration::getDefaultPdfSettings();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('sales_paid', $defaults);
        $this->assertArrayHasKey('sales_credit', $defaults);
        $this->assertArrayHasKey('orders', $defaults);
        $this->assertArrayHasKey('purchase_orders', $defaults);
        $this->assertArrayHasKey('debit_notes', $defaults);
        $this->assertArrayHasKey('reports', $defaults);

        // Verify sales_paid keys
        $this->assertTrue($defaults['sales_paid']['show_logo']);
        $this->assertTrue($defaults['sales_paid']['show_company_data']);
        $this->assertTrue($defaults['sales_paid']['show_seller_data']);
        $this->assertTrue($defaults['sales_paid']['show_seller_banks']);
        $this->assertTrue($defaults['sales_paid']['show_subtotal']);
        $this->assertTrue($defaults['sales_paid']['show_tax']);
        $this->assertTrue($defaults['sales_paid']['show_signature_box']);
        $this->assertTrue($defaults['sales_paid']['show_amount_in_words']);
        $this->assertTrue($defaults['sales_paid']['show_notes']);
        $this->assertTrue($defaults['sales_paid']['show_qr']);
        $this->assertTrue($defaults['sales_paid']['show_footer_code']);

        // Verify sales_credit keys
        $this->assertTrue($defaults['sales_credit']['show_logo']);
        $this->assertTrue($defaults['sales_credit']['show_company_data']);
        $this->assertTrue($defaults['sales_credit']['show_seller_data']);
        $this->assertTrue($defaults['sales_credit']['show_seller_banks']);
        $this->assertTrue($defaults['sales_credit']['show_subtotal']);
        $this->assertTrue($defaults['sales_credit']['show_tax']);
        $this->assertTrue($defaults['sales_credit']['show_signature_box']);
        $this->assertTrue($defaults['sales_credit']['show_amount_in_words']);
        $this->assertTrue($defaults['sales_credit']['show_notes']);
        $this->assertTrue($defaults['sales_credit']['show_qr']);
        $this->assertTrue($defaults['sales_credit']['show_footer_code']);

        // Verify orders keys
        $this->assertTrue($defaults['orders']['show_logo']);
        $this->assertTrue($defaults['orders']['show_company_data']);
        $this->assertTrue($defaults['orders']['show_seller_data']);
        $this->assertTrue($defaults['orders']['show_seller_banks']);
        $this->assertTrue($defaults['orders']['show_subtotal']);
        $this->assertTrue($defaults['orders']['show_tax']);
        $this->assertTrue($defaults['orders']['show_signature_box']);
        $this->assertTrue($defaults['orders']['show_amount_in_words']);
        $this->assertTrue($defaults['orders']['show_notes']);
        $this->assertTrue($defaults['orders']['show_qr']);
        $this->assertTrue($defaults['orders']['show_footer_code']);

        // Verify purchase_orders keys
        $this->assertTrue($defaults['purchase_orders']['show_logo']);
        $this->assertTrue($defaults['purchase_orders']['show_company_data']);
        $this->assertTrue($defaults['purchase_orders']['show_subtotal']);
        $this->assertTrue($defaults['purchase_orders']['show_tax']);
        $this->assertTrue($defaults['purchase_orders']['show_signature_box']);
        $this->assertTrue($defaults['purchase_orders']['show_amount_in_words']);
        $this->assertTrue($defaults['purchase_orders']['show_notes']);
        $this->assertTrue($defaults['purchase_orders']['show_footer_code']);

        // Verify debit_notes keys
        $this->assertTrue($defaults['debit_notes']['show_logo']);
        $this->assertTrue($defaults['debit_notes']['show_company_data']);
        $this->assertTrue($defaults['debit_notes']['show_subtotal']);
        $this->assertTrue($defaults['debit_notes']['show_tax']);
        $this->assertTrue($defaults['debit_notes']['show_signature_box']);
        $this->assertTrue($defaults['debit_notes']['show_amount_in_words']);
        $this->assertTrue($defaults['debit_notes']['show_notes']);
        $this->assertTrue($defaults['debit_notes']['show_footer_code']);

        // Verify reports keys
        $this->assertTrue($defaults['reports']['show_logo']);
        $this->assertTrue($defaults['reports']['show_company_data']);
        $this->assertTrue($defaults['reports']['show_footer_timestamp']);
    }

    /** @test */
    public function get_pdf_setting_returns_default_when_settings_are_empty_or_unset()
    {
        $config = new Configuration();
        $config->pdf_settings = null;

        $this->assertTrue($config->getPdfSetting('sales_paid', 'show_logo', true));
        $this->assertTrue($config->getPdfSetting('sales_paid', 'show_company_data', true));
        $this->assertTrue($config->getPdfSetting('sales_paid', 'show_footer_code', true));
        $this->assertFalse($config->getPdfSetting('sales_paid', 'non_existing_key', false));
    }

    /** @test */
    public function get_pdf_setting_returns_configured_values_correctly()
    {
        $config = new Configuration();
        $config->pdf_settings = [
            'sales_paid' => [
                'show_logo' => false,
                'show_company_data' => false,
                'show_seller_data' => true,
                'show_seller_banks' => false,
                'show_subtotal' => true,
                'show_tax' => false,
                'show_signature_box' => false,
                'show_amount_in_words' => false,
                'show_notes' => false,
                'show_qr' => false,
                'show_footer_code' => false,
            ],
            'orders' => [
                'show_logo' => true,
                'show_company_data' => false,
                'show_seller_data' => false,
                'show_seller_banks' => false,
                'show_subtotal' => false,
                'show_tax' => false,
                'show_signature_box' => false,
                'show_amount_in_words' => false,
                'show_notes' => false,
                'show_qr' => false,
                'show_footer_code' => false,
            ],
        ];

        // sales_paid assertions
        $this->assertFalse($config->getPdfSetting('sales_paid', 'show_logo', true));
        $this->assertFalse($config->getPdfSetting('sales_paid', 'show_company_data', true));
        $this->assertTrue($config->getPdfSetting('sales_paid', 'show_seller_data', true));
        $this->assertFalse($config->getPdfSetting('sales_paid', 'show_seller_banks', true));
        $this->assertTrue($config->getPdfSetting('sales_paid', 'show_subtotal', true));
        $this->assertFalse($config->getPdfSetting('sales_paid', 'show_tax', true));
        $this->assertFalse($config->getPdfSetting('sales_paid', 'show_signature_box', true));
        $this->assertFalse($config->getPdfSetting('sales_paid', 'show_amount_in_words', true));
        $this->assertFalse($config->getPdfSetting('sales_paid', 'show_notes', true));
        $this->assertFalse($config->getPdfSetting('sales_paid', 'show_qr', true));
        $this->assertFalse($config->getPdfSetting('sales_paid', 'show_footer_code', true));

        // orders assertions
        $this->assertTrue($config->getPdfSetting('orders', 'show_logo', true));
        $this->assertFalse($config->getPdfSetting('orders', 'show_company_data', true));
        $this->assertFalse($config->getPdfSetting('orders', 'show_footer_code', true));

        // Unconfigured category falls back to default
        $this->assertTrue($config->getPdfSetting('purchase_orders', 'show_company_data', true));
    }

    /** @test */
    public function livewire_settings_loads_and_persists_pdf_settings()
    {
        $config = Configuration::first();
        if (!$config) {
            $config = Configuration::create([
                'business_name' => 'EMPRESA TEST',
                'address' => 'CALLE 123',
                'city' => 'CIUDAD',
                'taxpayer_id' => 'J-12345678',
                'vat' => 16,
                'decimals' => 2,
                'printer_name' => 'POS-58',
                'credit_days' => 30,
                'pdf_settings' => null,
            ]);
        } else {
            $config->update(['pdf_settings' => null]);
        }

        $user = User::first() ?? User::factory()->create();

        $component = Livewire::actingAs($user)
            ->test(Settings::class)
            ->assertSet('tab', 1);

        // Check that pdfSettings are loaded with defaults
        $pdfSettings = $component->get('pdfSettings');
        $this->assertIsArray($pdfSettings);
        $this->assertTrue($pdfSettings['sales_paid']['show_logo']);
        $this->assertTrue($pdfSettings['sales_paid']['show_company_data']);
        $this->assertTrue($pdfSettings['sales_paid']['show_footer_code']);

        // Toggle some settings
        $component->set('pdfSettings.sales_paid.show_company_data', false)
            ->set('pdfSettings.sales_paid.show_logo', false)
            ->set('pdfSettings.sales_paid.show_footer_code', false)
            ->set('pdfSettings.orders.show_seller_banks', false)
            ->set('pdfSettings.purchase_orders.show_signature_box', false)
            ->call('saveConfig');

        // Verify DB persistence
        $config->refresh();
        $saved = $config->pdf_settings;
        if (is_string($saved)) {
            $saved = json_decode($saved, true);
        }

        $this->assertIsArray($saved);
        $this->assertFalse($saved['sales_paid']['show_company_data']);
        $this->assertFalse($saved['sales_paid']['show_logo']);
        $this->assertFalse($saved['sales_paid']['show_footer_code']);
        $this->assertFalse($saved['orders']['show_seller_banks']);
        $this->assertFalse($saved['purchase_orders']['show_signature_box']);
        $this->assertTrue($saved['sales_credit']['show_company_data']);
    }
}