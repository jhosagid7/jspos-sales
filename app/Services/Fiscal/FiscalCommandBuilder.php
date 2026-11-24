<?php

namespace App\Services\Fiscal;

use App\Services\Fiscal\ValueObjects\FiscalItem;
use App\Services\Fiscal\ValueObjects\FiscalPayment;
use InvalidArgumentException;

/**
 * Builds raw DATA strings for the TFHKA fiscal printer protocol.
 *
 * Protocol reference: Manual de Protocolos y Comandos Venezuela V8.5.0 (2022)
 *
 * This class only produces strings — it has ZERO side effects (no I/O, no DB).
 * This makes it fully unit-testable without hardware.
 *
 * # Document flow:
 *   1. openDocument()         → "T01..." / "T41..." / "T61..."
 *   2. addItem() × N          → "<taxChar>..." per item
 *   3. addDiscount() (opt.)   → "M..." per item discount
 *   4. closeDocument()        → "T" (closes item section)
 *   5. addPayment() × N       → "199<media><amount>" (command 199 required when IGTF active)
 *   6. finalizeDocument()     → prints and stores
 *
 * For Credit Notes:
 *   openCreditNote() → requires iF* iD* iI* iR* iS* headers first
 *
 * For Debit Notes:
 *   openDebitNote() → same header requirements as credit note
 */
final class FiscalCommandBuilder
{
    // -------------------------------------------------------------------------
    // Document type prefixes (open-document commands)
    // -------------------------------------------------------------------------
    private const CMD_OPEN_INVOICE      = 'T01'; // Factura Fiscal
    private const CMD_OPEN_CREDIT_NOTE  = 'T41'; // Nota de Crédito
    private const CMD_OPEN_DEBIT_NOTE   = 'T61'; // Nota de Débito

    // -------------------------------------------------------------------------
    // Item tax type characters (literal protocol chars)
    // -------------------------------------------------------------------------
    public const TAX_EXENTO    = FiscalItem::TAX_EXENTO;    // ' '
    public const TAX_GENERAL   = FiscalItem::TAX_GENERAL;   // '!'
    public const TAX_REDUCIDA  = FiscalItem::TAX_REDUCIDA;  // '"'
    public const TAX_ADICIONAL = FiscalItem::TAX_ADICIONAL; // '#'

    // Credit note item chars: d0-d4 (d + taxIndex)
    private const CREDIT_TAX_MAP = [
        FiscalItem::TAX_EXENTO    => 'd0',
        FiscalItem::TAX_GENERAL   => 'd1',
        FiscalItem::TAX_REDUCIDA  => 'd2',
        FiscalItem::TAX_ADICIONAL => 'd3',
    ];

    // Debit note item chars: '0-'4
    private const DEBIT_TAX_MAP = [
        FiscalItem::TAX_EXENTO    => "'0",
        FiscalItem::TAX_GENERAL   => "'1",
        FiscalItem::TAX_REDUCIDA  => "'2",
        FiscalItem::TAX_ADICIONAL => "'3",
    ];

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Build the "open invoice" command.
     *
     * @param  string  $customerName  Razón social del cliente (max 40 chars)
     * @param  string  $customerId    RIF or Cédula (e.g. "J-12345678-9" or "V-12345678")
     */
    public function openInvoice(string $customerName, string $customerId): string
    {
        return self::CMD_OPEN_INVOICE
            . $this->padName($customerName)
            . $this->formatId($customerId);
    }

    /**
     * Build an "open credit note" command.
     * Must be preceded by the iF*, iD*, iI*, iR*, iS* header commands.
     *
     * @param  string  $customerName  Razón social del cliente
     * @param  string  $customerId    RIF or Cédula
     */
    public function openCreditNote(string $customerName, string $customerId): string
    {
        return self::CMD_OPEN_CREDIT_NOTE
            . $this->padName($customerName)
            . $this->formatId($customerId);
    }

    /**
     * Build an "open debit note" command.
     */
    public function openDebitNote(string $customerName, string $customerId): string
    {
        return self::CMD_OPEN_DEBIT_NOTE
            . $this->padName($customerName)
            . $this->formatId($customerId);
    }

    /**
     * Build credit/debit note header commands (OBLIGATORIO before open).
     *
     * @param  string  $invoiceNumber  Original fiscal invoice number
     * @param  string  $invoiceDate    Original invoice date (DD/MM/YYYY)
     * @param  string  $printerSerial  Serial number of printer that issued the invoice
     * @param  string  $customerId     RIF/CI of the customer on the original invoice
     * @param  string  $customerName   Razón social on the original invoice
     * @return array<string>  Array of header DATA strings to send in order
     */
    public function buildNoteHeaders(
        string $invoiceNumber,
        string $invoiceDate,
        string $printerSerial,
        string $customerId,
        string $customerName,
    ): array {
        return [
            'iF*' . $invoiceNumber,                // Original invoice number
            'iD*' . $invoiceDate,                  // Original invoice date
            'iI*' . $printerSerial,                // Printer serial that issued it
            'iR*' . $this->formatId($customerId),  // Customer RIF/CI
            'iS*' . $this->padName($customerName), // Customer razón social
        ];
    }

    /**
     * Build an item line command for a Factura Fiscal.
     *
     * @param  FiscalItem  $item
     * @param  int         $maxNameLength  Max name chars (20 for HKA80, 40 for PP9)
     * @return string  DATA string ready to send
     */
    public function buildItemCommand(FiscalItem $item, int $maxNameLength = 20): string
    {
        $name   = mb_substr($item->name, 0, $maxNameLength);
        $qty    = $this->formatQuantity($item->quantity);
        $price  = $this->formatAmount($item->unitPrice);
        $tax    = $item->taxType;

        // Format: {taxChar}{name}@{qty}@{price}
        return "{$tax}{$name}@{$qty}@{$price}";
    }

    /**
     * Build an item line command for a Nota de Crédito.
     */
    public function buildCreditItemCommand(FiscalItem $item, int $maxNameLength = 20): string
    {
        $prefix = self::CREDIT_TAX_MAP[$item->taxType]
            ?? throw new InvalidArgumentException("Invalid tax type for credit note: '{$item->taxType}'");

        $name  = mb_substr($item->name, 0, $maxNameLength);
        $qty   = $this->formatQuantity($item->quantity);
        $price = $this->formatAmount($item->unitPrice);

        return "{$prefix}{$name}@{$qty}@{$price}";
    }

    /**
     * Build an item line command for a Nota de Débito.
     */
    public function buildDebitItemCommand(FiscalItem $item, int $maxNameLength = 20): string
    {
        $prefix = self::DEBIT_TAX_MAP[$item->taxType]
            ?? throw new InvalidArgumentException("Invalid tax type for debit note: '{$item->taxType}'");

        $name  = mb_substr($item->name, 0, $maxNameLength);
        $qty   = $this->formatQuantity($item->quantity);
        $price = $this->formatAmount($item->unitPrice);

        return "{$prefix}{$name}@{$qty}@{$price}";
    }

    /**
     * Build a discount command for the current item.
     * Must be sent immediately after the item it applies to.
     *
     * @param  float   $discountPercent  0.01 - 99.99
     * @param  string  $description      Optional description (max 10 chars)
     */
    public function buildDiscountCommand(float $discountPercent, string $description = 'DESCUENTO'): string
    {
        $desc    = mb_substr($description, 0, 10);
        $percent = number_format($discountPercent, 2, '.', '');
        return "M{$desc}@{$percent}";
    }

    /**
     * Build the "subtotal" command that closes the item section.
     * Sent before payment commands.
     */
    public function buildSubtotalCommand(): string
    {
        return 'T';
    }

    /**
     * Build a payment command.
     *
     * When IGTF is active (flag 50 = 01), command 199 is MANDATORY to close
     * the invoice and correctly calculate the 3% surcharge on divisas.
     *
     * @param  FiscalPayment  $payment
     * @param  bool           $igtfEnabled  Whether the installation has IGTF active
     * @return string  DATA string
     */
    public function buildPaymentCommand(FiscalPayment $payment, bool $igtfEnabled = false): string
    {
        $amount = $this->formatAmount($payment->amountVes);

        if ($igtfEnabled) {
            // Command 199 is mandatory when flag 50=01
            return "199{$payment->mediaCode}{$amount}";
        }

        // Standard payment command (without IGTF)
        return "1{$payment->mediaCode}{$amount}";
    }

    /**
     * Build an X (interim) report command.
     * Prints current totals without closing the fiscal day.
     */
    public function buildXReportCommand(): string
    {
        return 'I';
    }

    /**
     * Build a Z (daily closure) report command.
     * CLOSES the fiscal day — irreversible. Increments Z counter.
     */
    public function buildZReportCommand(): string
    {
        return 'Z';
    }

    /**
     * Build the S1 status read command.
     */
    public function buildS1Command(): string
    {
        return 'S1';
    }

    /**
     * Build the S2 status read command.
     */
    public function buildS2Command(): string
    {
        return 'S2';
    }

    /**
     * Build the "print programming" command (useful for setup verification).
     * Prints all current flags, tax rates, and printer serial.
     */
    public function buildPrintProgrammingCommand(): string
    {
        return 'D';
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Pad/truncate customer name to 40 chars.
     * The printer field is fixed-width in some models.
     */
    private function padName(string $name): string
    {
        return mb_substr($name, 0, 40);
    }

    /**
     * Format RIF/CI: remove hyphens, uppercase.
     * Input:  "J-12345678-9" → Output: "J123456789"
     */
    private function formatId(string $id): string
    {
        return strtoupper(str_replace(['-', ' '], '', $id));
    }

    /**
     * Format quantity: 3 decimal places, no thousands separator.
     * Example: 1.5 → "1.500"
     */
    private function formatQuantity(float $qty): string
    {
        return number_format($qty, 3, '.', '');
    }

    /**
     * Format monetary amount: 2 decimal places, no thousands separator.
     * Example: 1234.5 → "1234.50"
     */
    private function formatAmount(float $amount): string
    {
        return number_format($amount, 2, '.', '');
    }
}
