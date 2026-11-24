<?php

namespace App\Services\Fiscal\Drivers;

use App\Services\Fiscal\ValueObjects\FiscalResponse;

/**
 * Contract that every fiscal printer driver must implement.
 *
 * All drivers communicate via the TFHKA trama protocol:
 * STX(0x02) + DATA + ETX(0x03) + LRC(XOR of DATA+ETX)
 * When using high-level APIs (IntTFHKA / TfhkaNet / TCP), only DATA is sent.
 *
 * Reference: Manual de Protocolos y Comandos Venezuela V8.5.0 (2022)
 */
interface FiscalPrinterDriverInterface
{
    /**
     * Send a raw DATA string to the printer and return the response.
     * The driver is responsible for framing (STX/ETX/LRC) if needed.
     *
     * @param  string  $data  Raw command DATA (e.g. "T0100000000" for open invoice)
     * @return FiscalResponse
     */
    public function send(string $data): FiscalResponse;

    /**
     * Read the S1 status register (last invoice number, serial, Z number).
     * Must be called after printing to persist fiscal data in DB.
     *
     * @return FiscalResponse
     */
    public function readS1(): FiscalResponse;

    /**
     * Read the S2 status register (subtotals, payment breakdown).
     * Must be called between partial payments for rounding reconciliation.
     *
     * @return FiscalResponse
     */
    public function readS2(): FiscalResponse;

    /**
     * Check if the driver is currently connected / reachable.
     */
    public function isConnected(): bool;

    /**
     * Return the driver identifier string (e.g. 'intTfhka', 'tfhkaNet', 'tcp', 'fake').
     */
    public function driverName(): string;
}
