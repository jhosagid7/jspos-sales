<?php

namespace App\Services\Fiscal\Drivers;

use App\Services\Fiscal\FiscalStatusParser;
use App\Services\Fiscal\ValueObjects\FiscalResponse;

/** In-memory driver for tests and hardware-free development. Records every command. */
final class FakeDriver implements FiscalPrinterDriverInterface
{
    /** @var string[] */
    private array $sent = [];
    private bool $connected = true;
    private int $lastInvoice = 1000;
    private string $serial = 'ZKF0000001';
    private string $z = '0042';
    /** @var array<string,string> */
    private array $forcedErrors = [];

    public function send(string $command): FiscalResponse
    {
        return $this->sendBatch([$command]);
    }

    public function sendBatch(array $commands): FiscalResponse
    {
        if (isset($this->forcedErrors['send'])) {
            return FiscalResponse::failure($this->forcedErrors['send'], '', ['sent' => 0]);
        }

        foreach ($commands as $c) {
            $this->sent[] = $c;
        }
        $this->lastInvoice++;

        return FiscalResponse::ok('ASK', ['sent' => count($commands)]);
    }

    public function readStatus(int $n = 1): FiscalResponse
    {
        if (isset($this->forcedErrors['status'])) {
            return FiscalResponse::failure($this->forcedErrors['status']);
        }

        $raw = FiscalStatusParser::compose([
            'status'       => '0000',
            'last_invoice' => str_pad((string) $this->lastInvoice, 8, '0', STR_PAD_LEFT),
            'z_counter'    => $this->z,
            'serial'       => $this->serial,
            'date'         => date('dmy'),
        ]);

        return FiscalResponse::ok($raw);
    }

    public function isConnected(): bool
    {
        return $this->connected;
    }

    public function driverName(): string
    {
        return 'fake';
    }

    // ---- test helpers
    /** @return string[] */
    public function sentCommands(): array
    {
        return $this->sent;
    }

    public function hasSent(string $prefix): bool
    {
        foreach ($this->sent as $c) {
            if (str_starts_with($c, $prefix)) {
                return true;
            }
        }

        return false;
    }

    public function goOffline(): void { $this->connected = false; }
    public function goOnline(): void { $this->connected = true; }
    public function forceError(string $channel, string $message): void { $this->forcedErrors[$channel] = $message; }
    public function reset(): void { $this->sent = []; $this->forcedErrors = []; }
    public function setLastInvoice(int $n): void { $this->lastInvoice = $n; }
}
