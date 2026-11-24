<?php

namespace App\Services\Fiscal;

use InvalidArgumentException;

/**
 * Parses the S1 status string (library layout) of HKA80 / PP9 / PP9-PLUS family.
 *
 * Offsets are 0-based inclusive "Desde-Hasta" of the "Librería" columns in
 * Manual V8.5.0, Tabla 45 (printers SRP812, DT230, HKA80, P3100DL, PP9, PP9-PLUS, TD1140).
 * Total length: 129 chars.
 *
 * NOTE: derived from a column-garbled PDF table; must be re-validated against the
 * virtual printer (VFP HKA) / physical device before production.
 */
final class FiscalStatusParser
{
    public const S1_LENGTH = 129;

    /** field => [from, to] (inclusive) */
    private const S1_LAYOUT = [
        'status'             => [0, 3],
        'sales_subtotal'     => [5, 21],
        'last_invoice'       => [23, 30],
        'invoice_count'      => [32, 36],
        'last_debit_note'    => [38, 45],
        'debit_note_count'   => [47, 51],
        'last_credit_note'   => [53, 60],
        'credit_note_count'  => [62, 66],
        'last_non_fiscal'    => [68, 75],
        'non_fiscal_count'   => [77, 81],
        'z_counter'          => [83, 86],
        'fiscal_mem_reports' => [88, 91],
        'rif'                => [93, 103],
        'serial'             => [105, 114],
        'time'               => [116, 121],
        'date'               => [123, 128],
    ];

    /** @return array<string,string> */
    public function parseS1(string $raw): array
    {
        $raw = rtrim($raw, "\r\n");
        if (strlen($raw) < self::S1_LENGTH) {
            throw new InvalidArgumentException(
                'S1 response too short: expected ' . self::S1_LENGTH . ' chars, got ' . strlen($raw) . '.'
            );
        }

        $out = [];
        foreach (self::S1_LAYOUT as $field => [$from, $to]) {
            $out[$field] = trim(substr($raw, $from, $to - $from + 1));
        }

        return $out;
    }

    /** Test helper: compose a raw S1 string from field values (inverse of parseS1). */
    public static function compose(array $values): string
    {
        $buf = str_repeat(' ', self::S1_LENGTH);
        foreach (self::S1_LAYOUT as $field => [$from, $to]) {
            $width = $to - $from + 1;
            $val   = substr((string) ($values[$field] ?? ''), 0, $width);
            $buf   = substr_replace($buf, str_pad($val, $width, ' ', STR_PAD_LEFT), $from, $width);
        }

        return $buf;
    }
}
