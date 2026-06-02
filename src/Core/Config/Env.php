<?php

declare(strict_types=1);

namespace GoniCore\Core\Config;

use RuntimeException;

/**
 * Native .env file parser — no external dependencies.
 *
 * Supported syntax:
 *   KEY=value              # bare value
 *   KEY="quoted value"     # double-quoted (escape sequences: \n \t \r \\ \")
 *   KEY='literal value'    # single-quoted (no escape processing)
 *   KEY=${OTHER}           # variable expansion (double-quoted and bare)
 *   # comment              # full-line comment
 *   KEY=value # inline     # inline comment stripped on bare values
 *   KEY=                   # empty value
 *   export KEY=value       # optional 'export' prefix (ignored)
 *
 * Values are stored in $_ENV and made accessible via getenv().
 * Already-set environment variables (e.g. from the OS) are NOT overwritten
 * unless load() is called with $override = true.
 */
final class Env
{
    /** @var array<string, string> Parsed key-value pairs from the last load(). */
    private static array $parsed = [];

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Parse and load a .env file into $_ENV / getenv().
     *
     * @throws RuntimeException if the file is not readable.
     */
    public static function load(string $path, bool $override = false): void
    {
        if (!is_file($path) || !is_readable($path)) {
            throw new RuntimeException("Env file not found or not readable: {$path}");
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES);

        if ($lines === false) {
            throw new RuntimeException("Failed to read env file: {$path}");
        }

        foreach ($lines as $lineNumber => $raw) {
            $line = trim($raw);

            // Skip blank lines and full-line comments.
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Strip optional 'export ' prefix.
            if (str_starts_with($line, 'export ')) {
                $line = ltrim(substr($line, 7));
            }

            if (!str_contains($line, '=')) {
                // Lines without '=' are silently ignored (prevents fatal errors
                // from malformed entries while still loading the rest of the file).
                continue;
            }

            [$key, $value] = self::parseLine($line, $lineNumber + 1);

            // Normalise key: strip whitespace, must be a valid identifier.
            $key = trim($key);

            if ($key === '' || !self::isValidKey($key)) {
                continue;
            }

            // Expand ${VAR} references after the full file has been parsed
            // so that forward-references within the same file work.
            self::$parsed[$key] = $value;

            if ($override || getenv($key) === false) {
                $expanded       = self::expand($value);
                $_ENV[$key]     = $expanded;
                putenv("{$key}={$expanded}");
            }
        }
    }

    /**
     * Return an env value, or $default if the variable is not set.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? getenv($key);

        return $value !== false ? $value : $default;
    }

    /**
     * Return an env value and throw if it is missing or empty.
     *
     * @throws RuntimeException
     */
    public static function require(string $key): string
    {
        $value = self::get($key);

        if ($value === null || $value === '') {
            throw new RuntimeException(
                "Required environment variable \"{$key}\" is not set."
            );
        }

        return (string) $value;
    }

    /**
     * Return true if the variable exists (even if its value is an empty string).
     */
    public static function has(string $key): bool
    {
        return isset($_ENV[$key]) || getenv($key) !== false;
    }

    /**
     * Return all variables loaded by the last load() call.
     *
     * @return array<string, string>
     */
    public static function all(): array
    {
        return self::$parsed;
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Split a raw line into [key, raw-value].
     *
     * @return array{0: string, 1: string}
     */
    private static function parseLine(string $line, int $lineNumber): array
    {
        $equalsPos = strpos($line, '=');

        // $equalsPos is guaranteed non-false here (checked by caller).
        $key      = substr($line, 0, (int) $equalsPos);
        $rawValue = substr($line, (int) $equalsPos + 1);

        $value = self::parseValue(trim($rawValue));

        return [$key, $value];
    }

    /**
     * Interpret the raw value portion of a KEY=... line.
     *
     * Handles double-quoted, single-quoted, and bare values.
     */
    private static function parseValue(string $raw): string
    {
        if ($raw === '') {
            return '';
        }

        // Double-quoted: process escape sequences.
        if (str_starts_with($raw, '"')) {
            $inner = self::extractQuoted($raw, '"');
            return self::unescape($inner);
        }

        // Single-quoted: literal — no escapes, no expansion.
        if (str_starts_with($raw, "'")) {
            return self::extractQuoted($raw, "'");
        }

        // Bare value: strip inline comment (# preceded by whitespace).
        $commentPos = self::findInlineComment($raw);
        if ($commentPos !== false) {
            $raw = rtrim(substr($raw, 0, $commentPos));
        }

        return $raw;
    }

    /**
     * Extract the inner content of a quoted string.
     * Handles escaped quote characters (\" or \') inside the string.
     */
    private static function extractQuoted(string $raw, string $quote): string
    {
        $i      = 1; // skip opening quote
        $result = '';
        $len    = strlen($raw);

        while ($i < $len) {
            $ch = $raw[$i];

            if ($ch === '\\' && $quote === '"' && $i + 1 < $len) {
                // Peek at the next character for double-quoted escape.
                $next = $raw[$i + 1];
                if ($next === $quote || $next === '\\') {
                    $result .= $next;
                    $i      += 2;
                    continue;
                }
            }

            if ($ch === $quote) {
                break; // closing quote reached
            }

            $result .= $ch;
            $i++;
        }

        return $result;
    }

    /**
     * Process standard escape sequences inside double-quoted values.
     */
    private static function unescape(string $value): string
    {
        return strtr($value, [
            '\\n'  => "\n",
            '\\t'  => "\t",
            '\\r'  => "\r",
            '\\\\' => '\\',
        ]);
    }

    /**
     * Expand ${VAR} and $VAR references using already-loaded values.
     */
    private static function expand(string $value): string
    {
        // ${VAR} syntax.
        $value = (string) preg_replace_callback(
            '/\$\{([A-Z_][A-Z0-9_]*)\}/i',
            static fn(array $m): string => (string) self::get($m[1], $m[0]),
            $value,
        );

        // $VAR syntax (not followed by word char).
        $value = (string) preg_replace_callback(
            '/\$([A-Z_][A-Z0-9_]*)(?![A-Z0-9_])/i',
            static fn(array $m): string => (string) self::get($m[1], $m[0]),
            $value,
        );

        return $value;
    }

    /**
     * Find the position of an inline comment (#) that is preceded by whitespace.
     * Returns false if no such comment is found.
     */
    private static function findInlineComment(string $raw): int|false
    {
        $len = strlen($raw);

        for ($i = 1; $i < $len; $i++) {
            if ($raw[$i] === '#' && ($raw[$i - 1] === ' ' || $raw[$i - 1] === "\t")) {
                return $i;
            }
        }

        return false;
    }

    /**
     * Allow only alphanumeric characters and underscores in keys,
     * matching typical shell-variable naming conventions.
     */
    private static function isValidKey(string $key): bool
    {
        return (bool) preg_match('/^[A-Z_][A-Z0-9_]*$/i', $key);
    }
}
