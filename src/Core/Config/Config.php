<?php

declare(strict_types=1);

namespace GoniCore\Core\Config;

use RuntimeException;

/**
 * Flat configuration registry with dot-notation access.
 *
 * Usage:
 *   $config = new Config();
 *   $config->loadFile(__DIR__ . '/../config/database.php', 'database');
 *   $config->set('app.debug', true);
 *   $config->get('database.host');          // '127.0.0.1'
 *   $config->get('database.port', 3306);    // with default
 *   $config->require('database.dbname');    // throws if missing
 */
final class Config
{
    /** @var array<string, mixed> */
    private array $data = [];

    // -------------------------------------------------------------------------
    // Loading
    // -------------------------------------------------------------------------

    /**
     * Require a PHP file that returns an array and merge it into the registry.
     *
     * @param string $namespace  Optional top-level key to nest the values under.
     *                           Pass '' to merge at root level.
     * @throws RuntimeException  If the file is missing or does not return an array.
     */
    public function loadFile(string $path, string $namespace = ''): void
    {
        if (!is_file($path)) {
            throw new RuntimeException("Config file not found: {$path}");
        }

        $values = require $path;

        if (!is_array($values)) {
            throw new RuntimeException(
                "Config file must return an array, got " . get_debug_type($values) . ": {$path}"
            );
        }

        if ($namespace !== '') {
            $existing = isset($this->data[$namespace]) && is_array($this->data[$namespace])
                ? $this->data[$namespace]
                : [];

            $this->data[$namespace] = array_replace_recursive($existing, $values);
        } else {
            $this->data = array_replace_recursive($this->data, $values);
        }
    }

    // -------------------------------------------------------------------------
    // Write
    // -------------------------------------------------------------------------

    /**
     * Set a value by dot-notation key.
     *
     * Example: $config->set('database.host', '10.0.0.1');
     */
    public function set(string $key, mixed $value): void
    {
        $this->setNested($this->data, explode('.', $key), $value);
    }

    // -------------------------------------------------------------------------
    // Read
    // -------------------------------------------------------------------------

    /**
     * Get a value by dot-notation key, returning $default if not found.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $current  = $this->data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return $default;
            }
            $current = $current[$segment];
        }

        return $current;
    }

    /**
     * Get a value and throw if it is missing or null.
     *
     * @throws RuntimeException
     */
    public function require(string $key): mixed
    {
        $value = $this->get($key);

        if ($value === null) {
            throw new RuntimeException("Required config key is missing: \"{$key}\"");
        }

        return $value;
    }

    /**
     * Return true when the key exists (even with a null value stored via set()).
     */
    public function has(string $key): bool
    {
        $segments = explode('.', $key);
        $current  = $this->data;

        foreach ($segments as $segment) {
            if (!is_array($current) || !array_key_exists($segment, $current)) {
                return false;
            }
            $current = $current[$segment];
        }

        return true;
    }

    /**
     * Return the entire config tree.
     *
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    /**
     * Recursively traverse and set a nested key.
     *
     * @param array<string, mixed> $data  Passed by reference.
     * @param string[]             $keys  Remaining key segments.
     */
    private function setNested(array &$data, array $keys, mixed $value): void
    {
        $key = array_shift($keys);

        if ($key === null) {
            return;
        }

        if (empty($keys)) {
            $data[$key] = $value;
            return;
        }

        if (!isset($data[$key]) || !is_array($data[$key])) {
            $data[$key] = [];
        }

        $this->setNested($data[$key], $keys, $value);
    }
}
