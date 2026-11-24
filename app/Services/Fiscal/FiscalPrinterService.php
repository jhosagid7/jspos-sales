<?php

namespace App\Services\Fiscal;

use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\DebitNote;
use App\Services\Fiscal\Drivers\FiscalPrinterDriverInterface;
use App\Services\Fiscal\ValueObjects\FiscalItem;
use App\Services\Fiscal\ValueObjects\FiscalPayment;
use App\Services\Fiscal\ValueObjects\FiscalResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * High-level fiscal printer orchestrator.
 *
 * Responsibilities:
 *   - Coordinate command sequences for each document type
 *   - Persist fiscal data (serial, invoice number, Z counter) back to the Sale
 *   - Log all printer interactions for audit
 *   - Enforce single-threaded access (via DB lock on the Sale)
 *
 * This service does NOT build raw protocol strings — that is FiscalCommandBuilder's job.
 * This service does NOT communicate with hardware — that is the Driver's job.
 *
 * # Supported document types:
 *   - Factura Fiscal        (printInvoice)
 *   - Nota de Crédito       (printCreditNote)
 *   - Nota de Débito        (printDebitNote)
 *   - Reporte X             (printXReport)
 *   - Reporte Z             (printZReport)
 *
 * # Multi-tenant note:
 *   igtfEnabled and printerModel come from the installation's Configuration.
 *   They must be injected at construction time (via FiscalPrinterServiceFactory).
 *
 * Reference: Manual de Protocolos V8.5.0 (2022)
 */
final class FiscalPrinterService
{
    /**
     * Maximum item name length by printer model.
     * HKA80 = 20 chars, PP9/PP9 Plus = 40 chars.
     */
    private const MAX_NAME_LENGTH = [
        'hka80'    => 20,
        'pp9'      => 40,
        'pp9plus'  => 40,
        'default'  => 20,
    ];

    public function __construct(
        private readonly FiscalPrinterDriverInterface $driver,
        private readonly FiscalCommandBuilder $builder,
        /** Whether IGTF (3%) is active for this installation. */
        private readonly bool $igtfEnabled = false,
        /** Printer model key (hka80 | pp9 | pp9plus). */
        private readonly string $printerModel = 'default',
    ) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Print a Factura Fiscal for a completed Sale.
     *
     * Flow:
     *   1. Open invoice (T01 + customer data)
     *   2. Send item lines
     *   3. Close item section (subtotal T)
     *   4. Send payment lines (command 199 when IGTF active)
     *   5. Read S1 status → persist fiscal_number, fiscal_serial, fiscal_z_number
     *
     * @param  Sale              $sale      Eloquent Sale with saleDetails and customer eager-loaded
     * @param  FiscalItem[]      $items     Pre-built fiscal items (from FiscalItemMapper)
     * @param  FiscalPayment[]   $payments  Payment methods used
     * @return FiscalResponse    Final S1 response with invoice number
     * @throws RuntimeException  When driver is offline or printer returns error
     */
    public function printInvoice(Sale $sale, array $items, array $payments): FiscalResponse
    {
        $this->guardConnected();
        $this->guardNotEmpty($items, 'items');
        $this->guardNotEmpty($payments, 'payments');

        $customerName = $sale->customer?->name ?? 'CLIENTE GENERICO';
        $customerId   = $sale->customer?->taxpayer_id ?? 'V-00000000';

        $log = [];

        try {
            // 1. Open document
            $openCmd = $this->builder->openInvoice($customerName, $customerId);
            $this->sendOrFail($openCmd, $log);

            // 2. Items
            $maxLen = $this->maxNameLength();
            foreach ($items as $item) {
                $cmd = $this->builder->buildItemCommand($item, $maxLen);
                $this->sendOrFail($cmd, $log);

                if ($item->discountPercent > 0) {
                    $discountCmd = $this->builder->buildDiscountCommand($item->discountPercent);
                    $this->sendOrFail($discountCmd, $log);
                }
            }

            // 3. Close item section
            $this->sendOrFail($this->builder->buildSubtotalCommand(), $log);

            // 4. Payments
            foreach ($payments as $payment) {
                $cmd = $this->builder->buildPaymentCommand($payment, $this->igtfEnabled);
                $this->sendOrFail($cmd, $log);
            }

            // 5. Read S1 → persist
            $s1 = $this->driver->readS1();
            $log[] = ['cmd' => 'S1', 'response' => $s1->raw, 'ok' => $s1->success];

            if ($s1->success) {
                $this->persistFiscalData($sale, $s1);
            }

            Log::channel('fiscal')->info('Invoice printed', [
                'sale_id'        => $sale->id,
                'invoice_number' => $s1->lastInvoiceNumber(),
                'commands'       => count($log),
            ]);

            $this->saveFiscalLog($sale, $log, 'printed');

            return $s1;

        } catch (\Throwable $e) {
            $this->saveFiscalLog($sale, $log, 'error', $e->getMessage());
            Log::channel('fiscal')->error('Invoice print failed', [
                'sale_id' => $sale->id,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Print a Nota de Crédito for a SaleReturn.
     *
     * Requires the original sale to have fiscal_number, fiscal_serial, and fiscal_datetime.
     *
     * @param  SaleReturn       $saleReturn  With originalSale eager-loaded
     * @param  FiscalItem[]     $items       Items being returned (use buildCreditItemCommand)
     * @param  FiscalPayment[]  $payments    Refund payment methods
     */
    public function printCreditNote(SaleReturn $saleReturn, array $items, array $payments): FiscalResponse
    {
        $this->guardConnected();

        $originalSale = $saleReturn->sale;
        $this->guardFiscalData($originalSale, 'credit note');

        $log = [];

        try {
            // Header commands (OBLIGATORIO)
            $headers = $this->builder->buildNoteHeaders(
                invoiceNumber: $originalSale->fiscal_number,
                invoiceDate:   $originalSale->fiscal_datetime?->format('d/m/Y') ?? '',
                printerSerial: $originalSale->fiscal_serial,
                customerId:    $originalSale->customer?->taxpayer_id ?? 'V-00000000',
                customerName:  $originalSale->customer?->name ?? 'CLIENTE GENERICO',
            );

            foreach ($headers as $header) {
                $this->sendOrFail($header, $log);
            }

            // Open credit note
            $this->sendOrFail(
                $this->builder->openCreditNote(
                    $originalSale->customer?->name ?? 'CLIENTE GENERICO',
                    $originalSale->customer?->taxpayer_id ?? 'V-00000000',
                ),
                $log
            );

            // Credit items
            $maxLen = $this->maxNameLength();
            foreach ($items as $item) {
                $this->sendOrFail($this->builder->buildCreditItemCommand($item, $maxLen), $log);
            }

            // Close + payment
            $this->sendOrFail($this->builder->buildSubtotalCommand(), $log);
            foreach ($payments as $payment) {
                $this->sendOrFail($this->builder->buildPaymentCommand($payment, $this->igtfEnabled), $log);
            }

            $s1 = $this->driver->readS1();
            $log[] = ['cmd' => 'S1', 'response' => $s1->raw, 'ok' => $s1->success];

            // Persist fiscal data on the return record
            if ($s1->success) {
                $saleReturn->update([
                    'fiscal_number'   => $s1->lastInvoiceNumber(),
                    'fiscal_serial'   => $s1->printerSerial(),
                    'fiscal_z_number' => $s1->zCounter(),
                    'fiscal_status'   => 'printed',
                ]);
            }

            Log::channel('fiscal')->info('Credit note printed', [
                'sale_return_id'  => $saleReturn->id,
                'fiscal_number'   => $s1->lastInvoiceNumber(),
            ]);

            return $s1;

        } catch (\Throwable $e) {
            Log::channel('fiscal')->error('Credit note failed', [
                'sale_return_id' => $saleReturn->id,
                'error'          => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Print a Nota de Débito.
     * Same flow as credit note but uses debit item commands.
     */
    public function printDebitNote(DebitNote $debitNote, array $items, array $payments): FiscalResponse
    {
        $this->guardConnected();

        $originalSale = $debitNote->sale;
        $this->guardFiscalData($originalSale, 'debit note');

        $log = [];

        try {
            $headers = $this->builder->buildNoteHeaders(
                invoiceNumber: $originalSale->fiscal_number,
                invoiceDate:   $originalSale->fiscal_datetime?->format('d/m/Y') ?? '',
                printerSerial: $originalSale->fiscal_serial,
                customerId:    $originalSale->customer?->taxpayer_id ?? 'V-00000000',
                customerName:  $originalSale->customer?->name ?? 'CLIENTE GENERICO',
            );

            foreach ($headers as $header) {
                $this->sendOrFail($header, $log);
            }

            $this->sendOrFail(
                $this->builder->openDebitNote(
                    $originalSale->customer?->name ?? 'CLIENTE GENERICO',
                    $originalSale->customer?->taxpayer_id ?? 'V-00000000',
                ),
                $log
            );

            $maxLen = $this->maxNameLength();
            foreach ($items as $item) {
                $this->sendOrFail($this->builder->buildDebitItemCommand($item, $maxLen), $log);
            }

            $this->sendOrFail($this->builder->buildSubtotalCommand(), $log);
            foreach ($payments as $payment) {
                $this->sendOrFail($this->builder->buildPaymentCommand($payment, $this->igtfEnabled), $log);
            }

            $s1 = $this->driver->readS1();
            $log[] = ['cmd' => 'S1', 'response' => $s1->raw, 'ok' => $s1->success];

            if ($s1->success) {
                $debitNote->update([
                    'fiscal_number'   => $s1->lastInvoiceNumber(),
                    'fiscal_serial'   => $s1->printerSerial(),
                    'fiscal_z_number' => $s1->zCounter(),
                    'fiscal_status'   => 'printed',
                ]);
            }

            Log::channel('fiscal')->info('Debit note printed', [
                'debit_note_id'  => $debitNote->id,
                'fiscal_number'  => $s1->lastInvoiceNumber(),
            ]);

            return $s1;

        } catch (\Throwable $e) {
            Log::channel('fiscal')->error('Debit note failed', [
                'debit_note_id' => $debitNote->id,
                'error'         => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Print X (interim) report — does NOT close the fiscal day.
     */
    public function printXReport(): FiscalResponse
    {
        $this->guardConnected();
        return $this->driver->send($this->builder->buildXReportCommand());
    }

    /**
     * Print Z (daily closure) report — CLOSES the fiscal day.
     * This is irreversible. Only call after explicit user confirmation.
     */
    public function printZReport(): FiscalResponse
    {
        $this->guardConnected();
        $response = $this->driver->send($this->builder->buildZReportCommand());

        Log::channel('fiscal')->warning('Z report executed', [
            'success' => $response->success,
            'raw'     => $response->raw,
        ]);

        return $response;
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function sendOrFail(string $cmd, array &$log): FiscalResponse
    {
        $response = $this->driver->send($cmd);
        $log[] = ['cmd' => $cmd, 'response' => $response->raw, 'ok' => $response->success];

        if (!$response->success) {
            throw new RuntimeException(
                "Fiscal printer error on command [{$cmd}]: {$response->error}"
            );
        }

        return $response;
    }

    private function guardConnected(): void
    {
        if (!$this->driver->isConnected()) {
            throw new RuntimeException(
                'Fiscal printer is not connected. Driver: ' . $this->driver->driverName()
            );
        }
    }

    private function guardNotEmpty(array $collection, string $name): void
    {
        if (empty($collection)) {
            throw new \InvalidArgumentException("Cannot print fiscal document: {$name} list is empty.");
        }
    }

    private function guardFiscalData(Sale $sale, string $context): void
    {
        if (empty($sale->fiscal_number) || empty($sale->fiscal_serial)) {
            throw new \InvalidArgumentException(
                "Cannot print {$context}: original sale #{$sale->id} has no fiscal data recorded."
            );
        }
    }

    private function persistFiscalData(Sale $sale, FiscalResponse $s1): void
    {
        $sale->update([
            'fiscal_number'   => $s1->lastInvoiceNumber(),
            'fiscal_serial'   => $s1->printerSerial(),
            'fiscal_z_number' => $s1->zCounter(),
            'fiscal_datetime' => now(),
            'fiscal_status'   => 'printed',
        ]);
    }

    private function saveFiscalLog(Sale $sale, array $log, string $status, string $error = ''): void
    {
        $sale->update([
            'fiscal_status' => $status,
            'fiscal_log'    => json_encode([
                'commands' => $log,
                'error'    => $error ?: null,
                'at'       => now()->toISOString(),
            ]),
        ]);
    }

    private function maxNameLength(): int
    {
        return self::MAX_NAME_LENGTH[$this->printerModel]
            ?? self::MAX_NAME_LENGTH['default'];
    }
}
