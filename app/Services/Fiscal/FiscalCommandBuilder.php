<?php

namespace App\Services\Fiscal;

use App\Services\Fiscal\ValueObjects\FiscalItem;
use App\Services\Fiscal\ValueObjects\FiscalPayment;
use InvalidArgumentException;

/**
 * Builds the command lines of the TFHKA fiscal protocol (library / "SendFileCmd" format).
 *
 * Verified against:
 *   - Manual de Protocolos y Comandos Venezuela V8.5.0 (tablas 28-30, ejemplos 14.2/14.4)
 *   - SDK PHP Windows: Factura.txt, Nota de Crédito.txt, Nota de Débito.txt, index.php
 *
 * Assumes printer FLAG 21 = 00 (price 8.2 -> 10 digits, quantity 5.3 -> 8 digits,
 * partial-payment amount 10.2 -> 12 digits). Pure functions: no I/O, no DB.
 *
 * Document layout (there is NO "open document" command; the first item opens it):
 *   Invoice : iR*, iS*, [i01..], [@comment], items (+discounts), [3 subtotal], payments, [199]
 *   Credit  : iR*, iS*, iF*, iD*, iI*, [Acomment], items d0-d4, payments, [199]
 *   Debit   : iR*, iS*, iF*, iD*, iI*, [Bcomment], items `0-`4, payments, [199]
 *   Command 199 closes the document and is MANDATORY when flag 50 = 01 (IGTF).
 */
final class FiscalCommandBuilder
{
    public const COMMENT_INVOICE = '@';
    public const COMMENT_CREDIT  = 'A';
    public const COMMENT_DEBIT   = 'B';

    private const CREDIT_PREFIX = [
        FiscalItem::TAX_EXENTO    => 'd0',
        FiscalItem::TAX_GENERAL   => 'd1',
        FiscalItem::TAX_REDUCIDA  => 'd2',
        FiscalItem::TAX_ADICIONAL => 'd3',
    ];

    private const DEBIT_PREFIX = [
        FiscalItem::TAX_EXENTO    => '`0',
        FiscalItem::TAX_GENERAL   => '`1',
        FiscalItem::TAX_REDUCIDA  => '`2',
        FiscalItem::TAX_ADICIONAL => '`3',
    ];

    // ------------------------------------------------------------------ headers

    /** @return string[] */
    public function invoiceHeader(string $customerName, string $customerId): array
    {
        return [
            'iR*' . $this->clean($customerId, 20),
            'iS*' . $this->clean($customerName, 40),
        ];
    }

    /**
     * Header for credit AND debit notes. All five fields are mandatory.
     *
     * @param string $invoiceDate Original document date, any parseable date or DD-MM-YYYY
     * @return string[]
     */
    public function noteHeader(
        string $customerName,
        string $customerId,
        string $originalNumber,
        string $originalDate,
        string $printerSerial,
    ): array {
        if (trim($originalNumber) === '' || trim($printerSerial) === '' || trim($originalDate) === '') {
            throw new InvalidArgumentException('Credit/debit notes require original number, date and printer serial.');
        }

        return [
            'iR*' . $this->clean($customerId, 20),
            'iS*' . $this->clean($customerName, 40),
            'iF*' . $this->clean($originalNumber, 11),
            'iD*' . $this->formatDate($originalDate),
            'iI*' . $this->clean($printerSerial, 15),
        ];
    }

    /** Free-text comment line. $type: COMMENT_INVOICE | COMMENT_CREDIT | COMMENT_DEBIT. */
    public function comment(string $text, string $type = self::COMMENT_INVOICE): string
    {
        return $type . $this->clean($text, 40);
    }

    // -------------------------------------------------------------------- items

    public function invoiceItem(FiscalItem $item, int $maxName = 40): string
    {
        return $item->taxType . $this->itemBody($item, $maxName);
    }

    public function creditItem(FiscalItem $item, int $maxName = 40): string
    {
        $prefix = self::CREDIT_PREFIX[$item->taxType]
            ?? throw new InvalidArgumentException("Invalid tax type for credit note: '{$item->taxType}'");

        return $prefix . $this->itemBody($item, $maxName);
    }

    public function debitItem(FiscalItem $item, int $maxName = 40): string
    {
        $prefix = self::DEBIT_PREFIX[$item->taxType]
            ?? throw new InvalidArgumentException("Invalid tax type for debit note: '{$item->taxType}'");

        return $prefix . $this->itemBody($item, $maxName);
    }

    /** Percentage discount on the previous item, e.g. 10.0 => "p-1000". */
    public function discountPercent(float $percent): string
    {
        if ($percent <= 0 || $percent >= 100) {
            throw new InvalidArgumentException('Discount percent must be between 0 and 100 (exclusive).');
        }

        return 'p-' . $this->digits($percent * 100, 4);
    }

    /** Amount discount on the previous item, e.g. 10.00 => "q-000001000". */
    public function discountAmount(float $amount): string
    {
        return 'q-' . $this->digits($amount * 100, 9);
    }

    public function subtotal(): string
    {
        return '3';
    }

    // ----------------------------------------------------------------- payments

    /**
     * Payment lines for a document: partial payments (2) for all but the last,
     * direct payment (1) for the last one, then 199 when IGTF/flag 50 is active.
     *
     * @param FiscalPayment[] $payments
     * @return string[]
     */
    public function payments(array $payments, bool $igtfEnabled): array
    {
        $payments = array_values($payments);
        if ($payments === []) {
            throw new InvalidArgumentException('At least one payment is required.');
        }

        $lines = [];
        $last  = count($payments) - 1;
        foreach ($payments as $i => $payment) {
            $lines[] = $i === $last
                ? '1' . $this->media($payment->mediaCode)
                : '2' . $this->media($payment->mediaCode) . $this->digits($payment->amountVes * 100, 12);
        }

        if ($igtfEnabled) {
            $lines[] = '199';
        }

        return $lines;
    }

    // ------------------------------------------------------------ reports/status

    public function xReport(): string
    {
        return 'I0X';
    }

    /** Closes the fiscal day. Irreversible. */
    public function zReport(): string
    {
        return 'I0Z';
    }

    /** Status request name understood by IntTFHKA UploadStatusCmd / TfhkaNet. */
    public function statusRequest(int $n = 1): string
    {
        return 'S' . $n;
    }

    // ------------------------------------------------------------------ helpers

    private function itemBody(FiscalItem $item, int $maxName): string
    {
        if ($item->quantity <= 0 || $item->unitPrice < 0) {
            throw new InvalidArgumentException("Invalid quantity/price for item '{$item->name}'.");
        }

        return $this->digits($item->unitPrice * 100, 10)
            . $this->digits($item->quantity * 1000, 8)
            . $this->clean($item->name, $maxName);
    }

    /** Zero-padded integer; throws on overflow/negative instead of silently truncating. */
    private function digits(float $scaled, int $width): string
    {
        $n = (int) round($scaled);
        if ($n < 0 || strlen((string) $n) > $width) {
            throw new InvalidArgumentException("Value out of range for a {$width}-digit field.");
        }

        return str_pad((string) $n, $width, '0', STR_PAD_LEFT);
    }

    private function media(string $code): string
    {
        if (!preg_match('/^(0[1-9]|1[0-9]|2[0-4])$/', $code)) {
            throw new InvalidArgumentException("Invalid payment media code '{$code}' (01-24).");
        }

        return $code;
    }

    private function formatDate(string $date): string
    {
        if (preg_match('/^\d{2}-\d{2}-\d{4}$/', $date)) {
            return $date;
        }
        $ts = strtotime(str_replace('/', '-', $date));
        if ($ts === false) {
            throw new InvalidArgumentException("Unparseable date '{$date}'.");
        }

        return date('d-m-Y', $ts);
    }

    /** Strip control chars and line breaks (they would split the command), then truncate. */
    private function clean(string $text, int $max): string
    {
        $text = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $text) ?? '';

        return trim(mb_substr(trim($text), 0, $max));
    }
}
