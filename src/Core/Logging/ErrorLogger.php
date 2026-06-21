<?php

declare(strict_types=1);

namespace GoniCore\Core\Logging;

use Throwable;

/**
 * File-based error logger.
 *
 * Captures PHP errors, warnings, uncaught exceptions and fatal shutdown errors
 * and appends them to a daily log file in the storage/logs directory
 * (gc-YYYY-MM-DD.log). The manage panel reads these files on the Logs page.
 *
 * Register it once during bootstrap:
 *   $logger = new ErrorLogger(__DIR__ . '/../storage/logs');
 *   $logger->register();
 */
final class ErrorLogger
{
    private static ?self $instance = null;

    public function __construct(private readonly string $logDir) {}

    /** Globally reachable instance (set by register()). */
    public static function instance(): ?self
    {
        return self::$instance;
    }

    /**
     * Install PHP error / exception / shutdown handlers.
     * Existing handlers are chained so we never change app behaviour — we only
     * observe and record.
     */
    public function register(): void
    {
        self::$instance = $this;

        set_error_handler(function (int $severity, string $message, string $file = '', int $line = 0): bool {
            // Respect the configured error_reporting() level.
            if (!(error_reporting() & $severity)) {
                return false;
            }
            $this->write($this->severityName($severity), $message, $file, $line);
            return false; // let PHP's normal handling continue
        });

        set_exception_handler(function (Throwable $e): void {
            $this->logThrowable($e);
        });

        register_shutdown_function(function (): void {
            $err = error_get_last();
            if ($err !== null && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
                $this->write('fatal', $err['message'], $err['file'] ?? '', (int) ($err['line'] ?? 0));
            }
        });
    }

    /** Log a Throwable with its file/line and stack trace. */
    public function logThrowable(Throwable $e): void
    {
        $this->write(
            'error',
            $e::class . ': ' . $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString(),
        );
    }

    /**
     * Log an arbitrary message.
     *
     * @param array<string,mixed> $context
     */
    public function log(string $level, string $message, array $context = []): void
    {
        if ($context !== []) {
            $message .= ' ' . json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        $this->write($level, $message);
    }

    // ── Internal ────────────────────────────────────────────────────────────

    private function write(string $level, string $message, string $file = '', int $line = 0, ?string $trace = null): void
    {
        try {
            if (!is_dir($this->logDir)) {
                @mkdir($this->logDir, 0775, true);
            }

            $entry  = '[' . date('Y-m-d H:i:s') . '] ' . strtoupper($level) . ': ' . $this->oneLine($message);
            if ($file !== '') {
                $entry .= ' in ' . $file . ($line > 0 ? ':' . $line : '');
            }
            $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'CLI'));
            $uri    = (string) ($_SERVER['REQUEST_URI'] ?? '');
            if ($uri !== '') {
                $entry .= ' [' . $method . ' ' . $this->oneLine($uri) . ']';
            }
            $entry .= PHP_EOL;
            if ($trace !== null && $trace !== '') {
                $entry .= $trace . PHP_EOL;
            }

            @file_put_contents(
                $this->logDir . '/gc-' . date('Y-m-d') . '.log',
                $entry,
                FILE_APPEND | LOCK_EX,
            );
        } catch (Throwable) {
            // Logging must never throw.
        }
    }

    private function oneLine(string $s): string
    {
        return trim(preg_replace('/\s+/', ' ', $s) ?? $s);
    }

    private function severityName(int $severity): string
    {
        return match ($severity) {
            E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR => 'error',
            E_WARNING, E_CORE_WARNING, E_COMPILE_WARNING, E_USER_WARNING => 'warning',
            E_NOTICE, E_USER_NOTICE        => 'notice',
            E_DEPRECATED, E_USER_DEPRECATED => 'deprecated',
            default                         => 'error',
        };
    }
}
