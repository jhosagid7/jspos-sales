<?php

namespace Tests\Unit\Fiscal;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\Fiscal\Drivers\FakeDriver;
use App\Services\Fiscal\FiscalPrinterService;
use App\Services\Fiscal\ValueObjects\FiscalItem;
use App\Services\Fiscal\ValueObjects\FiscalPayment;
use RuntimeException;
use Tests\TestCase;

/** Uses only unsaved models (no DB access): update() on a non-persisted model is a no-op. */
class FiscalPrinterServiceTest extends TestCase
{
    private function items(): array
    {
        return [new FiscalItem('Harina', 10.0, 1.0)];
    }

    private function pays(): array
    {
        return [new FiscalPayment('01', 11.6)];
    }

    public function test_print_invoice_sends_document_and_returns_s1(): void
    {
        $fake = new FakeDriver();
        $fake->setLastInvoice(1324);
        $svc  = new FiscalPrinterService($fake);

        $s1 = $svc->printInvoice(new Sale(), $this->items(), $this->pays());

        $this->assertSame('00001325', $s1['last_invoice']);
        $this->assertSame('ZKF0000001', $s1['serial']);
        $this->assertSame('!000000100000001000Harina', $fake->sentCommands()[2]);
        $sent = $fake->sentCommands();
        $this->assertSame('101', end($sent));
    }

    public function test_offline_printer_blocks_printing(): void
    {
        $fake = new FakeDriver();
        $fake->goOffline();

        $this->expectException(RuntimeException::class);
        (new FiscalPrinterService($fake))->printInvoice(new Sale(), $this->items(), $this->pays());
    }

    public function test_rejected_document_throws_and_is_not_marked_printed(): void
    {
        $fake = new FakeDriver();
        $fake->forceError('send', 'NAK');

        $this->expectException(RuntimeException::class);
        (new FiscalPrinterService($fake))->printInvoice(new Sale(), $this->items(), $this->pays());
    }

    public function test_unreadable_s1_after_print_throws(): void
    {
        $fake = new FakeDriver();
        $fake->forceError('status', 'timeout');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('S1 could not be read');
        (new FiscalPrinterService($fake))->printInvoice(new Sale(), $this->items(), $this->pays());
    }

    public function test_credit_note_requires_fiscal_data_on_original_sale(): void
    {
        $return = new SaleReturn();
        $return->setRelation('sale', new Sale()); // never printed fiscally

        $this->expectException(\InvalidArgumentException::class);
        (new FiscalPrinterService(new FakeDriver()))->printCreditNote($return, $this->items(), $this->pays());
    }

    public function test_credit_note_references_original_invoice(): void
    {
        $sale = new Sale();
        $sale->forceFill([
            'fiscal_number'   => '00001325',
            'fiscal_serial'   => 'Z1F9999988',
            'fiscal_datetime' => '2026-11-15 10:00:00',
        ]);
        $return = new SaleReturn();
        $return->setRelation('sale', $sale);

        $fake = new FakeDriver();
        (new FiscalPrinterService($fake))->printCreditNote($return, $this->items(), $this->pays());

        $this->assertContains('iF*00001325', $fake->sentCommands());
        $this->assertContains('iD*15-11-2026', $fake->sentCommands());
        $this->assertContains('iI*Z1F9999988', $fake->sentCommands());
        $this->assertContains('d1000000100000001000Harina', $fake->sentCommands());
    }

    public function test_reports_use_documented_commands(): void
    {
        $fake = new FakeDriver();
        $svc  = new FiscalPrinterService($fake);
        $svc->printXReport();
        $svc->printZReport();

        $this->assertSame(['I0X', 'I0Z'], $fake->sentCommands());
    }
}
