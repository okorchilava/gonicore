<?php

declare(strict_types=1);

namespace GoniCore\Modules\Language;

use GoniCore\Core\Http\Request;

/**
 * Detects the active language and provides the t() translation function.
 *
 * Detection priority:
 *   1. ?lang= query param  → sets cookie, redirects
 *   2. gc_lang cookie
 *   3. Default language from DB
 *   4. 'en' fallback
 */
final class LanguageService
{
    private const COOKIE_NAME = 'gc_lang';
    private const COOKIE_DAYS = 365;

    private static string $currentCode = 'en';

    /** @var array<string, string> */
    private static array $translations = [];

    private static bool $loaded = false;

    public function __construct(
        private readonly LanguageRepository $repo,
    ) {}

    // ── Bootstrap ─────────────────────────────────────────────────────────────

    /**
     * Detect and set the active language for this request.
     * Call once, early in the request lifecycle (ThemeController, etc.)
     */
    public function boot(Request $request): void
    {
        if (self::$loaded) return;

        $cookieLang = $request->cookie(self::COOKIE_NAME);
        $code = is_string($cookieLang) ? $cookieLang : null;

        // Validate against DB
        if ($code) {
            $row = $this->repo->findByCode($code);
            if (!$row || !$row['is_active']) $code = null;
        }

        // Fall back to default
        if (!$code) {
            $default = $this->repo->defaultLanguage();
            $code    = $default ? (string) $default['code'] : 'en';
        }

        self::$currentCode = $code;
        $this->loadTranslations($code);
        self::$loaded = true;
    }

    // ── Switch ────────────────────────────────────────────────────────────────

    /**
     * Persist a language choice as a cookie.
     * Call before any output is sent.
     */
    public function switchTo(string $code, string $basePath = '/'): void
    {
        $row = $this->repo->findByCode($code);
        if (!$row || !$row['is_active']) return;

        // Use basePath as cookie scope so subdirectory installs work correctly.
        $cookiePath = ($basePath !== '' ? rtrim($basePath, '/') . '/' : '/');

        setcookie(
            self::COOKIE_NAME,
            $code,
            time() + (86400 * self::COOKIE_DAYS),
            $cookiePath,
            '',
            false,
            false,
        );
        self::$currentCode = $code;
        self::$loaded      = false; // allow re-load
    }

    // ── Translation ───────────────────────────────────────────────────────────

    /**
     * Translate a key. Falls back to English, then to the key itself.
     */
    public function t(string $key, array $replace = []): string
    {
        $value = self::$translations[$key] ?? null;

        if ($value === null && self::$currentCode !== 'en') {
            $this->loadTranslations('en');
            $value = self::$translations[$key] ?? $key;
        }

        $value ??= $key;

        foreach ($replace as $search => $rep) {
            $value = str_replace(':' . $search, (string) $rep, $value);
        }

        return $value;
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function currentCode(): string
    {
        return self::$currentCode;
    }

    public function getRepo(): LanguageRepository
    {
        return $this->repo;
    }

    /** @return list<array<string,mixed>> */
    public function activeLanguages(): array
    {
        return $this->repo->allActive();
    }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function loadTranslations(string $code): void
    {
        $file = dirname(__DIR__, 3) . '/lang/' . $code . '.php';
        if (is_file($file)) {
            $loaded = require $file;
            if (is_array($loaded)) {
                self::$translations = array_merge(self::$translations, $loaded);
            }
        }
    }
}
