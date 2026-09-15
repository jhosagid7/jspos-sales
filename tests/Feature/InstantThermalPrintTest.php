<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\CustomWindowsPrintConnector;
use Mike42\Escpos\Printer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InstantThermalPrintTest extends TestCase
{
    /** @test */
    public function rawprint_binary_exists_and_is_executable()
    {
        $exePath = CustomWindowsPrintConnector::getRawPrintExePath();

        $this->assertNotNull($exePath, 'rawprint.exe path should not be null');
        $this->assertFileExists($exePath, 'rawprint.exe binary file must exist');
        
        exec('"' . $exePath . '" 2>&1', $out, $ret);
        // Returns exit code 2 (missing arguments) or 1
        $this->assertContains($ret, [1, 2]);
        $this->assertStringContainsString('Usage: rawprint', implode(' ', $out));
    }

    /** @test */
    public function custom_windows_print_connector_parses_destinations_correctly()
    {
        // 1. Local printer name
        $local = new CustomWindowsPrintConnector('POS-80');
        $this->assertInstanceOf(CustomWindowsPrintConnector::class, $local);

        // 2. UNC path
        $unc = new CustomWindowsPrintConnector('\\\\192.168.1.100\\POS80');
        $this->assertInstanceOf(CustomWindowsPrintConnector::class, $unc);

        // 3. SMB URL with credentials
        $smb = new CustomWindowsPrintConnector('smb://admin:secret123*@192.168.1.50/TicketPrinter');
        $this->assertInstanceOf(CustomWindowsPrintConnector::class, $smb);
    }

    /** @test */
    public function print_connector_executes_within_subsecond_threshold()
    {
        $exePath = CustomWindowsPrintConnector::getRawPrintExePath();
        if (!$exePath || !file_exists($exePath)) {
            $this->markTestSkipped('rawprint.exe is only available on Windows environments.');
        }

        $tmpFile = tempnam(sys_get_temp_dir(), "testesc");
        file_put_contents($tmpFile, "PRUEBA DE IMPRESION INSTANTANEA\n");

        $t0 = microtime(true);
        $cmd = '"' . $exePath . '" "Microsoft Print to PDF" "' . $tmpFile . '" 2>&1';
        exec($cmd, $out, $ret);
        $t1 = microtime(true);

        @unlink($tmpFile);

        $executionTimeMs = ($t1 - $t0) * 1000;

        // Verify execution is subsecond (< 500ms, typically 80ms)
        $this->assertLessThan(500, $executionTimeMs, "Raw printing took {$executionTimeMs}ms, expected under 500ms.");
    }
}
