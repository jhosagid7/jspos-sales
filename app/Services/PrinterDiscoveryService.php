<?php

namespace App\Services;

use App\Services\CustomWindowsPrintConnector;
use Mike42\Escpos\Printer;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PrinterDiscoveryService
{
    /**
     * Discover all local and shared network printers on the LAN.
     */
    public static function discoverPrinters(): array
    {
        $discovered = [];

        // 1. Get local printers installed on Windows
        try {
            $localPrintersOutput = @shell_exec('powershell -NoProfile -Command "Get-Printer | Select-Object Name, Type, DriverName, PortName | ConvertTo-Json" 2>&1');
            if ($localPrintersOutput) {
                $decoded = json_decode($localPrintersOutput, true);
                if ($decoded) {
                    $items = isset($decoded['Name']) ? [$decoded] : $decoded;
                    foreach ($items as $item) {
                        $name = trim($item['Name'] ?? '');
                        if (!empty($name) && !str_contains($name, 'OneNote') && !str_contains($name, 'Fax') && !str_contains($name, 'XPS')) {
                            $isPdf = str_contains($name, 'PDF');
                            $isThermal = preg_match('/POS|80|58|Receipt|Ticket|Thermal|TM-|XP-|Epson/i', $name);
                            
                            $discovered[] = [
                                'name' => $name,
                                'unc' => $name,
                                'host' => 'Local (Esta PC)',
                                'share' => $name,
                                'type' => 'local',
                                'is_thermal' => (bool) $isThermal,
                                'is_pdf' => $isPdf,
                                'label' => $name . ($isThermal ? ' ⚡ (Térmica Local)' : ' (Local)')
                            ];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Local printer discovery error: " . $e->getMessage());
        }

        // 2. Discover shared printers across the local subnet
        try {
            $arpOutput = @shell_exec("arp -a 2>&1");
            if ($arpOutput) {
                preg_match_all('/([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/i', $arpOutput, $matches);
                $localIps = array_unique($matches[1] ?? []);
                
                $targetIps = [];
                foreach ($localIps as $ip) {
                    if (str_starts_with($ip, '192.168.') || str_starts_with($ip, '10.') || str_starts_with($ip, '172.')) {
                        $lastOctet = intval(substr(strrchr($ip, '.'), 1));
                        if ($lastOctet > 1 && $lastOctet < 255) {
                            $targetIps[] = $ip;
                        }
                    }
                }

                foreach ($targetIps as $ip) {
                    $fp = @fsockopen($ip, 445, $errno, $errstr, 0.12);
                    if ($fp) {
                        fclose($fp);
                        $netView = @shell_exec("net view \\\\$ip 2>&1");
                        if ($netView && !str_contains($netView, 'Error')) {
                            $lines = explode("\n", $netView);
                            foreach ($lines as $line) {
                                if (str_contains($line, 'Impresora') || str_contains($line, 'Print')) {
                                    $parts = preg_split('/\s{2,}/', trim($line));
                                    $shareName = $parts[0] ?? '';
                                    if (!empty($shareName)) {
                                        $unc = "\\\\{$ip}\\{$shareName}";
                                        $isThermal = preg_match('/POS|80|58|Receipt|Ticket|Thermal|TM-|XP-|Epson/i', $shareName);

                                        // Avoid duplicate if already in list
                                        $exists = false;
                                        foreach ($discovered as $d) {
                                            if (strtolower($d['unc']) === strtolower($unc)) {
                                                $exists = true;
                                                break;
                                            }
                                        }

                                        if (!$exists) {
                                            $discovered[] = [
                                                'name' => $shareName,
                                                'unc' => $unc,
                                                'host' => $ip,
                                                'share' => $shareName,
                                                'type' => 'network',
                                                'is_thermal' => (bool) $isThermal,
                                                'is_pdf' => false,
                                                'label' => "{$ip} \\ {$shareName}" . ($isThermal ? ' ⚡ (Térmica de Red)' : ' (Red)')
                                            ];
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Network printer discovery error: " . $e->getMessage());
        }

        return $discovered;
    }

    /**
     * Test connection to a printer and measure latency in milliseconds.
     */
    public static function testConnection(string $printerName): array
    {
        $printerName = trim($printerName);
        if (empty($printerName)) {
            return [
                'success' => false,
                'message' => 'El nombre de la impresora no puede estar vacío.',
                'latency_ms' => 0
            ];
        }

        $exePath = CustomWindowsPrintConnector::getRawPrintExePath();
        if (!$exePath || !file_exists($exePath)) {
            return [
                'success' => false,
                'message' => 'El ejecutable nativo rawprint.exe no fue encontrado.',
                'latency_ms' => 0
            ];
        }

        $tmpFile = tempnam(sys_get_temp_dir(), "prntest");
        // Null byte ping (does not print paper, just tests spooler handle)
        file_put_contents($tmpFile, "");

        $t0 = microtime(true);
        $cmd = '"' . $exePath . '" ' . escapeshellarg($printerName) . ' ' . escapeshellarg($tmpFile) . ' 2>&1';
        exec($cmd, $out, $ret);
        $t1 = microtime(true);

        @unlink($tmpFile);

        $latencyMs = round(($t1 - $t0) * 1000, 1);
        $outputStr = implode(" ", $out);

        if ($ret === 0) {
            return [
                'success' => true,
                'message' => "¡Conexión Exitosa! Impresora lista ({$latencyMs} ms).",
                'latency_ms' => $latencyMs,
                'raw_output' => $outputStr
            ];
        }

        $errorMsg = 'No se pudo conectar a la impresora.';
        if (str_contains($outputStr, '1801')) {
            $errorMsg = 'Nombre de impresora o equipo de red no encontrado (Error 1801).';
        } elseif (str_contains($outputStr, '1722')) {
            $errorMsg = 'El equipo remoto está apagado o inaccesible (Error 1722).';
        } elseif (str_contains($outputStr, '5')) {
            $errorMsg = 'Acceso denegado en el equipo remoto (Error 5).';
        }

        return [
            'success' => false,
            'message' => $errorMsg . " ({$latencyMs} ms)",
            'latency_ms' => $latencyMs,
            'raw_output' => $outputStr
        ];
    }

    /**
     * Print a formatted physical test page.
     */
    public static function printTestPage(string $printerName, string $printerWidth = '80mm'): array
    {
        try {
            $testRes = self::testConnection($printerName);
            if (!$testRes['success']) {
                return $testRes;
            }

            $connector = new CustomWindowsPrintConnector($printerName);
            $printer = new Printer($connector);

            $config = \App\Models\Configuration::first();
            $businessName = $config->business_name ?? 'JSPOS SALES';

            $is58mm = ($printerWidth === '58mm');
            $separator = $is58mm ? "--------------------------------" : "================================================";

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->setTextSize(1, 1);
            $printer->text($separator . "\n");
            $printer->text("PRUEBA DE IMPRESION EXITOSA\n");
            $printer->text(strtoupper($businessName) . "\n");
            $printer->text($separator . "\n\n");

            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("Fecha: " . Carbon::now()->format('d/m/Y H:i:s') . "\n");
            $printer->text("Impresora: " . $printerName . "\n");
            $printer->text("Ancho Papel: " . $printerWidth . "\n");
            $printer->text("Estado: 100% OPERATIVA Y CONECTADA\n");
            $printer->text("Latencia Spooler: " . $testRes['latency_ms'] . " ms\n");
            $printer->text($separator . "\n\n");

            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->text("¡Configuracion guardada correctamente!\n");
            $printer->text("JSPOS Sales POS Thermal Engine\n");

            $printer->feed(3);
            $printer->cut();
            $printer->close();

            return [
                'success' => true,
                'message' => "¡Ticket de prueba enviado a {$printerName} con éxito!",
                'latency_ms' => $testRes['latency_ms']
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error imprimiendo ticket de prueba: ' . $e->getMessage(),
                'latency_ms' => 0
            ];
        }
    }
}
