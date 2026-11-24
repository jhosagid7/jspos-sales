<?php

namespace App\Services\Fiscal;

use App\Models\Sale;
use App\Services\Fiscal\ValueObjects\FiscalItem;
use InvalidArgumentException;
use RuntimeException;

/**
 * Maps JSPOS sale lines to FiscalItem[] (what the printer must receive).
 *
 * Key facts:
 *  - JSPOS stores prices WITH tax included (subtotal = total / (1 + vat)).
 *    The printer wants the BASE price and adds the tax itself, so when
 *    $pricesIncludeTax is true the unit price is divided by (1 + rate).
 *  - The printer works in VES: $toVesRate converts from the sale currency.
 *  - Product::tax_type (exento|general|reducida|adicional) picks the rate;
 *    null falls back to `general`.
 *  - The legal total is the printer's, so reconcile() lets callers refuse to
 *    print when our total and the printer's estimate diverge.
 */
final class FiscalItemMapper
{
    public const DEFAULT_RATES = [
        'exento'    => 0.0,
        'general'   => 16.0,
        'reducida'  => 8.0,
        'adicional' => 31.0,
    ];

    private const CHAR = [
        'exento'    => FiscalItem::TAX_EXENTO,
        'general'   => FiscalItem::TAX_GENERAL,
        'reducida'  => FiscalItem::TAX_REDUCIDA,
        'adicional' => FiscalItem::TAX_ADICIONAL,
    ];

    /** @param array<string,float> $rates percent per tax type, must match printer programming (PT command) */
    public function __construct(
        private readonly array $rates = self::DEFAULT_RATES,
        private readonly bool $pricesIncludeTax = true,
        private readonly float $toVesRate = 1.0,
    ) {
        if ($toVesRate <= 0) {
            throw new InvalidArgumentException('Exchange rate to VES must be positive.');
        }
    }

    /** Resolve a Product::tax_type value (nullable) to a protocol tax type key. */
    public function resolveTaxType(?string $taxType): string
    {
        $taxType = $taxType === null || $taxType === '' ? 'general' : strtolower($taxType);
        if (!isset(self::CHAR[$taxType])) {
            throw new InvalidArgumentException("Unknown tax type '{$taxType}'.");
        }

        return $taxType;
    }

    public function mapLine(string $name, float $unitPrice, float $quantity, ?string $taxType, float $discountPercent = 0.0): FiscalItem
    {
        $type  = $this->resolveTaxType($taxType);
        $price = $unitPrice * $this->toVesRate;

        if ($this->pricesIncludeTax) {
            $price /= 1 + ($this->rates[$type] ?? 0.0) / 100;
        }

        return new FiscalItem(
            name: $name,
            unitPrice: round($price, 2),
            quantity: $quantity,
            taxType: self::CHAR[$type],
            discountPercent: $discountPercent,
        );
    }

    /** @return FiscalItem[] */
    public function fromSale(Sale $sale): array
    {
        $items = [];
        foreach ($sale->details as $detail) {
            $product = $detail->product;
            $items[] = $this->mapLine(
                $product?->name ?? 'PRODUCTO',
                (float) $detail->sale_price,
                (float) $detail->quantity,
                $product?->tax_type,
            );
        }

        if ($items === []) {
            throw new InvalidArgumentException("Sale #{$sale->id} has no lines to print.");
        }

        return $items;
    }

    /** Estimated printer total (base + tax - line discounts) in VES. */
    public function estimateTotal(array $items): float
    {
        $rateByChar = [];
        foreach (self::CHAR as $type => $char) {
            $rateByChar[$char] = $this->rates[$type] ?? 0.0;
        }

        $total = 0.0;
        foreach ($items as $item) {
            $net    = $item->netTotal();
            $total += $net + round($net * ($rateByChar[$item->taxType] ?? 0.0) / 100, 2);
        }

        return round($total, 2);
    }

    /**
     * Refuse to print when the printer's total would differ from the sale's.
     *
     * @throws RuntimeException when |estimate - expected| > $tolerance (VES)
     */
    public function reconcile(array $items, float $expectedTotalVes, float $tolerance = 0.05): float
    {
        $estimate = $this->estimateTotal($items);
        $diff     = round($estimate - $expectedTotalVes, 2);

        if (abs($diff) > $tolerance) {
            throw new RuntimeException(sprintf(
                'Fiscal total mismatch: printer would charge %.2f but the sale total is %.2f (diff %.2f).',
                $estimate, $expectedTotalVes, $diff
            ));
        }

        return $diff;
    }
}
