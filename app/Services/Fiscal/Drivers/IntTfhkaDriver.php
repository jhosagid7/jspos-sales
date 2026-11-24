<?php

namespace App\Services\Fiscal\Drivers;

use App\Services\Fiscal\ValueObjects\FiscalResponse;
use RuntimeException;
use Symfony\Component\Process\Process;

/**
 * Driver for the TFHKA "IntTFHKA.exe" command-line bridge (SDK A, Windows).
 *
 * Mirrors the official SDK (TfhkaPHP.php):
 *   - port is stored in `Puerto.dat` next to the exe
 *   - `SendFileCmd(<file>`            executes a file of command lines   -> Retorno.txt
 *   - `UploadStatusCmd(S1 <file>`     downloads a status register        -> <file>
 *   - `CheckFprinter()`               tests the connection               -> Retorno.txt
 * Everything runs with cwd = exe directory, using relative file names.
 *
 * A file lock serialises access: the exe shares Retorno.txt/Puerto.dat, and the
 * printer must be driven by a single synchronous thread (Manual V8.5.0).
 *
 * NOTE: the exact text returned in Retorno.txt for SendFileCmd is undocumented in
 * the SDK; success detection is intentionally tolerant and MUST be validated
 * against the virtual printer (VFP HKA, COM99) before production.
 */
final class IntTfhkaDriver implements FiscalPrinterDriverInterface
{
    /** @var callable(array<int,string>, string, int): void */
    private $runner;

    /**
     * @param string        $exeDir  Directory containing IntTFHKA.exe
     * @param string        $port    Serial port, e.g. COM5 (COM99 for the virtual printer)
     * @param callable|null $runner  fn(array $argv, string $cwd, int $timeout): void — injectable for tests
     */
    public function __construct(
        private readonly string $exeDir,
        private readonly string $port,
        ?callable $runner = null,
        private readonly int $timeout = 60,
    ) {
        if (!preg_match('/^COM\d{1,3}$/i', $port)) {
            throw new \InvalidArgumentException("Invalid serial port '{$port}'.");
        }
        $this->runner = $runner ?? [$this, 'runProcess'];
    }

    public function send(string $command): FiscalResponse
    {
        return $this->sendBatch([$command]);
    }

    public function sendBatch(array $commands): FiscalResponse
    {
        if ($commands === []) {
            return FiscalResponse::failure('Empty command batch.');
        }

        return $this->locked(function () use ($commands) {
            $file = 'batch_' . bin2hex(random_bytes(6)) . '.txt';
            $path = $this->path($file);

            // SDK sample: lines joined by "\n", ANSI (Windows-1252) encoding.
            file_put_contents($path, $this->toAnsi(implode("\n", $commands)));

            try {
                $reply = $this->exec(['SendFileCmd(' . $file]);
            } finally {
                @unlink($path);
            }

            if (!$this->isAccepted($reply)) {
                return FiscalResponse::failure("Printer rejected batch: '{$reply}'", $reply, ['sent' => 0]);
            }

            return FiscalResponse::ok($reply, ['sent' => count($commands)]);
        });
    }

    public function readStatus(int $n = 1): FiscalResponse
    {
        if ($n < 1 || $n > 6) {
            throw new \InvalidArgumentException('Status register must be S1..S6.');
        }

        return $this->locked(function () use ($n) {
            $file = 'status_' . bin2hex(random_bytes(6)) . '.txt';
            $path = $this->path($file);

            try {
                $this->exec(['UploadStatusCmd(S' . $n, $file]);
                $raw = '';
                if (is_file($path)) {
                    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    $raw = count($lines) > 1 ? implode(' ', array_map('trim', $lines)) : $this->firstLine((string) file_get_contents($path));
                }
            } finally {
                @unlink($path);
            }

            return $raw === ''
                ? FiscalResponse::failure("No data returned for status S{$n}.")
                : FiscalResponse::ok($raw);
        });
    }

    public function isConnected(): bool
    {
        try {
            return $this->locked(fn () => str_starts_with($this->exec(['CheckFprinter()']), 'T'));
        } catch (\Throwable) {
            return false;
        }
    }

    public function driverName(): string
    {
        return 'intTfhka';
    }

    // ---------------------------------------------------------------- internals

    /** Run the exe and return the last line of Retorno.txt. */
    private function exec(array $args): string
    {
        $this->ensurePort();
        @unlink($this->path('Retorno.txt'));

        ($this->runner)(array_merge([$this->path('IntTFHKA.exe')], $args), $this->exeDir, $this->timeout);

        $ret = $this->path('Retorno.txt');

        return is_file($ret) ? $this->lastLine((string) file_get_contents($ret)) : '';
    }

    /** Puerto.dat must contain only the port name (SDK SetPort). */
    private function ensurePort(): void
    {
        $file = $this->path('Puerto.dat');
        if (!is_file($file) || trim((string) file_get_contents($file)) !== strtoupper($this->port)) {
            file_put_contents($file, strtoupper($this->port));
        }
    }

    private function isAccepted(string $reply): bool
    {
        $reply = trim($reply);
        if ($reply === '' || preg_match('/^(NAK|F)/i', $reply)) {
            return false;
        }

        return (bool) preg_match('/^(ASK|T|\d)/i', $reply);
    }

    private function locked(callable $fn): FiscalResponse|bool
    {
        $lock = fopen($this->path('.fiscal.lock'), 'c');
        if ($lock === false || !flock($lock, LOCK_EX)) {
            throw new RuntimeException('Could not acquire fiscal printer lock.');
        }

        try {
            return $fn();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function runProcess(array $argv, string $cwd, int $timeout): void
    {
        if (!is_file($argv[0])) {
            throw new RuntimeException("IntTFHKA.exe not found at {$argv[0]}");
        }

        $process = new Process($argv, $cwd, null, null, $timeout);
        $process->run();
    }

    private function path(string $file): string
    {
        return rtrim($this->exeDir, '\\/') . DIRECTORY_SEPARATOR . $file;
    }

    private function toAnsi(string $text): string
    {
        $out = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);

        return $out === false ? $text : $out;
    }

    private function lastLine(string $content): string
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($content)) ?: [];

        return trim((string) end($lines));
    }

    /** Status data keeps its leading padding: only strip line breaks. */
    private function firstLine(string $content): string
    {
        $lines = preg_split('/\r\n|\r|\n/', $content) ?: [];
        foreach ($lines as $l) {
            if (trim($l) !== '') {
                return rtrim($l, "\r\n");
            }
        }

        return '';
    }
}
