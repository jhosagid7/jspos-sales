<?php

namespace App\Services\Fiscal;

use App\Models\DebitNote;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Services\Fiscal\Drivers\FiscalPrinterDriverInterface;
use App\Services\Fiscal\ValueObjects\FiscalResponse;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Orchestrates printing: compose lines -> send batch -> read S1 -> persist fiscal data.
 *
 * Rules enforced:
 *  - a document is NEVER marked printed unless the batch AND the S1 read succeeded;
 *  - on failure the sale is marked `error` with the command log (no silent loss);
 *  - credit/debit notes require the original document's fiscal data.
 */
final class FiscalPrinterService
{
    public function __construct(
        private readonly FiscalPrinterDriverInterface $driver,
        private readonly FiscalDocumentComposer $composer = new FiscalDocumentComposer(),
        private readonly FiscalStatusParser $parser = new FiscalStatusParser(),
        private readonly FiscalCommandBuilder $builder = new FiscalCommandBuilder(),
        private readonly bool $igtfEnabled = false,
        private readonly int $maxName = 40,
    ) {}

    /** Print a Factura Fiscal for a sale. */
    public function printInvoice(Sale $sale, array $items, array $payments): array
    {
        $this->guardConnected();

        $lines = $this->composer->invoice(
            $sale->customer?->name ?? 'CLIENTE CONTADO',
            $sale->customer?->taxpayer_id ?? 'V-00000000',
            $items,
            $payments,
            $this->igtfEnabled,
            $this->maxName,
        );

        $s1 = $this->dispatch($lines, "sale#{$sale->id}", function (array $log, ?string $error) use ($sale) {
            $sale->update([
                'fiscal_status' => $error ? 'error' : $sale->fiscal_status,
                'fiscal_log'    => json_encode(['commands' => $log, 'error' => $error, 'at' => now()->toISOString()]),
            ]);
        });

        $sale->update([
            'fiscal_number'   => $s1['last_invoice'],
            'fiscal_serial'   => $s1['serial'],
            'fiscal_z_number' => $s1['z_counter'],
            'fiscal_datetime' => now(),
            'fiscal_status'   => 'printed',
            'is_fiscal'       => true,
        ]);

        return $s1;
    }

    /** Print a Nota de Crédito (refund) referencing the original fiscal invoice. */
    public function printCreditNote(SaleReturn $return, array $items, array $payments): array
    {
        return $this->printNote('credit', $return, $return->sale, $items, $payments, 'last_credit_note');
    }

    /** Print a Nota de Débito referencing the original fiscal invoice. */
    public function printDebitNote(DebitNote $note, array $items, array $payments): array
    {
        return $this->printNote('debit', $note, $note->sale, $items, $payments, 'last_debit_note');
    }

    public function printXReport(): FiscalResponse
    {
        $this->guardConnected();

        return $this->driver->send($this->builder->xReport());
    }

    /** Closes the fiscal day (irreversible). Caller must have asked for confirmation. */
    public function printZReport(): FiscalResponse
    {
        $this->guardConnected();
        $response = $this->driver->send($this->builder->zReport());
        Log::warning('Fiscal Z report executed', ['ok' => $response->success, 'raw' => $response->raw]);

        return $response;
    }

    // ---------------------------------------------------------------- internals

    private function printNote(string $kind, $note, ?Sale $original, array $items, array $payments, string $numberField): array
    {
        $this->guardConnected();

        if (!$original || !$original->fiscal_number || !$original->fiscal_serial || !$original->fiscal_datetime) {
            throw new \InvalidArgumentException(
                "Cannot print {$kind} note: the original sale has no fiscal data (number, serial, date)."
            );
        }

        $lines = $this->composer->note(
            $kind,
            $original->customer?->name ?? 'CLIENTE CONTADO',
            $original->customer?->taxpayer_id ?? 'V-00000000',
            $original->fiscal_number,
            $original->fiscal_datetime->format('d-m-Y'),
            $original->fiscal_serial,
            $items,
            $payments,
            $this->igtfEnabled,
            $this->maxName,
        );

        $s1 = $this->dispatch($lines, "{$kind}#{$note->id}", function (array $log, ?string $error) use ($note) {
            if ($error) {
                $note->update(['fiscal_status' => 'error']);
            }
        });

        $note->update([
            'fiscal_number'   => $s1[$numberField],
            'fiscal_serial'   => $s1['serial'],
            'fiscal_z_number' => $s1['z_counter'],
            'fiscal_datetime' => now(),
            'fiscal_status'   => 'printed',
        ]);

        return $s1;
    }

    /**
     * Sends the batch and reads S1. Returns parsed S1 fields.
     *
     * @param callable(array,?string):void $onFinish receives the log and the error (if any)
     */
    private function dispatch(array $lines, string $ref, callable $onFinish): array
    {
        $log = [];
        try {
            $sent = $this->driver->sendBatch($lines);
            $log[] = ['lines' => $lines, 'ok' => $sent->success, 'raw' => $sent->raw, 'sent' => $sent->field('sent')];
            if (!$sent->success) {
                throw new RuntimeException("Fiscal printer rejected the document: {$sent->error}");
            }

            $status = $this->driver->readStatus(1);
            $log[] = ['status' => 'S1', 'ok' => $status->success];
            if (!$status->success) {
                throw new RuntimeException("Document sent but S1 could not be read: {$status->error}");
            }

            $s1 = $this->parser->parseS1($status->raw);
            $onFinish($log, null);
            Log::info('Fiscal document printed', ['ref' => $ref, 'driver' => $this->driver->driverName()]);

            return $s1;
        } catch (\Throwable $e) {
            $onFinish($log, $e->getMessage());
            Log::error('Fiscal document failed', ['ref' => $ref, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function guardConnected(): void
    {
        if (!$this->driver->isConnected()) {
            throw new RuntimeException('Fiscal printer not reachable (driver: ' . $this->driver->driverName() . ').');
        }
    }
}
