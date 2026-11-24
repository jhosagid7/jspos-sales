<?php

namespace App\Services\Fiscal\ValueObjects;

/**
 * Immutable value object wrapping a printer response.
 *
 * Raw response format (from printer):
 *   P{state}{errorCode}{data}
 *   state: 0 = OK, 1 = warning, 2 = error
 *
 * S1 response example:
 *   P0{serialNumber}{Z:0001}{invoice:00001}
 *
 * Reference: Manual de Protocolos V8.5.0 §3.2
 */
final class FiscalResponse
{
    public function __construct(
        /** Whether the printer acknowledged the command successfully. */
        public readonly bool $success,

        /** Raw string as returned by the driver (before parsing). */
        public readonly string $raw,

        /** Parsed fields extracted from the response (driver-specific). */
        public readonly array $fields = [],

        /** Error message when success = false. */
        public readonly ?string $error = null,
    ) {}

    // -------------------------------------------------------------------------
    // Named constructors
    // -------------------------------------------------------------------------

    public static function ok(string $raw, array $fields = []): self
    {
        return new self(success: true, raw: $raw, fields: $fields);
    }

    public static function failure(string $error, string $raw = ''): self
    {
        return new self(success: false, raw: $raw, error: $error);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    /** Get a parsed field by key, with optional default. */
    public function field(string $key, mixed $default = null): mixed
    {
        return $this->fields[$key] ?? $default;
    }

    /** Convenience: last invoice number from S1 response. */
    public function lastInvoiceNumber(): ?string
    {
        return $this->field('invoice_number');
    }

    /** Convenience: printer serial from S1 response. */
    public function printerSerial(): ?string
    {
        return $this->field('serial');
    }

    /** Convenience: Z (daily closure) counter from S1 response. */
    public function zCounter(): ?string
    {
        return $this->field('z_counter');
    }
}
