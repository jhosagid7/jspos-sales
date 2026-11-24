<?php

namespace Tests\Unit\Fiscal;

use App\Services\Fiscal\Drivers\FakeDriver;
use App\Services\Fiscal\FiscalDocumentComposer;
use App\Services\Fiscal\ValueObjects\FiscalItem;
use App\Services\Fiscal\ValueObjects\FiscalPayment;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FiscalDocumentComposerTest extends TestCase
{
    private FiscalDocumentComposer $c;

    protected function setUp(): void
    {
        parent::setUp();
        $this->c = new FiscalDocumentComposer();
    }

    public function test_invoice_layout_without_igtf(): void
    {
        $lines = $this->c->invoice(
            'Cliente SA', 'J-123',
            [new FiscalItem('Harina', 10.0, 1.0, FiscalItem::TAX_GENERAL, 10.0)],
            [new FiscalPayment('01', 11.6)],
            false,
        );

        $this->assertSame([
            'iR*J-123',
            'iS*Cliente SA',
            '!000000100000001000Harina',
            'p-1000',
            '101',
        ], $lines);
    }

    public function test_invoice_with_igtf_ends_with_199(): void
    {
        $lines = $this->c->invoice('A', '1', [new FiscalItem('X', 1.0, 1.0)], [new FiscalPayment('20', 1.0)], true);
        $this->assertSame('199', end($lines));
    }

    public function test_credit_note_layout_matches_sdk_sample(): void
    {
        $lines = $this->c->note(
            'credit', 'Pedro Pablo Leon', '12345678', '00000000001', '14-07-2015', 'ZPA2000343',
            [new FiscalItem('PRODUCTO TASA GRAL', 5.0, 1.0, FiscalItem::TAX_GENERAL)],
            [new FiscalPayment('01', 5.8)],
            false,
            comment: 'COMENTARIO NOTA DE CREDITO',
        );

        $this->assertSame([
            'iR*12345678',
            'iS*Pedro Pablo Leon',
            'iF*00000000001',
            'iD*14-07-2015',
            'iI*ZPA2000343',
            'ACOMENTARIO NOTA DE CREDITO',
            'd1000000050000001000PRODUCTO TASA GRAL',
            '101',
        ], $lines);
    }

    public function test_debit_note_uses_b_comment_and_backtick_items(): void
    {
        $lines = $this->c->note(
            'debit', 'C', '1', '1', '10-07-2015', 'Z1',
            [new FiscalItem('CARGO', 3.0, 1.0, FiscalItem::TAX_EXENTO)],
            [new FiscalPayment('01', 3.0)], false, comment: 'NOTA',
        );

        $this->assertContains('BNOTA', $lines);
        $this->assertContains('`0000000030000001000CARGO', $lines);
    }

    public function test_empty_items_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->c->invoice('A', '1', [], [new FiscalPayment('01', 1.0)], false);
    }

    public function test_unknown_note_kind_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->c->note('refund', 'A', '1', '1', '10-07-2015', 'Z1', [new FiscalItem('X', 1.0, 1.0)], [new FiscalPayment('01', 1.0)], false);
    }

    public function test_fake_driver_records_composed_batch(): void
    {
        $fake  = new FakeDriver();
        $lines = $this->c->invoice('A', '1', [new FiscalItem('X', 1.0, 1.0)], [new FiscalPayment('01', 1.16)], false);

        $resp = $fake->sendBatch($lines);

        $this->assertTrue($resp->success);
        $this->assertSame($lines, $fake->sentCommands());
    }
}
