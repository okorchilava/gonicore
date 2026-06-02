<?php

declare(strict_types=1);

namespace GoniCore\Modules\Settings;

use GoniCore\Core\Database\QueryBuilder;

final class SettingsRepository
{
    private const TABLE = 'settings';

    /** @var array<string, string|null>|null */
    private ?array $cache = null;

    public function __construct(private readonly QueryBuilder $qb) {}

    // ── Read ──────────────────────────────────────────────────────────────────

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();
        // Return $default when key is absent OR value is NULL in the database.
        return (array_key_exists($key, $all) && $all[$key] !== null)
            ? $all[$key]
            : $default;
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        if ($this->cache !== null) return $this->cache;

        $rows = $this->qb->table(self::TABLE)->get();
        $this->cache = [];
        foreach ($rows as $row) {
            $this->cache[(string) $row['key']] = $row['value'];
        }
        return $this->cache;
    }

    // ── Write ─────────────────────────────────────────────────────────────────

    public function set(string $key, ?string $value): void
    {
        $existing = $this->qb->table(self::TABLE)->where('key', '=', $key)->first();
        if ($existing !== null) {
            $this->qb->table(self::TABLE)->where('key', '=', $key)->update(['value' => $value]);
        } else {
            $this->qb->table(self::TABLE)->insert(['key' => $key, 'value' => $value]);
        }
        if ($this->cache !== null) $this->cache[$key] = $value;
    }

    /** @param array<string, string|null> $data */
    public function bulk(array $data): void
    {
        foreach ($data as $key => $value) {
            $this->set($key, $value);
        }
    }
}
