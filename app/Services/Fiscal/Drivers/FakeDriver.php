<?php

namespace App\Services\Fiscal\Drivers;

use App\Services\Fiscal\ValueObjects\FiscalResponse;

/**
 * In-memory fake driver for unit testing and local development without hardware.
 *
 * Records all commands sent so tests can assert exact protocol sequences.
 * Simulates realistic S1/S2 responses using incrementing counters.
 *
 * Usage in tests:
 *   $fake = new FakeDriver();
 *   $service = new FiscalPrinterService($fake, ...);
 *   $service->printInvoice(...);
 *   $this->assertCount(5, $fake->sentCommands()); // open + items + close + payment + status
 */
final class FakeDriver implements FiscalPrinterDriverInterface
{
    /** @var array<string> All DATA strings sent via send(). */
    private array $sent = [];

    /** Whether the fake printer should report as connected. */
    private bool $connected = true;

    /** Auto-incrementing invoice counter (simulates printer memory). */
    private int $invoiceCounter = 1000;

    /** Z report counter. */
    private int $zCounter = 42;

    /** Printer serial number for simulation. */
    private string $serial = 'ZKFAKE0001';

    /** Queue of forced failures: ['send'|'s1'|'s2' => 'error message']. */
    private array $forcedErrors = [];

    // -------------------------------------------------------------------------
    // FiscalPrinterDriverInterface
    // -------------------------------------------------------------------------

    public function send(string $data): FiscalResponse
    {
        $this->sent[] = $data;

        if (isset($this->forcedErrors['send'])) {
            return FiscalResponse::failure($this->forcedErrors['send'], $data);
        }

        return FiscalResponse::ok("P0{$data}", ['echo' => $data]);
    }

    public function readS1(): FiscalResponse
    {
        if (isset($this->forcedErrors['s1'])) {
            return FiscalResponse::failure($this->forcedErrors['s1']);
        }

        $invoiceNum = str_pad($this->invoiceCounter, 8, '0', STR_PAD_LEFT);
        $zNum       = str_pad($this->zCounter, 4, '0', STR_PAD_LEFT);

        return FiscalResponse::ok(
            raw: "P0{$this->serial}{$invoiceNum}{$zNum}",
            fields: [
                'serial'          => $this->serial,
                'invoice_number'  => $invoiceNum,
                'z_counter'       => $zNum,
            ]
        );
    }

    public function readS2(): FiscalResponse
    {
        if (isset($this->forcedErrors['s2'])) {
            return FiscalResponse::failure($this->forcedErrors['s2']);
        }

        return FiscalResponse::ok('P0S2OK', [
            'subtotal_exento'   => '0.00',
            'subtotal_general'  => '0.00',
            'subtotal_reducida' => '0.00',
            'subtotal_adicional'=> '0.00',
        ]);
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function driverName(): string
    {
        return 'fake';
    }

    // -------------------------------------------------------------------------
    // Test helpers
    // -------------------------------------------------------------------------

    /** All DATA strings sent in order. */
    public function sentCommands(): array
    {
        return $this->sent;
    }

    /** Number of commands sent. */
    public function sentCount(): int
    {
        return count($this->sent);
    }

    /** Whether a command matching the prefix was sent. */
    public function hasSent(string $prefix): bool
    {
        foreach ($this->sent as $cmd) {
            if (str_starts_with($cmd, $prefix)) {
                return true;
            }
        }
        return false;
    }

    /** Reset all recorded commands (useful between test cases). */
    public function reset(): void
    {
        $this->sent = [];
        $this->forcedErrors = [];
    }

    /** Simulate connection failure. */
    public function goOffline(): void
    {
        $this->connected = false;
    }

    /** Simulate connection restored. */
    public function goOnline(): void
    {
        $this->connected = true;
    }

    /** Force the next send() / readS1() / readS2() to return an error. */
    public function forceError(string $channel, string $message): void
    {
        $this->forcedErrors[$channel] = $message;
    }

    /** Set the simulated invoice counter (printer memory). */
    public function setInvoiceCounter(int $counter): void
    {
        $this->invoiceCounter = $counter;
    }
}
