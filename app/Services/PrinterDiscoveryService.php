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

        // 1. Get local and mapped network printers installed on Windows
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
                            $isThermal = preg_match('/POS|80|58|Receipt|Ticket|Thermal|TM-|XP-|Epson|Print/i', $name);
                            $isNetwork = str_starts_with($name, '\\\\');

                            // If it's a mapped network printer, verify the remote host is alive
                            if ($isNetwork) {
                                $clean = substr($name, 2);
                                $slashIdx = strpos($clean, '\\');
                                $host = $slashIdx !== false ? substr($clean, 0, $slashIdx) : $clean;
                                if (!empty($host)) {
                                    $fp = @fsockopen($host, 445, $errno, $errstr, 0.1);
                                    if (!$fp) {
                                        continue; // Discard stale/offline mapped queue
                                    }
                                    @fclose($fp);
                                }
                            }
                            
                            $discovered[] = [
                                'name' => $name,
                                'unc' => $name,
                                'host' => $isNetwork ? 'Red (Mapeada)' : 'Local (Esta PC)',
                                'share' => $name,
                                'type' => $isNetwork ? 'network' : 'local',
                                'is_thermal' => (bool) $isThermal,
                                'is_pdf' => $isPdf,
                                'label' => $name . ($isThermal ? ' ⚡ (Térmica)' : '')
                            ];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Local printer discovery error: " . $e->getMessage());
        }

        // 2. Collect known credentials from database
        $credentials = [];
        try {
            $auths = \App\Models\DeviceAuthorization::all();
            foreach ($auths as $a) {
                if (!empty($a->printer_user) && !empty($a->printer_password)) {
                    $credentials[$a->printer_user] = $a->printer_password;
                }
            }
        } catch (\Throwable $e) {}

        // 3. Scan subnets using rawprint.exe fast multi-threaded port 445 probe
        try {
            $exePath = CustomWindowsPrintConnector::getRawPrintExePath();
            $subnetsToScan = [];

            // Detect subnets from ipconfig
            $ipconfig = @shell_exec("ipconfig 2>&1");
            if ($ipconfig) {
                preg_match_all('/(?:Direcci[oó]n|IPv4[^\:]*)\s*[\.\:]+\s*:\s*([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/i', $ipconfig, $m);
                foreach ($m[1] ?? [] as $ip) {
                    if (!str_starts_with($ip, '127.') && !str_starts_with($ip, '169.254.')) {
                        $prefix = substr($ip, 0, strrpos($ip, '.') + 1);
                        $subnetsToScan[] = $prefix;
                    }
                }
            }

            // Ensure 192.168.20. is scanned
            $subnetsToScan[] = '192.168.20.';
            $subnetsToScan = array_unique($subnetsToScan);

            $liveIps = [];
            foreach ($subnetsToScan as $prefix) {
                if ($exePath && file_exists($exePath)) {
                    $scanOut = @shell_exec('"' . $exePath . '" --scan-subnet ' . escapeshellarg($prefix) . ' 2>&1');
                    if ($scanOut) {
                        $ips = explode(',', trim($scanOut));
                        foreach ($ips as $ip) {
                            $ip = trim($ip);
                            if (!empty($ip) && filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                                $liveIps[] = $ip;
                            }
                        }
                    }
                }
            }

            $liveIps = array_unique($liveIps);

            // 4. For each live host, inspect shared printers
            foreach ($liveIps as $ip) {
                $netView = @shell_exec("net view \\\\{$ip} 2>&1");
                
                // If Error 5 (Acceso denegado), authenticate with known credentials
                if ($netView && (str_contains($netView, 'Error de sistema 5') || str_contains($netView, 'Acceso denegado') || str_contains($netView, 'System error 5'))) {
                    foreach ($credentials as $user => $pass) {
                        @shell_exec('net use \\\\' . $ip . ' /u:' . escapeshellarg($user) . ' ' . escapeshellarg($pass) . ' 2>&1');
                    }
                    $netView = @shell_exec("net view \\\\{$ip} 2>&1");
                }

                if ($netView && !str_contains($netView, 'Error')) {
                    $lines = explode("\n", $netView);
                    foreach ($lines as $line) {
                        if (str_contains($line, 'Impresora') || str_contains($line, 'Print') || str_contains($line, 'Printer')) {
                            $parts = preg_split('/\s{2,}/', trim($line));
                            $shareName = $parts[0] ?? '';
                            if (!empty($shareName)) {
                                $unc = "\\\\{$ip}\\{$shareName}";
                                $isThermal = preg_match('/POS|80|58|Receipt|Ticket|Thermal|TM-|XP-|Epson|Print/i', $shareName);

                                // Avoid duplicates
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
        } catch (\Throwable $e) {
            Log::warning("Network printer discovery error: " . $e->getMessage());
        }

        return $discovered;
    }

    /**
     * Test connection to a printer and measure latency in milliseconds.
     */
    public static function testConnection(string $printerName, string $user = '', string $pass = ''): array
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

        // If credentials not provided but it's a network printer, try finding stored credentials
        if (empty($user) && str_starts_with($printerName, '\\\\')) {
            $auth = \App\Models\DeviceAuthorization::where('printer_name', $printerName)
                ->whereNotNull('printer_user')
                ->where('printer_user', '!=', '')
                ->first();
            if ($auth) {
                $user = $auth->printer_user;
                $pass = $auth->printer_password ?? '';
            }
        }

        $t0 = microtime(true);
        $cmd = '"' . $exePath . '" --test ' . escapeshellarg($printerName);
        if (!empty($user)) {
            $cmd .= ' ' . escapeshellarg($user) . ' ' . escapeshellarg($pass);
        }
        $cmd .= ' 2>&1';

        exec($cmd, $out, $ret);
        $t1 = microtime(true);

        $latencyMs = round(($t1 - $t0) * 1000, 1);
        $outputStr = implode(" ", $out);

        if ($ret === 0 && str_contains($outputStr, 'OK')) {
            return [
                'success' => true,
                'message' => "¡Conexión Exitosa! Impresora lista ({$latencyMs} ms).",
                'latency_ms' => $latencyMs,
                'raw_output' => $outputStr
            ];
        }

        $errorMsg = 'No se pudo conectar a la impresora.';
        if (str_contains($outputStr, 'no responde o esta apagado')) {
            $errorMsg = 'El equipo de red remoto no responde o está apagado.';
        } elseif (str_contains($outputStr, '1801')) {
            $errorMsg = 'Nombre de impresora no encontrado en el equipo de red (Error 1801).';
        } elseif (str_contains($outputStr, '1722')) {
            $errorMsg = 'El servidor RPC está apagado o inaccesible (Error 1722).';
        } elseif (str_contains($outputStr, '5')) {
            $errorMsg = 'Acceso denegado en el equipo remoto. Verifique usuario y contraseña (Error 5).';
        } elseif (!empty($outputStr)) {
            $errorMsg = $outputStr;
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
    public static function printTestPage(string $printerName, string $printerWidth = '80mm', string $user = '', string $pass = ''): array
    {
        try {
            $testRes = self::testConnection($printerName, $user, $pass);
            if (!$testRes['success']) {
                return $testRes;
            }

            $connector = new CustomWindowsPrintConnector($printerName, $user, $pass);
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

