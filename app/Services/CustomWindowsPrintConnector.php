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

    const REGEX_LOCAL = "/^(LPT\d|COM\d)$/";
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

        if (preg_match(self::REGEX_LOCAL, $dest) == 1) {
            if ($this->platform !== self::PLATFORM_WIN) {
                throw new BadMethodCallException("WindowsPrintConnector can only be used to print to a local printer ('".$dest."') on a Windows computer.");
            }
            $this->isLocal = true;
            $this->hostname = null;
            $this->printerName = $dest;
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
        if (!$this->isLocal) {
            $device = "\\\\" . $this->hostname . "\\" . $this->printerName;
            if ($this->userName !== null) {
                $user = "/user:" . ($this->workgroup != null ? ($this->workgroup . "\\") : "") . $this->userName;
                if ($this->userPassword == null) {
                    $command = sprintf("net use %s %s", escapeshellarg($device), escapeshellarg($user));
                } else {
                    $command = sprintf("net use %s %s %s", escapeshellarg($device), escapeshellarg($user), escapeshellarg($this->userPassword));
                }
                
                $ret = $this->runCommand($command, $outputStr, $errorStr);
                
                // If net use failed, we should know why. 
                // However, often it fails because "Multiple connections to a server... are not allowed".
                // In that case, we might still be able to print if it was already connected with same credentials.
                // Or we might need to net use /delete first? 
                // For now, let's keep going but if copy fails, we append the net use error to the exception.
                $netUseError = "";
                if ($ret != 0) {
                     $netUseError = " | net use error: " . trim($errorStr);
                }
            }
            
            $filename = tempnam(sys_get_temp_dir(), "escpos");
            file_put_contents($filename, $data);
            if (!copy($filename, $device)) {
                 unlink($filename);
                 $authInfo = $this->userName ? " with User: " . $this->userName : " (No Auth)";
                 throw new Exception("Failed to copy file to printer at $device $authInfo" . $netUseError);
            }
            unlink($filename);
        } else {
            if (file_put_contents($this->printerName, $data) === false) {
                throw new Exception("Failed to write file to printer at " . $this->printerName);
            }
        }
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
