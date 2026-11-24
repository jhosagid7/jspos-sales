<?php

namespace Tests\Unit\Fiscal;

use App\Services\Fiscal\FiscalCommandBuilder;
use App\Services\Fiscal\ValueObjects\FiscalItem;
use App\Services\Fiscal\ValueObjects\FiscalPayment;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for FiscalCommandBuilder.
 *
 * Tests verify that the DATA strings produced match the TFHKA protocol
 * exactly as documented in Manual de Protocolos V8.5.0 (2022).
 *
 * No I/O, no DB, no Laravel bootstrap needed — these are pure PHP tests.
 */
class FiscalCommandBuilderTest extends TestCase
{
    private FiscalCommandBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new FiscalCommandBuilder();
    }

    // =========================================================================
    // openInvoice
    // =========================================================================

    /** @test */
    public function open_invoice_starts_with_T01(): void
    {
        $cmd = $this->builder->openInvoice('EMPRESA EJEMPLO C.A.', 'J-12345678-9');
        $this->assertStringStartsWith('T01', $cmd);
    }

    /** @test */
    public function open_invoice_formats_rif_without_hyphens(): void
    {
        $cmd = $this->builder->openInvoice('EMPRESA EJEMPLO C.A.', 'J-12345678-9');
        // RIF should be uppercase, hyphens removed
        $this->assertStringContainsString('J123456789', $cmd);
    }

    /** @test */
    public function open_invoice_truncates_customer_name_to_40_chars(): void
    {
        $longName = str_repeat('A', 50);
        $cmd = $this->builder->openInvoice($longName, 'V-12345678');
        // After T01, the name portion should be max 40 chars
        $this->assertLessThanOrEqual(3 + 40 + 9, strlen($cmd)); // T01 + name + rif
    }

    // =========================================================================
    // openCreditNote / openDebitNote
    // =========================================================================

    /** @test */
    public function open_credit_note_starts_with_T41(): void
    {
        $cmd = $this->builder->openCreditNote('CLIENTE SA', 'J-98765432-1');
        $this->assertStringStartsWith('T41', $cmd);
    }

    /** @test */
    public function open_debit_note_starts_with_T61(): void
    {
        $cmd = $this->builder->openDebitNote('CLIENTE SA', 'J-98765432-1');
        $this->assertStringStartsWith('T61', $cmd);
    }

    // =========================================================================
    // buildNoteHeaders
    // =========================================================================

    /** @test */
    public function note_headers_returns_exactly_5_commands(): void
    {
        $headers = $this->builder->buildNoteHeaders(
            invoiceNumber: '00001234',
            invoiceDate:   '15/11/2026',
            printerSerial: 'Z1C0001234',
            customerId:    'J-12345678-9',
            customerName:  'EMPRESA EJEMPLO',
        );

        $this->assertCount(5, $headers);
    }

    /** @test */
    public function note_headers_have_correct_prefixes(): void
    {
        $headers = $this->builder->buildNoteHeaders(
            invoiceNumber: '00001234',
            invoiceDate:   '15/11/2026',
            printerSerial: 'Z1C0001234',
            customerId:    'J-12345678-9',
            customerName:  'EMPRESA EJEMPLO',
        );

        $this->assertStringStartsWith('iF*', $headers[0]); // invoice number
        $this->assertStringStartsWith('iD*', $headers[1]); // invoice date
        $this->assertStringStartsWith('iI*', $headers[2]); // printer serial
        $this->assertStringStartsWith('iR*', $headers[3]); // RIF
        $this->assertStringStartsWith('iS*', $headers[4]); // razón social
    }

    /** @test */
    public function note_headers_contain_invoice_number(): void
    {
        $headers = $this->builder->buildNoteHeaders(
            invoiceNumber: '00001234',
            invoiceDate:   '15/11/2026',
            printerSerial: 'Z1C0001234',
            customerId:    'J-12345678-9',
            customerName:  'EMPRESA EJEMPLO',
        );

        $this->assertSame('iF*00001234', $headers[0]);
        $this->assertSame('iD*15/11/2026', $headers[1]);
        $this->assertSame('iI*Z1C0001234', $headers[2]);
    }

    // =========================================================================
    // buildItemCommand
    // =========================================================================

    /** @test */
    public function item_command_for_general_tax_starts_with_exclamation(): void
    {
        $item = new FiscalItem('Producto Test', 100.00, 1.0, FiscalItem::TAX_GENERAL);
        $cmd  = $this->builder->buildItemCommand($item);
        $this->assertStringStartsWith('!', $cmd);
    }

    /** @test */
    public function item_command_for_exento_starts_with_space(): void
    {
        $item = new FiscalItem('Producto Exento', 50.00, 2.0, FiscalItem::TAX_EXENTO);
        $cmd  = $this->builder->buildItemCommand($item);
        $this->assertStringStartsWith(' ', $cmd); // 0x20
    }

    /** @test */
    public function item_command_for_reducida_starts_with_double_quote(): void
    {
        $item = new FiscalItem('Alimento Basico', 30.00, 1.0, FiscalItem::TAX_REDUCIDA);
        $cmd  = $this->builder->buildItemCommand($item);
        $this->assertStringStartsWith('"', $cmd);
    }

    /** @test */
    public function item_command_for_adicional_starts_with_hash(): void
    {
        $item = new FiscalItem('Articulo Lujo', 500.00, 1.0, FiscalItem::TAX_ADICIONAL);
        $cmd  = $this->builder->buildItemCommand($item);
        $this->assertStringStartsWith('#', $cmd);
    }

    /** @test */
    public function item_command_contains_at_separated_name_qty_price(): void
    {
        $item = new FiscalItem('COCA COLA 2L', 15.50, 3.0, FiscalItem::TAX_GENERAL);
        $cmd  = $this->builder->buildItemCommand($item);

        // Format: !{name}@{qty}@{price}
        $this->assertStringContainsString('@3.000@15.50', $cmd);
    }

    /** @test */
    public function item_command_truncates_name_to_max_length(): void
    {
        $item = new FiscalItem(str_repeat('X', 50), 10.00, 1.0);
        $cmd  = $this->builder->buildItemCommand($item, maxNameLength: 20);

        // Tax char + 20 name chars + @qty@price
        $parts = explode('@', substr($cmd, 1)); // remove tax char
        $this->assertLessThanOrEqual(20, strlen($parts[0]));
    }

    /** @test */
    public function item_command_allows_longer_name_for_pp9(): void
    {
        $item = new FiscalItem(str_repeat('Z', 50), 10.00, 1.0);
        $cmd  = $this->builder->buildItemCommand($item, maxNameLength: 40);

        $parts = explode('@', substr($cmd, 1));
        $this->assertLessThanOrEqual(40, strlen($parts[0]));
        $this->assertGreaterThan(20, strlen($parts[0])); // PP9 allows more
    }

    // =========================================================================
    // buildCreditItemCommand / buildDebitItemCommand
    // =========================================================================

    /** @test */
    public function credit_item_general_starts_with_d1(): void
    {
        $item = new FiscalItem('Devolucion Producto', 100.00, 1.0, FiscalItem::TAX_GENERAL);
        $cmd  = $this->builder->buildCreditItemCommand($item);
        $this->assertStringStartsWith('d1', $cmd);
    }

    /** @test */
    public function credit_item_exento_starts_with_d0(): void
    {
        $item = new FiscalItem('Devolucion Exenta', 50.00, 1.0, FiscalItem::TAX_EXENTO);
        $cmd  = $this->builder->buildCreditItemCommand($item);
        $this->assertStringStartsWith('d0', $cmd);
    }

    /** @test */
    public function debit_item_general_uses_tick1_prefix(): void
    {
        $item = new FiscalItem('Cargo Extra', 200.00, 1.0, FiscalItem::TAX_GENERAL);
        $cmd  = $this->builder->buildDebitItemCommand($item);
        $this->assertStringStartsWith("'1", $cmd);
    }

    // =========================================================================
    // buildDiscountCommand
    // =========================================================================

    /** @test */
    public function discount_command_starts_with_M(): void
    {
        $cmd = $this->builder->buildDiscountCommand(10.0);
        $this->assertStringStartsWith('M', $cmd);
    }

    /** @test */
    public function discount_command_contains_percentage(): void
    {
        $cmd = $this->builder->buildDiscountCommand(15.5, 'OFERTA');
        $this->assertStringContainsString('15.50', $cmd);
    }

    // =========================================================================
    // buildSubtotalCommand
    // =========================================================================

    /** @test */
    public function subtotal_command_is_uppercase_T(): void
    {
        $this->assertSame('T', $this->builder->buildSubtotalCommand());
    }

    // =========================================================================
    // buildPaymentCommand
    // =========================================================================

    /** @test */
    public function payment_command_without_igtf_starts_with_1(): void
    {
        $payment = new FiscalPayment(FiscalPayment::MEDIA_EFECTIVO_VES, 500.00);
        $cmd = $this->builder->buildPaymentCommand($payment, igtfEnabled: false);
        $this->assertStringStartsWith('1', $cmd);
        $this->assertStringNotContainsString('199', $cmd);
    }

    /** @test */
    public function payment_command_with_igtf_starts_with_199(): void
    {
        $payment = new FiscalPayment(FiscalPayment::MEDIA_EFECTIVO_USD, 500.00);
        $cmd = $this->builder->buildPaymentCommand($payment, igtfEnabled: true);
        $this->assertStringStartsWith('199', $cmd);
    }

    /** @test */
    public function payment_command_contains_media_code_and_amount(): void
    {
        $payment = new FiscalPayment(FiscalPayment::MEDIA_TRANSFERENCIA_VES, 1234.56);
        $cmd = $this->builder->buildPaymentCommand($payment, igtfEnabled: false);
        $this->assertStringContainsString('04', $cmd); // media code
        $this->assertStringContainsString('1234.56', $cmd); // amount
    }

    // =========================================================================
    // buildXReportCommand / buildZReportCommand
    // =========================================================================

    /** @test */
    public function x_report_command_is_uppercase_I(): void
    {
        $this->assertSame('I', $this->builder->buildXReportCommand());
    }

    /** @test */
    public function z_report_command_is_uppercase_Z(): void
    {
        $this->assertSame('Z', $this->builder->buildZReportCommand());
    }

    // =========================================================================
    // FiscalItem value object
    // =========================================================================

    /** @test */
    public function fiscal_item_calculates_line_total(): void
    {
        $item = new FiscalItem('Producto', 100.00, 3.0);
        $this->assertSame(300.0, $item->lineTotal());
    }

    /** @test */
    public function fiscal_item_calculates_discount_amount(): void
    {
        $item = new FiscalItem('Producto', 100.00, 2.0, FiscalItem::TAX_GENERAL, 10.0);
        $this->assertSame(20.0, $item->discountAmount());
    }

    /** @test */
    public function fiscal_item_calculates_net_total_after_discount(): void
    {
        $item = new FiscalItem('Producto', 100.00, 2.0, FiscalItem::TAX_GENERAL, 10.0);
        $this->assertSame(180.0, $item->netTotal());
    }

    /** @test */
    public function fiscal_item_identifies_exempt_tax_type(): void
    {
        $exento = new FiscalItem('Exento', 50.00, 1.0, FiscalItem::TAX_EXENTO);
        $general = new FiscalItem('General', 50.00, 1.0, FiscalItem::TAX_GENERAL);

        $this->assertTrue($exento->isExempt());
        $this->assertFalse($general->isExempt());
    }

    // =========================================================================
    // FiscalPayment value object
    // =========================================================================

    /** @test */
    public function foreign_currency_payment_is_identified_correctly(): void
    {
        $ves = new FiscalPayment(FiscalPayment::MEDIA_EFECTIVO_VES, 100.00);
        $usd = new FiscalPayment(FiscalPayment::MEDIA_EFECTIVO_USD, 100.00);

        $this->assertFalse($ves->isForeignCurrency());
        $this->assertTrue($usd->isForeignCurrency());
    }

    /** @test */
    public function zelle_payment_is_foreign_currency(): void
    {
        $zelle = new FiscalPayment(FiscalPayment::MEDIA_ZELLE, 50.00);
        $this->assertTrue($zelle->isForeignCurrency());
    }

    /** @test */
    public function usdt_payment_is_foreign_currency(): void
    {
        $usdt = new FiscalPayment(FiscalPayment::MEDIA_CRIPTO, 25.00);
        $this->assertTrue($usdt->isForeignCurrency());
    }
}
