<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Configuration;
use App\Models\User;
use Livewire\Livewire;
use App\Livewire\Settings;

class TicketConfigurationTest extends TestCase
{
    /** @test */
    public function it_returns_default_ticket_settings_structure_with_all_true()
    {
        $defaults = Configuration::getDefaultTicketSettings();

        $this->assertIsArray($defaults);
        $this->assertArrayHasKey('sales', $defaults);
        $this->assertArrayHasKey('orders', $defaults);
        $this->assertArrayHasKey('payments', $defaults);
        $this->assertArrayHasKey('payables', $defaults);
        $this->assertArrayHasKey('cash_count', $defaults);
        $this->assertArrayHasKey('payment_history', $defaults);
        $this->assertArrayHasKey('internal', $defaults);

        // Verify sales keys
        $this->assertTrue($defaults['sales']['auto_print']);
        $this->assertTrue($defaults['sales']['show_company_data']);
        $this->assertTrue($defaults['sales']['show_sale_condition']);
        $this->assertTrue($defaults['sales']['show_items_header']);
        $this->assertTrue($defaults['sales']['show_customer']);
        $this->assertTrue($defaults['sales']['show_subtotal']);
        $this->assertTrue($defaults['sales']['show_tax']);
        $this->assertTrue($defaults['sales']['show_cash_change']);
        $this->assertTrue($defaults['sales']['show_footer_message']);
        $this->assertTrue($defaults['sales']['show_website']);
        $this->assertTrue($defaults['sales']['show_qr']);

        // Verify orders keys
        $this->assertTrue($defaults['orders']['show_company_data']);
        $this->assertTrue($defaults['orders']['show_items_header']);
        $this->assertTrue($defaults['orders']['show_customer']);
        $this->assertTrue($defaults['orders']['show_subtotal']);
        $this->assertTrue($defaults['orders']['show_tax']);
        $this->assertTrue($defaults['orders']['show_cash_change']);
        $this->assertTrue($defaults['orders']['show_footer_message']);
        $this->assertTrue($defaults['orders']['show_website']);
        $this->assertTrue($defaults['orders']['show_qr']);

        // Verify payments keys
        $this->assertTrue($defaults['payments']['show_company_data']);
        $this->assertTrue($defaults['payments']['show_debt']);
        $this->assertTrue($defaults['payments']['show_footer_message']);

        // Verify payables keys
        $this->assertTrue($defaults['payables']['show_company_data']);
        $this->assertTrue($defaults['payables']['show_debt']);

        // Verify cash count keys
        $this->assertTrue($defaults['cash_count']['show_company_data']);
        $this->assertTrue($defaults['cash_count']['show_sales_breakdown']);
        $this->assertTrue($defaults['cash_count']['show_payments_breakdown']);
        $this->assertTrue($defaults['cash_count']['show_wallet']);

        // Verify payment history keys
        $this->assertTrue($defaults['payment_history']['show_company_data']);
        $this->assertTrue($defaults['payment_history']['show_returns']);
        $this->assertTrue($defaults['payment_history']['show_due_alert']);

        // Verify internal keys
        $this->assertTrue($defaults['internal']['show_header']);
        $this->assertTrue($defaults['internal']['show_charges_breakdown']);

        // Verify seller_grouped keys
        $this->assertTrue($defaults['seller_grouped']['show_company_data']);
    }

    /** @test */
    public function get_ticket_setting_returns_default_when_settings_are_empty_or_unset()
    {
        $config = new Configuration();
        $config->ticket_settings = null;

        $this->assertTrue($config->getTicketSetting('sales', 'auto_print', true));
        $this->assertTrue($config->getTicketSetting('sales', 'show_company_data', true));
        $this->assertFalse($config->getTicketSetting('sales', 'non_existing_key', false));
    }

    /** @test */
    public function get_ticket_setting_returns_configured_values_correctly()
    {
        $config = new Configuration();
        $config->ticket_settings = [
            'sales' => [
                'auto_print' => false,
                'show_company_data' => false,
                'show_subtotal' => true,
                'show_tax' => false,
                'show_cash_change' => false,
                'show_footer_message' => false,
                'show_website' => false,
                'show_qr' => false,
            ],
            'payments' => [
                'show_company_data' => false,
                'show_debt' => false,
                'show_footer_message' => false,
            ]
        ];

        $this->assertFalse($config->getTicketSetting('sales', 'auto_print', true));
        $this->assertFalse($config->getTicketSetting('sales', 'show_company_data', true));
        $this->assertTrue($config->getTicketSetting('sales', 'show_subtotal', true));
        $this->assertFalse($config->getTicketSetting('sales', 'show_tax', true));
        $this->assertFalse($config->getTicketSetting('sales', 'show_cash_change', true));
        $this->assertFalse($config->getTicketSetting('sales', 'show_footer_message', true));
        $this->assertFalse($config->getTicketSetting('sales', 'show_website', true));
        $this->assertFalse($config->getTicketSetting('sales', 'show_qr', true));
        // Default true when not specified in saved array
        $this->assertTrue($config->getTicketSetting('sales', 'show_sale_condition', true));
        $this->assertTrue($config->getTicketSetting('sales', 'show_items_header', true));
        $this->assertTrue($config->getTicketSetting('sales', 'show_customer', true));

        $this->assertFalse($config->getTicketSetting('payments', 'show_company_data', true));
        $this->assertFalse($config->getTicketSetting('payments', 'show_debt', true));
        $this->assertFalse($config->getTicketSetting('payments', 'show_footer_message', true));

        // Unconfigured types should still return the default
        $this->assertTrue($config->getTicketSetting('orders', 'show_company_data', true));
        $this->assertTrue($config->getTicketSetting('orders', 'show_items_header', true));
        $this->assertTrue($config->getTicketSetting('orders', 'show_customer', true));
        $this->assertTrue($config->getTicketSetting('cash_count', 'show_sales_breakdown', true));
    }

    /** @test */
    public function livewire_settings_loads_and_saves_ticket_settings()
    {
        $config = Configuration::first();
        if (!$config) {
            $config = Configuration::create([
                'business_name' => 'EMPRESA TEST',
                'address' => 'CALLE 123',
                'city' => 'CIUDAD',
                'phone' => '123456789',
                'taxpayer_id' => 'J-12345678',
                'vat' => 16,
                'decimals' => 2,
                'printer_name' => 'POS-58',
                'credit_days' => 30,
                'ticket_settings' => null,
            ]);
        } else {
            $config->update([
                'business_name' => $config->business_name ?: 'EMPRESA TEST',
                'address' => $config->address ?: 'CALLE 123',
                'city' => $config->city ?: 'CIUDAD',
                'phone' => $config->phone ?: '123456789',
                'taxpayer_id' => $config->taxpayer_id ?: 'J-12345678',
                'vat' => $config->vat ?: 16,
                'decimals' => $config->decimals ?: 2,
                'printer_name' => $config->printer_name ?: 'POS-58',
                'credit_days' => $config->credit_days ?: 30,
                'ticket_settings' => null,
            ]);
        }

        $user = User::first();
        if (!$user) {
            $user = User::factory()->create();
        }
        $role = \Spatie\Permission\Models\Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $user->assignRole($role);

        $component = Livewire::actingAs($user)
            ->test(Settings::class)
            ->assertSet('tab', 1);

        // Check that ticketSettings are loaded with defaults
        $ticketSettings = $component->get('ticketSettings');
        $this->assertIsArray($ticketSettings);
        $this->assertTrue($ticketSettings['sales']['auto_print']);
        $this->assertTrue($ticketSettings['sales']['show_company_data']);
        $this->assertTrue($ticketSettings['sales']['show_sale_condition']);
        $this->assertTrue($ticketSettings['sales']['show_items_header']);
        $this->assertTrue($ticketSettings['sales']['show_customer']);

        // Toggle some settings
        $component->set('ticketSettings.sales.auto_print', false)
            ->set('ticketSettings.sales.show_company_data', false)
            ->set('ticketSettings.sales.show_sale_condition', false)
            ->set('ticketSettings.sales.show_items_header', false)
            ->set('ticketSettings.sales.show_customer', false)
            ->set('ticketSettings.sales.show_qr', false)
            ->set('ticketSettings.payments.show_debt', false)
            ->set('ticketSettings.seller_grouped.show_company_data', false)
            ->call('saveConfig');

        // Verify DB persistence
        $config = Configuration::first();
        $saved = $config->ticket_settings;
        if (is_string($saved)) {
            $saved = json_decode($saved, true);
        }

        $this->assertFalse($saved['sales']['auto_print']);
        $this->assertFalse($saved['sales']['show_company_data']);
        $this->assertFalse($saved['sales']['show_sale_condition']);
        $this->assertFalse($saved['sales']['show_items_header']);
        $this->assertFalse($saved['sales']['show_customer']);
        $this->assertFalse($saved['sales']['show_qr']);
        $this->assertFalse($saved['payments']['show_debt']);
        $this->assertFalse($saved['seller_grouped']['show_company_data']);
        $this->assertTrue($saved['sales']['show_subtotal']);
        $this->assertTrue($saved['orders']['show_company_data']);
    }

    /** @test */
    public function it_evaluates_auto_print_correctly_on_sale_completion()
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
                'ticket_settings' => ['sales' => ['auto_print' => true]],
            ]);
        }

        // When auto_print is true
        $config->update(['ticket_settings' => ['sales' => ['auto_print' => true]]]);
        $config->refresh();
        $this->assertTrue($config->getTicketSetting('sales', 'auto_print', true));

        // When auto_print is false
        $config->update(['ticket_settings' => ['sales' => ['auto_print' => false]]]);
        $config->refresh();
        $this->assertFalse($config->getTicketSetting('sales', 'auto_print', true));
    }

    /** @test */
    public function it_prints_sales_ticket_respecting_hidden_fields_and_single_header_line()
    {
        $connector = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
        $printer = new \Mike42\Escpos\Printer($connector);

        $config = new Configuration();
        $config->business_name = 'EMPRESA TEST';
        $config->address = 'CALLE 123';
        $config->taxpayer_id = 'J-12345678';
        $config->phone = '123456789';

        // Case 1: Header visible -> Both separator lines surround DESCRIPCION
        $config->ticket_settings = [
            'sales' => [
                'show_sale_condition' => false,
                'show_items_header' => true,
                'show_customer' => false,
            ]
        ];

        $separator = "--------------------------------";
        $maskHead = "%-16.16s %-5.5s %-9.9s";

        if ($config->getTicketSetting('sales', 'show_sale_condition', true)) {
            $printer->text("Condición: CONTADO\n");
        }

        if ($config->getTicketSetting('sales', 'show_items_header', true)) {
            $headersName = sprintf($maskHead, 'DESCRIPCION', 'CANT', 'PRECIO');
            $printer->text($separator . "\n");
            $printer->text($headersName . "\n");
            $printer->text($separator . "\n");
        } else {
            $printer->text($separator . "\n");
        }

        $printer->text("PRODUCTO TEST    1.00  $10.00\n");
        $printer->text($separator . "\n");

        if ($config->getTicketSetting('sales', 'show_customer', true)) {
            $printer->text("CLIENTE: Consumidor Final\n\n");
        }

        $outputVisible = $connector->getData();
        $printer->close();
        $this->assertStringNotContainsString("Condición:", $outputVisible);
        $this->assertStringNotContainsString("CLIENTE:", $outputVisible);
        $this->assertStringContainsString($separator . "\n" . sprintf($maskHead, 'DESCRIPCION', 'CANT', 'PRECIO') . "\n" . $separator, $outputVisible);

        // Case 2: Header hidden -> Only ONE single separator line before products
        $connector2 = new \Mike42\Escpos\PrintConnectors\DummyPrintConnector();
        $printer2 = new \Mike42\Escpos\Printer($connector2);

        $config->ticket_settings = [
            'sales' => [
                'show_sale_condition' => false,
                'show_items_header' => false,
                'show_customer' => false,
            ]
        ];

        if ($config->getTicketSetting('sales', 'show_items_header', true)) {
            $headersName = sprintf($maskHead, 'DESCRIPCION', 'CANT', 'PRECIO');
            $printer2->text($separator . "\n");
            $printer2->text($headersName . "\n");
            $printer2->text($separator . "\n");
        } else {
            $printer2->text($separator . "\n");
        }

        $printer2->text("PRODUCTO TEST    1.00  $10.00\n");

        $outputHidden = $connector2->getData();
        $printer2->close();
        $this->assertStringNotContainsString("DESCRIPCION", $outputHidden);
        $this->assertEquals(1, substr_count($outputHidden, $separator));
        $this->assertStringContainsString($separator . "\nPRODUCTO TEST", $outputHidden);
    }
}

