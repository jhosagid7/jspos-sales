<?php

namespace Tests\Unit\Fiscal;

use App\Services\Fiscal\FiscalCommandBuilder;
use App\Services\Fiscal\ValueObjects\FiscalItem;
use App\Services\Fiscal\ValueObjects\FiscalPayment;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

/**
 * Expected strings are taken from the official SDK samples (Factura.txt,
 * Nota de Crédito.txt, Nota de Débito.txt) and Manual V8.5.0 §14.
 */
class FiscalCommandBuilderTest extends TestCase
{
    private FiscalCommandBuilder $b;

    protected function setUp(): void
    {
        parent::setUp();
        $this->b = new FiscalCommandBuilder();
    }

    public function test_invoice_header_matches_sdk_sample(): void
    {
        $this->assertSame(['iR*14.547.292', 'iS*Dany Méndez'], $this->b->invoiceHeader('Dany Méndez', '14.547.292'));
    }

    public function test_header_strips_line_breaks_and_truncates(): void
    {
        $h = $this->b->invoiceHeader("ACME\nC.A." . str_repeat('X', 60), 'J-1');
        $this->assertStringNotContainsString("\n", $h[1]);
        $this->assertLessThanOrEqual(3 + 40, mb_strlen($h[1]));
    }

    /** @dataProvider itemSamples */
    public function test_invoice_items_match_sdk_sample(string $tax, string $expected): void
    {
        $item = new FiscalItem('PRODUCTO', 5.00, 1.0, $tax);
        $this->assertSame($expected, $this->b->invoiceItem($item));
    }

    public static function itemSamples(): array
    {
        return [
            'general'   => [FiscalItem::TAX_GENERAL,   '!000000050000001000PRODUCTO'],
            'reducida'  => [FiscalItem::TAX_REDUCIDA,  '"000000050000001000PRODUCTO'],
            'adicional' => [FiscalItem::TAX_ADICIONAL, '#000000050000001000PRODUCTO'],
            'exento'    => [FiscalItem::TAX_EXENTO,    ' 000000050000001000PRODUCTO'],
        ];
    }

    public function test_item_encodes_decimals_and_quantity(): void
    {
        // price 15.50 -> 0000001550 ; qty 3.5 -> 00003500
        $this->assertSame('!000000155000003500Jamon', $this->b->invoiceItem(new FiscalItem('Jamon', 15.50, 3.5)));
    }

    public function test_item_rejects_overflow_and_bad_values(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->b->invoiceItem(new FiscalItem('X', 999999999.99, 1.0)); // > 8 integer digits
    }

    public function test_item_rejects_zero_quantity(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->b->invoiceItem(new FiscalItem('X', 1.0, 0.0));
    }

    public function test_credit_items_match_sdk_sample(): void
    {
        $this->assertSame('d1000000050000001000PRODUCTO TASA GRAL',
            $this->b->creditItem(new FiscalItem('PRODUCTO TASA GRAL', 5.0, 1.0, FiscalItem::TAX_GENERAL)));
        $this->assertSame('d0000000030000001000PRODUCTO EXENTO',
            $this->b->creditItem(new FiscalItem('PRODUCTO EXENTO', 3.0, 1.0, FiscalItem::TAX_EXENTO)));
    }

    public function test_debit_items_use_backtick_prefix_like_sdk_sample(): void
    {
        $this->assertSame('`2000000070000001000PRODUCTO TASA REDU',
            $this->b->debitItem(new FiscalItem('PRODUCTO TASA REDU', 7.0, 1.0, FiscalItem::TAX_REDUCIDA)));
    }

    public function test_note_header_matches_sdk_sample_order(): void
    {
        $h = $this->b->noteHeader('Pedro Pablo Leon', '12345678', '00000000001', '14-07-2015', 'ZPA2000343');
        $this->assertSame(
            ['iR*12345678', 'iS*Pedro Pablo Leon', 'iF*00000000001', 'iD*14-07-2015', 'iI*ZPA2000343'],
            $h
        );
    }

    public function test_note_header_normalizes_dates(): void
    {
        $h = $this->b->noteHeader('A', '1', '1', '2026-11-15', 'Z1');
        $this->assertSame('iD*15-11-2026', $h[3]);
    }

    public function test_note_header_requires_original_data(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->b->noteHeader('A', '1', '', '14-07-2015', 'Z1');
    }

    public function test_comment_prefixes(): void
    {
        $this->assertSame('@PROMO', $this->b->comment('PROMO'));
        $this->assertSame('ACOMENTARIO', $this->b->comment('COMENTARIO', FiscalCommandBuilder::COMMENT_CREDIT));
        $this->assertSame('BCOMENTARIO', $this->b->comment('COMENTARIO', FiscalCommandBuilder::COMMENT_DEBIT));
    }

    public function test_discounts_match_manual(): void
    {
        $this->assertSame('p-1000', $this->b->discountPercent(10.0));
        $this->assertSame('q-000001000', $this->b->discountAmount(10.0));
    }

    public function test_invalid_discount_percent_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->b->discountPercent(100.0);
    }

    public function test_single_payment_is_direct_without_igtf(): void
    {
        $this->assertSame(['101'], $this->b->payments([new FiscalPayment('01', 100.0)], false));
    }

    public function test_single_payment_with_igtf_adds_199(): void
    {
        $this->assertSame(['122', '199'], $this->b->payments([new FiscalPayment('22', 100.0)], true));
    }

    public function test_mixed_payments_follow_manual_example(): void
    {
        // Manual 14.2: partial 01 (20.62), partial 05... direct 22, then 199
        $lines = $this->b->payments([
            new FiscalPayment('01', 20.62),
            new FiscalPayment('11', 51.00),
            new FiscalPayment('22', 103.00),
        ], true);

        $this->assertSame(['201000000002062', '211000000005100', '122', '199'], $lines);
    }

    public function test_payment_media_is_validated(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->b->payments([new FiscalPayment('99', 1.0)], false);
    }

    public function test_payments_cannot_be_empty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->b->payments([], false);
    }

    public function test_reports_and_status(): void
    {
        $this->assertSame('I0X', $this->b->xReport());
        $this->assertSame('I0Z', $this->b->zReport());
        $this->assertSame('S1', $this->b->statusRequest());
        $this->assertSame('3', $this->b->subtotal());
    }
}
