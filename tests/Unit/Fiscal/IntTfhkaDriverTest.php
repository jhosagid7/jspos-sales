<?php

namespace Tests\Unit\Fiscal;

use App\Services\Fiscal\Drivers\IntTfhkaDriver;
use App\Services\Fiscal\FiscalStatusParser;
use PHPUnit\Framework\TestCase;

/** Exercises IntTfhkaDriver with a simulated IntTFHKA.exe (injected runner). */
class IntTfhkaDriverTest extends TestCase
{
    private string $dir;
    /** @var array<int,array<int,string>> */
    private array $calls = [];
    /** @var array<string,string> */
    private array $capturedFiles = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'fiscal_' . bin2hex(random_bytes(4));
        mkdir($this->dir);
        $this->calls = [];
        $this->capturedFiles = [];
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/{,.}*', GLOB_BRACE) ?: [] as $f) {
            is_file($f) && @unlink($f);
        }
        @rmdir($this->dir);
        parent::tearDown();
    }

    /** Fake exe: writes $retorno to Retorno.txt; optionally writes status data. */
    private function driver(string $retorno, ?string $statusData = null, string $port = 'COM5'): IntTfhkaDriver
    {
        $runner = function (array $argv, string $cwd, int $timeout) use ($retorno, $statusData): void {
            $this->calls[] = $argv;
            $action = $argv[1];

            if (str_starts_with($action, 'SendFileCmd(')) {
                $file = substr($action, strlen('SendFileCmd('));
                $this->capturedFiles[] = file_get_contents($cwd . DIRECTORY_SEPARATOR . $file);
            }
            if (str_starts_with($action, 'UploadStatusCmd(') && $statusData !== null) {
                file_put_contents($cwd . DIRECTORY_SEPARATOR . $argv[2], $statusData . "\r\n");
            }
            file_put_contents($cwd . DIRECTORY_SEPARATOR . 'Retorno.txt', $retorno . "\r\n");
        };

        return new IntTfhkaDriver($this->dir, $port, $runner);
    }

    public function test_send_batch_writes_ansi_file_and_succeeds(): void
    {
        $d = $this->driver('ASK');
        $r = $d->sendBatch(['iS*Dany Méndez', '101']);

        $this->assertTrue($r->success);
        $this->assertSame(2, $r->field('sent'));
        $this->assertSame("iS*Dany M\xE9ndez\n101", $this->capturedFiles[0]); // Windows-1252 é
        $this->assertSame([], glob($this->dir . '/batch_*.txt'), 'temp batch file must be removed');
    }

    public function test_port_file_is_written(): void
    {
        $this->driver('ASK', null, 'com99')->sendBatch(['I0X']);
        $this->assertSame('COM99', file_get_contents($this->dir . '/Puerto.dat'));
    }

    public function test_nak_is_a_failure(): void
    {
        $r = $this->driver('NAK')->sendBatch(['101']);
        $this->assertFalse($r->success);
        $this->assertSame(0, $r->field('sent'));
    }

    public function test_missing_reply_is_a_failure(): void
    {
        $d = new IntTfhkaDriver($this->dir, 'COM1', fn () => null); // exe wrote nothing
        $this->assertFalse($d->sendBatch(['101'])->success);
    }

    public function test_read_status_returns_untrimmed_s1(): void
    {
        $s1 = FiscalStatusParser::compose(['last_invoice' => '00001325', 'serial' => 'Z1F9999988']);
        $r  = $this->driver('T', $s1)->readStatus(1);

        $this->assertTrue($r->success);
        $this->assertSame($s1, $r->raw);
        $this->assertSame('UploadStatusCmd(S1', $this->calls[0][1]);
    }

    public function test_read_status_without_data_fails(): void
    {
        $this->assertFalse($this->driver('T', null)->readStatus(1)->success);
    }

    public function test_is_connected_uses_check_fprinter(): void
    {
        $this->assertTrue($this->driver('T')->isConnected());
        $this->assertSame('CheckFprinter()', $this->calls[0][1]);
        $this->assertFalse($this->driver('F')->isConnected());
    }

    public function test_invalid_port_is_rejected(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new IntTfhkaDriver($this->dir, 'COM5; calc.exe');
    }

    public function test_empty_batch_fails_without_running_exe(): void
    {
        $d = $this->driver('ASK');
        $this->assertFalse($d->sendBatch([])->success);
        $this->assertSame([], $this->calls);
    }
}
