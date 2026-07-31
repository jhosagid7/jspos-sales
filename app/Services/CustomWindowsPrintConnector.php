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
        $printerName = $this->isLocal ? $this->printerName : ("\\\\" . $this->hostname . "\\" . $this->printerName);

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
            $device = "\\\\" . $this->hostname . "\\" . $this->printerName;
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

    protected function sendToWin32Spooler($printerName, $data)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), "escraw");
        file_put_contents($tmpFile, $data);

        $psScript = '$code = @"' . "\n"
                  . 'using System; using System.IO; using System.Runtime.InteropServices; ' . "\n"
                  . 'public class RawPrinter { ' . "\n"
                  . '[StructLayout(LayoutKind.Sequential, CharSet = CharSet.Ansi)] public class DOCINFOA { [MarshalAs(UnmanagedType.LPStr)] public string pDocName; [MarshalAs(UnmanagedType.LPStr)] public string pOutputFile; [MarshalAs(UnmanagedType.LPStr)] public string pDataType; } ' . "\n"
                  . '[DllImport("winspool.Drv", EntryPoint = "OpenPrinterA", SetLastError = true, CharSet = CharSet.Ansi, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)] public static extern bool OpenPrinter([MarshalAs(UnmanagedType.LPStr)] string szPrinter, out IntPtr hPrinter, IntPtr pd); ' . "\n"
                  . '[DllImport("winspool.Drv", EntryPoint = "ClosePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)] public static extern bool ClosePrinter(IntPtr hPrinter); ' . "\n"
                  . '[DllImport("winspool.Drv", EntryPoint = "StartDocPrinterA", SetLastError = true, CharSet = CharSet.Ansi, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)] public static extern bool StartDocPrinter(IntPtr hPrinter, Int32 level, [In, MarshalAs(UnmanagedType.LPStruct)] DOCINFOA di); ' . "\n"
                  . '[DllImport("winspool.Drv", EntryPoint = "EndDocPrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)] public static extern bool EndDocPrinter(IntPtr hPrinter); ' . "\n"
                  . '[DllImport("winspool.Drv", EntryPoint = "StartPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)] public static extern bool StartPagePrinter(IntPtr hPrinter); ' . "\n"
                  . '[DllImport("winspool.Drv", EntryPoint = "EndPagePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)] public static extern bool EndPagePrinter(IntPtr hPrinter); ' . "\n"
                  . '[DllImport("winspool.Drv", EntryPoint = "WritePrinter", SetLastError = true, ExactSpelling = true, CallingConvention = CallingConvention.StdCall)] public static extern bool WritePrinter(IntPtr hPrinter, IntPtr pBytes, Int32 dwCount, out Int32 dwWritten); ' . "\n"
                  . 'public static bool Send(string pName, byte[] bytes) { ' . "\n"
                  . 'Int32 written = 0; IntPtr h = new IntPtr(0); DOCINFOA di = new DOCINFOA(); bool ok = false; ' . "\n"
                  . 'di.pDocName = "POS Ticket"; di.pDataType = "RAW"; ' . "\n"
                  . 'if (OpenPrinter(pName, out h, IntPtr.Zero)) { if (StartDocPrinter(h, 1, di)) { if (StartPagePrinter(h)) { IntPtr p = Marshal.AllocCoTaskMem(bytes.Length); Marshal.Copy(bytes, 0, p, bytes.Length); ok = WritePrinter(h, p, bytes.Length, out written); Marshal.FreeCoTaskMem(p); EndPagePrinter(h); } EndDocPrinter(h); } ClosePrinter(h); } ' . "\n"
                  . 'return ok; } } ' . "\n"
                  . '"@' . "\n"
                  . 'Add-Type -TypeDefinition $code;' . "\n"
                  . '$bytes = [System.IO.File]::ReadAllBytes("' . str_replace('\\', '\\\\', $tmpFile) . '");' . "\n"
                  . '$ok = [RawPrinter]::Send("' . str_replace('\\', '\\\\', $printerName) . '", $bytes);' . "\n"
                  . 'if ($ok) { exit 0 } else { exit 1 }';

        $psFile = tempnam(sys_get_temp_dir(), "psp") . ".ps1";
        file_put_contents($psFile, $psScript);

        $cmd = 'powershell -NoProfile -ExecutionPolicy Bypass -File "' . $psFile . '"';
        exec($cmd, $out, $ret);

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
