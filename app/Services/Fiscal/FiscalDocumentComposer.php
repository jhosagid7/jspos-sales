<?php

namespace App\Services\Fiscal;

use App\Services\Fiscal\ValueObjects\FiscalItem;
use App\Services\Fiscal\ValueObjects\FiscalPayment;
use InvalidArgumentException;

/**
 * Pure composer: turns document data into the ordered list of command lines.
 * No I/O, no DB — fully unit-testable.
 */
final class FiscalDocumentComposer
{
    public function __construct(private readonly FiscalCommandBuilder $b = new FiscalCommandBuilder()) {}

    /**
     * @param FiscalItem[]    $items
     * @param FiscalPayment[] $payments
     * @return string[]
     */
    public function invoice(
        string $customerName,
        string $customerId,
        array $items,
        array $payments,
        bool $igtfEnabled,
        int $maxName = 40,
        ?string $comment = null,
    ): array {
        $this->requireItems($items);

        $lines = $this->b->invoiceHeader($customerName, $customerId);
        if ($comment !== null && $comment !== '') {
            $lines[] = $this->b->comment($comment, FiscalCommandBuilder::COMMENT_INVOICE);
        }

        foreach ($items as $item) {
            $lines[] = $this->b->invoiceItem($item, $maxName);
            if ($item->discountPercent > 0) {
                $lines[] = $this->b->discountPercent($item->discountPercent);
            }
        }

        return array_merge($lines, $this->b->payments($payments, $igtfEnabled));
    }

    /**
     * @param 'credit'|'debit' $kind
     * @param FiscalItem[]     $items
     * @param FiscalPayment[]  $payments
     * @return string[]
     */
    public function note(
        string $kind,
        string $customerName,
        string $customerId,
        string $originalNumber,
        string $originalDate,
        string $printerSerial,
        array $items,
        array $payments,
        bool $igtfEnabled,
        int $maxName = 40,
        ?string $comment = null,
    ): array {
        if (!in_array($kind, ['credit', 'debit'], true)) {
            throw new InvalidArgumentException("Unknown note kind '{$kind}'.");
        }
        $this->requireItems($items);

        $lines = $this->b->noteHeader($customerName, $customerId, $originalNumber, $originalDate, $printerSerial);
        if ($comment !== null && $comment !== '') {
            $lines[] = $this->b->comment(
                $comment,
                $kind === 'credit' ? FiscalCommandBuilder::COMMENT_CREDIT : FiscalCommandBuilder::COMMENT_DEBIT
            );
        }

        foreach ($items as $item) {
            $lines[] = $kind === 'credit'
                ? $this->b->creditItem($item, $maxName)
                : $this->b->debitItem($item, $maxName);
            if ($item->discountPercent > 0) {
                $lines[] = $this->b->discountPercent($item->discountPercent);
            }
        }

        return array_merge($lines, $this->b->payments($payments, $igtfEnabled));
    }

    private function requireItems(array $items): void
    {
        if ($items === []) {
            throw new InvalidArgumentException('A fiscal document needs at least one item.');
        }
    }
}
