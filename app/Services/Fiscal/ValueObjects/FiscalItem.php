<?php

namespace App\Services\Fiscal\ValueObjects;

/**
 * Immutable value object representing a single line item for a fiscal document.
 *
 * Tax type codes (TFHKA protocol):
 *   ' ' (space / 0x20) = Exento  (tax-exempt)
 *   '!'               = General  (Tasa1 = 16% IVA)
 *   '"'               = Reducida (Tasa2 = 8%  IVA)
 *   '#'               = Adicional(Tasa3 = 31% IVA)
 *   '$'               = Percibido(Tasa4 = IGTF, used only for media payments)
 *
 * Reference: Manual de Protocolos V8.5.0 §4.1 (item commands)
 */
final class FiscalItem
{
    // Tax type constants — these are the TFHKA protocol characters
    public const TAX_EXENTO    = ' '; // 0x20
    public const TAX_GENERAL   = '!'; // 16%
    public const TAX_REDUCIDA  = '"'; // 8%
    public const TAX_ADICIONAL = '#'; // 31%

    public function __construct(
        /** Product name (max 20 chars for HKA80, 40 for PP9). Will be truncated. */
        public readonly string $name,

        /** Unit price in VES (bolivares), 2 decimal places. */
        public readonly float $unitPrice,

        /** Quantity sold (up to 3 decimal places). */
        public readonly float $quantity,

        /**
         * Tax type character. Use class constants:
         * FiscalItem::TAX_EXENTO, TAX_GENERAL, TAX_REDUCIDA, TAX_ADICIONAL
         */
        public readonly string $taxType = self::TAX_GENERAL,

        /** Optional discount percentage (0-100). */
        public readonly float $discountPercent = 0.0,
    ) {}

    /** Total line amount before discount. */
    public function lineTotal(): float
    {
        return round($this->unitPrice * $this->quantity, 2);
    }

    /** Discount amount in currency. */
    public function discountAmount(): float
    {
        if ($this->discountPercent <= 0) {
            return 0.0;
        }
        return round($this->lineTotal() * ($this->discountPercent / 100), 2);
    }

    /** Net total (after discount). */
    public function netTotal(): float
    {
        return round($this->lineTotal() - $this->discountAmount(), 2);
    }

    /** Whether this item is tax-exempt. */
    public function isExempt(): bool
    {
        return $this->taxType === self::TAX_EXENTO;
    }
}
