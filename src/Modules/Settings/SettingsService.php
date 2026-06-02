<?php

declare(strict_types=1);

namespace GoniCore\Modules\Settings;

/**
 * Application-wide settings service.
 *
 * Call boot() once during bootstrap to apply system-level settings
 * (timezone, etc.) to the PHP runtime.
 */
final class SettingsService
{
    private static bool $booted = false;

    public function __construct(
        private readonly SettingsRepository $repo,
    ) {}

    // ── Bootstrap ─────────────────────────────────────────────────────────────

    /**
     * Apply settings that must be set early (timezone, etc.).
     * Safe to call multiple times — runs only once.
     */
    public function boot(): void
    {
        if (self::$booted) return;

        $timezone = $this->get('timezone', 'UTC');
        if ($timezone && in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            date_default_timezone_set($timezone);
        }

        self::$booted = true;
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->repo->get($key, $default);
    }

    public function set(string $key, ?string $value): void
    {
        $this->repo->set($key, $value);
    }

    /** @param array<string, string|null> $data */
    public function bulk(array $data): void
    {
        $this->repo->bulk($data);
    }

    /** @return array<string, string|null> */
    public function all(): array
    {
        return $this->repo->all();
    }

    // ── Typed helpers ─────────────────────────────────────────────────────────

    public function siteName(): string
    {
        return (string) $this->get('site_name', 'GoniCore');
    }

    public function siteTagline(): string
    {
        return (string) $this->get('site_tagline', '');
    }

    public function postsPerPage(): int
    {
        return (int) $this->get('posts_per_page', 9);
    }

    public function homepageType(): string
    {
        return (string) $this->get('homepage_type', 'posts');
    }

    public function homepagePageId(): ?int
    {
        $v = $this->get('homepage_page_id');
        return $v ? (int) $v : null;
    }

    public function postsPageId(): ?int
    {
        $v = $this->get('posts_page_id');
        return $v ? (int) $v : null;
    }

    public function timezone(): string
    {
        return (string) $this->get('timezone', 'UTC');
    }

    public function dateFormat(): string
    {
        return (string) $this->get('date_format', 'M j, Y');
    }

    public function timeFormat(): string
    {
        return (string) $this->get('time_format', 'H:i');
    }
}
