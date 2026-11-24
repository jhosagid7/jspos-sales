<?php

namespace App\Services\Fiscal\Drivers;

use App\Services\Fiscal\ValueObjects\FiscalResponse;

/**
 * Contract for every fiscal printer transport (local exe, remote agent, TCP, fake).
 *
 * Drivers move command LINES (library format, see FiscalCommandBuilder) to the
 * printer; framing (STX/ETX/LRC) is handled by the underlying SDK.
 */
interface FiscalPrinterDriverInterface
{
    /** Send a single command (e.g. "I0X"). */
    public function send(string $command): FiscalResponse;

    /**
     * Send many command lines sequentially, stopping at the first failure.
     * The response `fields['sent']` holds how many lines were accepted.
     *
     * @param string[] $commands
     */
    public function sendBatch(array $commands): FiscalResponse;

    /** Read a status register (S1..S6). Raw string is in FiscalResponse::$raw. */
    public function readStatus(int $n = 1): FiscalResponse;

    /** True if the printer answers on the configured port. */
    public function isConnected(): bool;

    public function driverName(): string;
}
