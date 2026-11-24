<?php

namespace App\Services\Fiscal\ValueObjects;

/**
 * Immutable value object representing a payment method for a fiscal document.
 *
 * TFHKA media codes:
 *   01-19 = VES (bolivares) — does NOT trigger IGTF
 *   20-24 = Divisas (foreign currency) — TRIGGERS IGTF if flag 50=01
 *
 * Common codes:
 *   01 = Efectivo VES
 *   02 = Tarjeta de Débito/Crédito VES
 *   03 = Cheque VES
 *   04 = Transferencia VES
 *   20 = Efectivo USD / divisas
 *   21 = Tarjeta débito/crédito USD
 *   22 = Zelle / transferencia divisas
 *   23 = Criptomoneda (USDT, etc.)
 *   24 = Divisas varias
 *
 * Reference: Manual de Protocolos V8.5.0 §4.3 and IGTF guide §2.1
 */
final class FiscalPayment
{
    // VES payment media codes
    public const MEDIA_EFECTIVO_VES        = '01';
    public const MEDIA_DEBITO_VES          = '02';
    public const MEDIA_CHEQUE              = '03';
    public const MEDIA_TRANSFERENCIA_VES   = '04';

    // Foreign currency payment media codes (trigger IGTF if applicable)
    public const MEDIA_EFECTIVO_USD        = '20';
    public const MEDIA_TARJETA_USD         = '21';
    public const MEDIA_ZELLE              = '22';
    public const MEDIA_CRIPTO             = '23';
    public const MEDIA_DIVISA_VARIAS      = '24';

    public function __construct(
        /** TFHKA 2-digit media code (see constants above). */
        public readonly string $mediaCode,

        /** Amount in VES (bolivares). The printer converts to foreign currency using its programmed rate. */
        public readonly float $amountVes,
    ) {}

    /** Whether this payment method triggers IGTF (3% surcharge). */
    public function isForeignCurrency(): bool
    {
        return (int) $this->mediaCode >= 20;
    }

    /** Whether this is a VES payment. */
    public function isVes(): bool
    {
        return !$this->isForeignCurrency();
    }
}
