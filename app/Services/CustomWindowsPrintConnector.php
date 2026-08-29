<?php

namespace App\Services;

use Exception;
use BadMethodCallException;
use Mike42\Escpos\PrintConnectors\PrintConnector;

/**
 * Custom Connector based on WindowsPrintConnector but with permissive Regex for passwords.
 */
class CustomWindowsPrintConnector implements PrintConnector
{
    /**
     * @var array $buffer
     */
    private $buffer;

    /**
     * @var string $hostname
     */
    private $hostname;

    /**
     * @var boolean $isLocal
     */
    private $isLocal;

    /**
     * @var int $platform
     */
    private $platform;

    /**
     * @var string $printerName
     */
    private $printerName;

    /**
     * @var string $userName
     */
    private $userName;

    /**
     * @var string $userPassword
     */
    private $userPassword;

    /**
     * @var string $workgroup
     */
    private $workgroup;

    const PLATFORM_LINUX = 0;
    const PLATFORM_MAC = 1;
    const PLATFORM_WIN = 2;

    const REGEX_LOCAL = "/^(LPT\d|COM\d):?$/i";
    const REGEX_PRINTERNAME = "/^[\d\w-]+(\s[\d\w-]+)*$/";
    
    // MODIFIED REGEX: Allows any character in password/user except control chars. 
    // Much more permissive to support special chars like '*'
    const REGEX_SMB_PERMISSIVE = "/^smb:\/\/.*$/";

    public function __construct($dest)
    {
        $this->platform = $this->getCurrentPlatform();
        $this->isLocal = false;
        $this->buffer = null;
        $this->userName = null;
        $this->userPassword = null;
        $this->workgroup = null;

        $cleanDest = rtrim(trim($dest), ':');

        if (preg_match(self::REGEX_LOCAL, $cleanDest) == 1) {
            if ($this->platform !== self::PLATFORM_WIN) {
                throw new BadMethodCallException("WindowsPrintConnector can only be used to print to a local printer ('".$dest."') on a Windows computer.");
            }
            $this->isLocal = true;
            $this->hostname = null;
            $this->printerName = strtoupper($cleanDest);
        } elseif (preg_match(self::REGEX_SMB_PERMISSIVE, $dest) == 1) {
            // Connect to samba share, eg smb://host/printer
            $part = parse_url($dest);
            $this->hostname = $part['host'] ?? '';
            
            /* Printer name and optional workgroup */
            $path = ltrim($part['path'] ?? '', '/');
            if (strpos($path, "/") !== false) {
                $pathPart = explode("/", $path);
                $this->workgroup = $pathPart[0];
                $this->printerName = $pathPart[1];
            } else {
                $this->printerName = $path;
            }
            
            /* Username and password */
            if (isset($part['user'])) {
                $this->userName = urldecode($part['user']); // Decode to handle encoded chars if needed
                if (isset($part['pass'])) {
                    $this->userPassword = urldecode($part['pass']);
                }
            }
        } elseif (str_starts_with($dest, '\\\\')) {
            // Support raw UNC paths like \\COMPUTER\SHARE or \\192.168.1.100\POS80
            $uncClean = ltrim($dest, '\\');
            $parts = explode('\\', $uncClean);
            if (count($parts) >= 2) {
                $this->hostname = $parts[0];
                $this->printerName = $parts[1];
                $this->isLocal = false;
            } else {
                $this->printerName = $dest;
                $this->isLocal = true;
            }
        } elseif (preg_match(self::REGEX_PRINTERNAME, $dest) == 1) {
            $hostname = gethostname();
            if (!$hostname) {
                $hostname = "localhost";
            }
            $this->hostname = $hostname;
            $this->printerName = $dest;
        } else {
            // Fallback: If it doesn't match permissive regex (unlikely for smb://) or printername regex
            // Try to treat as raw printer name if valid
            $this->printerName = $dest;
            $this->isLocal = true; // Assume local/mapped if no smb:// prefix matched
        }
        $this->buffer = [];
    }

    public function __destruct()
    {
        if ($this->buffer !== null) {
            trigger_error("Print connector was not finalized. Did you forget to close the printer?", E_USER_NOTICE);
        }
    }

    public function finalize()
    {
        $data = implode($this->buffer);
        $this->buffer = null;
        if ($this->platform == self::PLATFORM_WIN) {
            $this->finalizeWin($data);
        } else {
            // Fallback for non-windows (though this is Windows connector)
            throw new Exception("Non-Windows printing not fully implemented in this custom connector.");
        }
    }

    protected function finalizeWin($data)
    {
        $targetHost = $this->isLocal ? null : self::resolveHostnameToIp($this->hostname);
        $printerName = $this->isLocal ? $this->printerName : ("\\\\" . ($targetHost ?: $this->hostname) . "\\" . $this->printerName);

        // If network printer with credentials, authenticate SMB session prior to spooling/copying
        if (!$this->isLocal && $this->userName !== null) {
            $device = $printerName;
            $user = "/user:" . ($this->workgroup != null ? ($this->workgroup . "\\") : "") . $this->userName;
            if ($this->userPassword == null) {
                $command = sprintf("net use %s %s", escapeshellarg($device), escapeshellarg($user));
            } else {
                $command = sprintf("net use %s %s %s", escapeshellarg($device), escapeshellarg($user), escapeshellarg($this->userPassword));
            }
            $this->runCommand($command, $outputStr, $errorStr);
        }

        // Primary Method: Windows Raw Print Spooler API via PowerShell (Works for ALL local and network printers)
        try {
            if ($this->sendToWin32Spooler($printerName, $data)) {
                return;
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning("Win32 Spooler print attempt failed for {$printerName}: " . $e->getMessage());
        }

        // Fallback Method 1: copy to device UNC
        if (!$this->isLocal) {
            $device = $printerName;
            $netUseError = "";
            if ($this->userName !== null) {
                $user = "/user:" . ($this->workgroup != null ? ($this->workgroup . "\\") : "") . $this->userName;
                if ($this->userPassword == null) {
                    $command = sprintf("net use %s %s", escapeshellarg($device), escapeshellarg($user));
                } else {
                    $command = sprintf("net use %s %s %s", escapeshellarg($device), escapeshellarg($user), escapeshellarg($this->userPassword));
                }
                
                $ret = $this->runCommand($command, $outputStr, $errorStr);
                if ($ret != 0) {
                     $netUseError = " | net use error: " . trim($errorStr);
                }
            } else {
                // Ensure net use connection is active for UNC share
                $command = sprintf("net use %s", escapeshellarg($device));
                $this->runCommand($command, $outputStr, $errorStr);
            }
            
            $filename = tempnam(sys_get_temp_dir(), "escpos");
            file_put_contents($filename, $data);
            if (!@copy($filename, $device)) {
                 // Fallback 2: Try writing directly to LPT1 if mapped by Windows net use
                 if (@file_put_contents("LPT1", $data) !== false) {
                     unlink($filename);
                     return;
                 }
                 unlink($filename);
                 $authInfo = $this->userName ? " with User: " . $this->userName : " (No Auth)";
                 throw new Exception("Failed to copy file to printer at $device $authInfo" . $netUseError);
            }
            unlink($filename);
        } else {
            $target = rtrim($this->printerName, ':');
            if (@file_put_contents($target, $data) === false && @file_put_contents($this->printerName, $data) === false) {
                throw new Exception("Failed to write file to printer at " . $this->printerName);
            }
        }
    }

    /**
     * Resolve a hostname/computer name to an IP address dynamically.
     * Supports IPv4, IPv6, localhost, DNS, mDNS (.local), Tailscale/JSVPN mesh, and NetBIOS/ARP.
     */
    public static function resolveHostnameToIp($hostname)
    {
        if (empty($hostname)) return $hostname;
        
        $hostname = trim($hostname, "\\ ");
        
        // 1. If already valid IPv4 or IPv6, return immediately
        if (filter_var($hostname, FILTER_VALIDATE_IP)) {
            return $hostname;
        }

        if (strtolower($hostname) === 'localhost') {
            return '127.0.0.1';
        }

        // Cache resolution for 5 minutes to keep printing instantaneous (0 ms)
        $cacheKey = "printer_resolved_ip_" . md5($hostname);
        try {
            if (class_exists('\Illuminate\Support\Facades\Cache')) {
                $cached = \Illuminate\Support\Facades\Cache::get($cacheKey);
                if ($cached && filter_var($cached, FILTER_VALIDATE_IP)) {
                    return $cached;
                }
            }
        } catch (\Throwable $e) {}

        // 2. Try standard DNS / gethostbyname
        $ip = @gethostbyname($hostname);
        if ($ip !== $hostname && filter_var($ip, FILTER_VALIDATE_IP)) {
            self::cacheResolvedIp($cacheKey, $ip);
            return $ip;
        }

        // 3. Try .local (mDNS)
        $ipLocal = @gethostbyname($hostname . '.local');
        if ($ipLocal !== ($hostname . '.local') && filter_var($ipLocal, FILTER_VALIDATE_IP)) {
            self::cacheResolvedIp($cacheKey, $ipLocal);
            return $ipLocal;
        }

        // 4. Windows NetBIOS + ARP + Tailscale resolution
        if (PHP_OS_FAMILY === 'Windows') {
            // Check Tailscale / JSVPN Mesh status
            $tailscale = @shell_exec("tailscale status 2>&1");
            if ($tailscale && preg_match('/([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\s+' . preg_quote(strtolower($hostname), '/') . '\b/i', $tailscale, $mTs)) {
                self::cacheResolvedIp($cacheKey, $mTs[1]);
                return $mTs[1];
            }

            // Query NetBIOS for MAC address
            $nbtOutput = @shell_exec("nbtstat -a " . escapeshellarg($hostname));
            if ($nbtOutput && preg_match('/MAC\s*=\s*([0-9a-fA-F\-]{17})/i', $nbtOutput, $m)) {
                $mac = strtolower(str_replace(':', '-', trim($m[1])));

                // Find candidate IPs in ARP table with this MAC
                $arpOutput = @shell_exec("arp -a");
                if ($arpOutput && preg_match_all('/([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})\s+' . preg_quote($mac, '/') . '/i', $arpOutput, $mArp)) {
                    $candidateIps = $mArp[1];
                    if (count($candidateIps) === 1) {
                        self::cacheResolvedIp($cacheKey, $candidateIps[0]);
                        return $candidateIps[0];
                    }

                    // Test which IP is responding to SMB port 445 / 139
                    foreach ($candidateIps as $candidate) {
                        $fp = @fsockopen($candidate, 445, $errno, $errstr, 0.3);
                        if ($fp) {
                            fclose($fp);
                            self::cacheResolvedIp($cacheKey, $candidate);
                            return $candidate;
                        }
                        $fp = @fsockopen($candidate, 139, $errno, $errstr, 0.3);
                        if ($fp) {
                            fclose($fp);
                            self::cacheResolvedIp($cacheKey, $candidate);
                            return $candidate;
                        }
                    }

                    if (!empty($candidateIps[0])) {
                        self::cacheResolvedIp($cacheKey, $candidateIps[0]);
                        return $candidateIps[0];
                    }
                }
            }
        }

        return $hostname;
    }

    private static function cacheResolvedIp($key, $ip)
    {
        try {
            if (class_exists('\Illuminate\Support\Facades\Cache')) {
                \Illuminate\Support\Facades\Cache::put($key, $ip, now()->addMinutes(5));
            }
        } catch (\Throwable $e) {}
    }

    protected function sendToWin32Spooler($printerName, $data)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), "escraw");
        file_put_contents($tmpFile, $data);

        $escapedTmpFile = str_replace("'", "''", $tmpFile);
        $escapedPrinterName = str_replace("'", "''", $printerName);

        $psScript = <<<POWERSHELL
Add-Type -TypeDefinition @"
using System;
using System.IO;
using System.Runtime.InteropServices;

public class RawPrinterW {
    [StructLayout(LayoutKind.Sequential, CharSet = CharSet.Unicode)]
    public class DOCINFOW {
        [MarshalAs(UnmanagedType.LPWStr)] public string pDocName;
        [MarshalAs(UnmanagedType.LPWStr)] public string pOutputFile;
        [MarshalAs(UnmanagedType.LPWStr)] public string pDataType;
    }
    [DllImport("winspool.Drv", EntryPoint = "OpenPrinterW", SetLastError = true, CharSet = CharSet.Unicode, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool OpenPrinter([MarshalAs(UnmanagedType.LPWStr)] string szPrinter, out IntPtr hPrinter, IntPtr pd);

    [DllImport("winspool.Drv", EntryPoint = "ClosePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool ClosePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartDocPrinterW", SetLastError = true, CharSet = CharSet.Unicode, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartDocPrinter(IntPtr hPrinter, Int32 level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOW di);

    [DllImport("winspool.Drv", EntryPoint = "EndDocPrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool EndDocPrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "StartPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool StartPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "EndPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool EndPagePrinter(IntPtr hPrinter);

    [DllImport("winspool.Drv", EntryPoint = "WritePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)]
    public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, Int32 dwCount, out Int32 dwWritten);

    public static bool Send(string pName, byte[] bytes) {
        Int32 written = 0;
        IntPtr h = new IntPtr(0);
        DOCINFOW di = new DOCINFOW();
        bool ok = false;

        di.pDocName = "POS Ticket";
        di.pDataType = "RAW";

        if (OpenPrinter(pName, out h, IntPtr.Zero)) {
            if (StartDocPrinter(h, 1, di)) {
                if (StartPagePrinter(h)) {
                    IntPtr p = Marshal.AllocCoTaskMem(bytes.Length);
                    Marshal.Copy(bytes, 0, p, bytes.Length);
                    ok = WritePrinter(h, p, bytes.Length, out written);
                    Marshal.FreeCoTaskMem(p);
                    EndPagePrinter(h);
                }
                EndDocPrinter(h);
            }
            ClosePrinter(h);
        }
        return ok;
    }
}
"@

\$bytes = [System.IO.File]::ReadAllBytes('{$escapedTmpFile}')
\$ok = [RawPrinterW]::Send('{$escapedPrinterName}', \$bytes)
if (\$ok) {
    exit 0
} else {
    exit 1
}
POWERSHELL;

        $psFile = tempnam(sys_get_temp_dir(), "psp") . ".ps1";
        file_put_contents($psFile, $psScript);

        $cmd = 'powershell -NoProfile -ExecutionPolicy Bypass -File "' . $psFile . '" 2>&1';
        exec($cmd, $out, $ret);

        if ($ret !== 0) {
            \Illuminate\Support\Facades\Log::warning("sendToWin32Spooler failed for {$printerName} with exit code {$ret}: " . implode(" | ", $out));
        } else {
            \Illuminate\Support\Facades\Log::info("sendToWin32Spooler succeeded for {$printerName}: " . implode(" | ", $out));
        }

        @unlink($psFile);
        @unlink($tmpFile);

        return ($ret === 0);
    }

    protected function getCurrentPlatform()
    {
        if (PHP_OS == "WINNT") return self::PLATFORM_WIN;
        if (PHP_OS == "Darwin") return self::PLATFORM_MAC;
        return self::PLATFORM_LINUX;
    }

    public function read($len) { return false; }

    public function write($data)
    {
        $this->buffer[] = $data;
    }
    
    protected function runCommand($command, &$outputStr, &$errorStr)
    {
        $descriptors = [0 => ["pipe", "r"], 1 => ["pipe", "w"], 2 => ["pipe", "w"]];
        $process = proc_open($command, $descriptors, $fd);
        if (is_resource($process)) {
            fclose($fd[0]);
            $outputStr = stream_get_contents($fd[1]);
            fclose($fd[1]);
            $errorStr = stream_get_contents($fd[2]);
            fclose($fd[2]);
            return proc_close($process);
        }
        return -1;
    }
}
