<?php

declare(strict_types=1);

namespace GoniCore\Modules\Language;

use GoniCore\Core\Http\Request;

/**
 * Detects the active language and provides the t() translation helper.
 *
 * Translation files are loaded from multiple sources in priority order:
 *   1. Core engine:  /lang/{code}.php
 *   2. Active theme: /themes/{theme}/lang/{code}.php
 *   3. Plugins can merge via hook: lang.load.{code}
 *
 * Theme translations override core keys with the same name.
 *
 * Detection priority:
 *   1. gc_lang cookie
 *   2. Default language from DB
 *   3. 'en' hard fallback
 */
final class LanguageService
{
    private const COOKIE_NAME = 'gc_lang';
    private const COOKIE_DAYS = 365;
    private const DEFAULT_THEME = 'default';

    private static string $currentCode = 'en';

    /** @var array<string, array<string,string>>  keyed by language code */
    private static array $byCode = [];

    private static bool $booted = false;

    public function __construct(
        private readonly LanguageRepository $repo,
    ) {}

    // ── Bootstrap ─────────────────────────────────────────────────────────────

    public function boot(Request $request): void
    {
        if (self::$booted) return;
        $cookieLang = $request->cookie(self::COOKIE_NAME);
        $this->bootCode(is_string($cookieLang) && $cookieLang !== '' ? $cookieLang : null);
    }

    /**
     * Lazy boot used by t() when no controller called boot() explicitly
     * (e.g. plugin-rendered admin pages). Reads the language cookie from
     * $_COOKIE so the chosen language still applies — keeping the whole
     * admin panel visually consistent.
     */
    private function ensureBooted(): void
    {
        if (self::$booted) return;
        $cookie = $_COOKIE[self::COOKIE_NAME] ?? null;
        $this->bootCode(is_string($cookie) && $cookie !== '' ? $cookie : null);
    }

    /** Resolve the active language from a cookie value (or default) and load it. */
    private function bootCode(?string $code): void
    {
        if (self::$booted) return;

        try {
            if ($code) {
                $row = $this->repo->findByCode($code);
                if (!$row || !$row['is_active']) $code = null;
            }
            if (!$code) {
                $default = $this->repo->defaultLanguage();
                $code    = $default ? (string) $default['code'] : 'en';
            }
        } catch (\Throwable) {
            // DB unavailable / languages table missing — fall back gracefully.
            $code = $code ?: 'en';
        }

        self::$currentCode = $code;
        $this->loadTranslations($code);
        self::$booted = true;
    }

    // ── Switch ────────────────────────────────────────────────────────────────

    public function switchTo(string $code, string $basePath = ''): void
    {
        $row = $this->repo->findByCode($code);
        if (!$row || !$row['is_active']) return;

        $cookiePath = $basePath !== '' ? rtrim($basePath, '/') . '/' : '/';

        setcookie(
            self::COOKIE_NAME,
            $code,
            [
                'expires'  => time() + 86400 * self::COOKIE_DAYS,
                'path'     => $cookiePath,
                'httponly' => false,
                'samesite' => 'Lax',
            ],
        );

        self::$currentCode = $code;
        self::$booted      = false;
    }

    // ── Translation ───────────────────────────────────────────────────────────

    /**
     * Translate a key.
     *
     * Falls back to English, then returns the key itself.
     *
     * @param array<string,string> $replace  Named placeholders, e.g. ['name' => 'John']
     */
    public function t(string $key, array $replace = []): string
    {
        // Safety net: if no controller booted the language (some plugin pages),
        // load it now from the cookie so t() never leaks raw keys into the UI.
        if (!self::$booted) {
            $this->ensureBooted();
        }

        // Current language
        $value = self::$byCode[self::$currentCode][$key] ?? null;

        // English fallback
        if ($value === null && self::$currentCode !== 'en') {
            if (!isset(self::$byCode['en'])) {
                $this->loadTranslations('en');
            }
            $value = self::$byCode['en'][$key] ?? null;
        }

        $value ??= $key;

        foreach ($replace as $search => $rep) {
            $value = str_replace(':' . $search, (string) $rep, $value);
        }

        return $value;
    }

    // ── Accessors ─────────────────────────────────────────────────────────────

    public function currentCode(): string { if (!self::$booted) $this->ensureBooted(); return self::$currentCode; }

    public function getRepo(): LanguageRepository { return $this->repo; }

    /** @return list<array<string,mixed>> */
    public function activeLanguages(): array { return $this->repo->allActive(); }

    // ── Internal ──────────────────────────────────────────────────────────────

    private function loadTranslations(string $code): void
    {
        if (isset(self::$byCode[$code])) return;

        $root   = dirname(__DIR__, 3);
        $merged = [];

        // 1. Core / engine translations
        $coreFile = "{$root}/lang/{$code}.php";
        if (is_file($coreFile)) {
            $data = require $coreFile;
            if (is_array($data)) $merged = array_merge($merged, $data);
        }

        // 2. Active theme translations (override core keys)
        $themeFile = "{$root}/themes/" . self::DEFAULT_THEME . "/lang/{$code}.php";
        if (is_file($themeFile)) {
            $data = require $themeFile;
            if (is_array($data)) $merged = array_merge($merged, $data);
        }

        self::$byCode[$code] = $merged;
    }
}
