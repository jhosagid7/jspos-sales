<?php

namespace Tests\Unit\Fiscal;

use App\Services\Fiscal\FiscalStatusParser;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class FiscalStatusParserTest extends TestCase
{
    public function test_composed_s1_has_documented_length(): void
    {
        $this->assertSame(FiscalStatusParser::S1_LENGTH, strlen(FiscalStatusParser::compose([])));
    }

    public function test_parse_roundtrip(): void
    {
        $raw = FiscalStatusParser::compose([
            'last_invoice'     => '00001325',
            'last_credit_note' => '00000043',
            'last_debit_note'  => '00000007',
            'z_counter'        => '0042',
            'rif'              => 'J-312171197',
            'serial'           => 'Z1F9999988',
            'date'             => '161126',
        ]);

        $s1 = (new FiscalStatusParser())->parseS1($raw);

        $this->assertSame('00001325', $s1['last_invoice']);
        $this->assertSame('00000043', $s1['last_credit_note']);
        $this->assertSame('00000007', $s1['last_debit_note']);
        $this->assertSame('0042', $s1['z_counter']);
        $this->assertSame('J-312171197', $s1['rif']);
        $this->assertSame('Z1F9999988', $s1['serial']);
        $this->assertSame('161126', $s1['date']);
    }

    public function test_short_response_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new FiscalStatusParser())->parseS1('too short');
    }

    public function test_trailing_line_break_is_ignored(): void
    {
        $raw = FiscalStatusParser::compose(['serial' => 'ZPA2000343']) . "\r\n";
        $this->assertSame('ZPA2000343', (new FiscalStatusParser())->parseS1($raw)['serial']);
    }
}
