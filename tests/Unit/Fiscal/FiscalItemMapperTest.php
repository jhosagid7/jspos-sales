<?php

namespace Tests\Unit\Fiscal;

use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Services\Fiscal\FiscalCommandBuilder;
use App\Services\Fiscal\FiscalItemMapper;
use App\Services\Fiscal\ValueObjects\FiscalItem;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class FiscalItemMapperTest extends TestCase
{
    public function test_tax_inclusive_price_is_converted_to_base_price(): void
    {
        $m    = new FiscalItemMapper(); // 16% included
        $item = $m->mapLine('Harina', 11.60, 2.0, 'general');

        $this->assertSame(10.00, $item->unitPrice);
        $this->assertSame(FiscalItem::TAX_GENERAL, $item->taxType);
    }

    public function test_exempt_items_keep_their_price(): void
    {
        $item = (new FiscalItemMapper())->mapLine('Arroz', 7.00, 1.0, 'exento');

        $this->assertSame(7.00, $item->unitPrice);
        $this->assertSame(FiscalItem::TAX_EXENTO, $item->taxType);
    }

    public function test_reduced_and_additional_rates(): void
    {
        $m = new FiscalItemMapper();
        $this->assertSame(10.00, $m->mapLine('A', 10.80, 1.0, 'reducida')->unitPrice);
        $this->assertSame(10.00, $m->mapLine('B', 13.10, 1.0, 'adicional')->unitPrice);
    }

    public function test_null_tax_type_defaults_to_general(): void
    {
        $this->assertSame(FiscalItem::TAX_GENERAL, (new FiscalItemMapper())->mapLine('X', 1.0, 1.0, null)->taxType);
    }

    public function test_unknown_tax_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new FiscalItemMapper())->mapLine('X', 1.0, 1.0, 'lujo');
    }

    public function test_prices_are_converted_to_ves(): void
    {
        $item = (new FiscalItemMapper(toVesRate: 40.0))->mapLine('X', 1.16, 1.0, 'general'); // 46.40 incl -> 40.00 base
        $this->assertSame(40.00, $item->unitPrice);
    }

    public function test_prices_not_including_tax_are_left_as_base(): void
    {
        $item = (new FiscalItemMapper(pricesIncludeTax: false))->mapLine('X', 10.0, 1.0, 'general');
        $this->assertSame(10.00, $item->unitPrice);
    }

    public function test_invalid_exchange_rate_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new FiscalItemMapper(toVesRate: 0);
    }

    public function test_estimate_total_adds_tax_per_rate(): void
    {
        $m     = new FiscalItemMapper();
        $items = [
            $m->mapLine('A', 11.60, 1.0, 'general'),  // 10.00 + 1.60
            $m->mapLine('B', 5.00, 2.0, 'exento'),    // 10.00
        ];

        $this->assertSame(21.60, $m->estimateTotal($items));
    }

    public function test_line_discount_reduces_estimate(): void
    {
        $m = new FiscalItemMapper();
        $items = [$m->mapLine('A', 11.60, 1.0, 'general', 10.0)]; // 10 - 1 = 9 + 1.44

        $this->assertSame(10.44, $m->estimateTotal($items));
    }

    public function test_reconcile_accepts_rounding_noise_and_rejects_real_gaps(): void
    {
        $m     = new FiscalItemMapper();
        $items = [$m->mapLine('A', 11.60, 1.0, 'general')];

        $this->assertSame(0.0, $m->reconcile($items, 11.60));
        $this->assertSame(0.01, $m->reconcile($items, 11.59));

        $this->expectException(RuntimeException::class);
        $m->reconcile($items, 15.00);
    }

    public function test_from_sale_uses_product_tax_type_and_quantities(): void
    {
        $general = (new Product())->forceFill(['name' => 'HARINA', 'tax_type' => 'general']);
        $exento  = (new Product())->forceFill(['name' => 'ARROZ', 'tax_type' => 'exento']);

        $d1 = (new SaleDetail())->forceFill(['sale_price' => 11.60, 'quantity' => 2]);
        $d1->setRelation('product', $general);
        $d2 = (new SaleDetail())->forceFill(['sale_price' => 3.00, 'quantity' => 1]);
        $d2->setRelation('product', $exento);

        $sale = new Sale();
        $sale->setRelation('details', collect([$d1, $d2]));

        $items = (new FiscalItemMapper())->fromSale($sale);

        $this->assertCount(2, $items);
        $this->assertSame(10.00, $items[0]->unitPrice);
        $this->assertSame(2.0, $items[0]->quantity);
        $this->assertSame(FiscalItem::TAX_EXENTO, $items[1]->taxType);
    }

    public function test_from_sale_without_lines_fails(): void
    {
        $sale = new Sale();
        $sale->setRelation('details', collect());

        $this->expectException(InvalidArgumentException::class);
        (new FiscalItemMapper())->fromSale($sale);
    }

    public function test_mapped_item_encodes_with_builder(): void
    {
        $item = (new FiscalItemMapper())->mapLine('HARINA', 11.60, 2.0, 'general');

        $this->assertSame('!000000100000002000HARINA', (new FiscalCommandBuilder())->invoiceItem($item));
    }
}
