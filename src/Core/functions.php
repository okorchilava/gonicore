<?php

declare(strict_types=1);

/**
 * GoniCore Global Plugin API
 *
 * WordPress-style convenience functions that delegate to the global
 * HookManager / Container instances registered during bootstrap.
 *
 * Available in plugin bootstrap.php files and anywhere after bootstrap.
 *
 * ── Hook API ─────────────────────────────────────────────────────────────────
 *
 *   gc_on('post.created', function(int $id, array $data) { ... }, 10);
 *   gc_emit('post.created', $id, $data);
 *
 *   gc_filter('the_content', function(string $html): string { return $html; }, 10);
 *   $html = gc_apply('the_content', $rawHtml);
 *
 * ── Settings API ─────────────────────────────────────────────────────────────
 *
 *   $name = gc_setting('site_name', 'My Site');
 *   gc_set_setting('site_name', 'New Name');
 *
 * ── Auth API ─────────────────────────────────────────────────────────────────
 *
 *   if (gc_is_logged_in()) { ... }
 *   $userId = gc_current_user_id();
 *   $user   = gc_current_user();
 */

use GoniCore\Core\Container\Container;
use GoniCore\Core\Hooks\HookManager;

// ── Actions ───────────────────────────────────────────────────────────────────

/**
 * Register a callback to run when $tag is emitted.
 *
 * @param callable $fn       Receives the args passed to gc_emit().
 * @param int      $priority Lower runs first. Default 10.
 */
function gc_on(string $tag, callable $fn, int $priority = 10): void
{
    HookManager::global()->on($tag, $fn, $priority);
}

/**
 * Fire all callbacks registered for $tag.
 */
function gc_emit(string $tag, mixed ...$args): void
{
    HookManager::global()->emit($tag, ...$args);
}

/**
 * Remove action callbacks for $tag.
 */
function gc_off(string $tag, ?int $priority = null): void
{
    HookManager::global()->off($tag, $priority);
}

/**
 * Return true if at least one action callback is registered for $tag.
 */
function gc_has(string $tag): bool
{
    return HookManager::global()->has($tag);
}

// ── Filters ───────────────────────────────────────────────────────────────────

/**
 * Register a filter callback for $tag.
 * The callback receives ($value, ...$extraArgs) and must return the (modified) value.
 *
 * @param callable $fn       Must return the (modified) value.
 * @param int      $priority Lower runs first. Default 10.
 */
function gc_filter(string $tag, callable $fn, int $priority = 10): void
{
    HookManager::global()->filter($tag, $fn, $priority);
}

/**
 * Pass $value through all filter callbacks registered for $tag.
 */
function gc_apply(string $tag, mixed $value, mixed ...$args): mixed
{
    return HookManager::global()->apply($tag, $value, ...$args);
}

/**
 * Remove filter callbacks for $tag.
 */
function gc_unfilter(string $tag, ?int $priority = null): void
{
    HookManager::global()->unfilter($tag, $priority);
}

// ── Settings API ─────────────────────────────────────────────────────────────

/**
 * Get a site setting by key.
 * Equivalent of WordPress's get_option().
 */
function gc_setting(string $key, mixed $default = null): mixed
{
    static $svc = null;
    $svc ??= Container::global()->get(\GoniCore\Modules\Settings\SettingsService::class);
    return $svc->get($key, $default);
}

/**
 * Update a site setting.
 * Equivalent of WordPress's update_option().
 */
function gc_set_setting(string $key, mixed $value): void
{
    static $svc = null;
    $svc ??= Container::global()->get(\GoniCore\Modules\Settings\SettingsService::class);
    $svc->set($key, (string) $value);
}

// ── Auth API ─────────────────────────────────────────────────────────────────

/** Return true if a user is currently logged in. */
function gc_is_logged_in(): bool
{
    return Container::global()
        ->get(\GoniCore\Modules\Login\LoginService::class)
        ->isLoggedIn();
}

/** Return the current user's ID, or null if not logged in. */
function gc_current_user_id(): ?int
{
    return Container::global()
        ->get(\GoniCore\Modules\Login\LoginService::class)
        ->currentUserId();
}

/** Return the current user's data array, or null if not logged in. */
function gc_current_user(): ?array
{
    $id = gc_current_user_id();
    if ($id === null) return null;
    return Container::global()
        ->get(\GoniCore\Modules\User\UserRepository::class)
        ->findById($id);
}

// ── URL helpers ───────────────────────────────────────────────────────────────

/**
 * Get the URL to a file inside a plugin directory.
 * Equivalent of WordPress's plugins_url().
 *
 * Usage:  gc_plugins_url('assets/style.css', __FILE__)
 */
function gc_plugins_url(string $path = '', string $pluginFile = ''): string
{
    $docRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');
    $abs     = $pluginFile
        ? rtrim(str_replace('\\', '/', dirname($pluginFile)), '/')
        : '';

    if ($abs && $docRoot && str_starts_with($abs, $docRoot)) {
        $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host    = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
        $relDir  = substr($abs, strlen($docRoot));
        $base    = $scheme . '://' . $host . $relDir;
        return $base . ($path ? '/' . ltrim($path, '/') : '');
    }

    // Fallback: site_url + relative path
    $siteUrl     = rtrim((string) gc_setting('site_url', ''), '/');
    $pluginFolder = $pluginFile ? basename(dirname($pluginFile)) : '';
    $base         = $siteUrl . '/plugins' . ($pluginFolder ? '/' . $pluginFolder : '');
    return $base . ($path ? '/' . ltrim($path, '/') : '');
}

// ── Plugin i18n ────────────────────────────────────────────────────────────────

/**
 * Build a translator bound to a plugin's OWN language pack.
 *
 * Plugins must NOT use the engine's /lang pack. Instead they ship their own
 * translations at  plugins/<plugin>/lang/<code>.php  and translate via this
 * helper. The returned callable follows the site's selected language, falling
 * back to the plugin's English pack, then to the key itself. A plugin with no
 * lang pack simply gets the keys back (so just write literal strings instead).
 *
 *   // plugins/my-plugin/bootstrap.php
 *   $t = gc_plugin_translator($pluginDir);
 *   echo $t('settings.title');
 *
 * @return callable(string,array<string,mixed>=):string
 */
function gc_plugin_translator(string $pluginDir): callable
{
    static $packs = [];

    $code = 'en';
    try {
        $code = Container::global()
            ->get(\GoniCore\Modules\Language\LanguageService::class)
            ->currentCode();
    } catch (\Throwable) {
        // Language service unavailable — fall back to English.
    }

    $dir      = rtrim(str_replace('\\', '/', $pluginDir), '/');
    $cacheKey = $dir . '|' . $code;

    if (!isset($packs[$cacheKey])) {
        $map = [];
        $enFile = $dir . '/lang/en.php';
        if (is_file($enFile)) {
            $d = require $enFile;
            if (is_array($d)) $map = $d;
        }
        if ($code !== 'en') {
            $codeFile = $dir . '/lang/' . $code . '.php';
            if (is_file($codeFile)) {
                $d = require $codeFile;
                if (is_array($d)) $map = array_merge($map, $d);
            }
        }
        $packs[$cacheKey] = $map;
    }

    $map = $packs[$cacheKey];

    return static function (string $key, array $replace = []) use ($map): string {
        $value = $map[$key] ?? $key;
        foreach ($replace as $search => $rep) {
            $value = str_replace(':' . $search, (string) $rep, $value);
        }
        return $value;
    };
}

// ── Container access ─────────────────────────────────────────────────────────

/**
 * Access the global DI container.
 * For advanced plugin use — prefer dedicated gc_* helpers when available.
 */
function gc_container(): Container
{
    return Container::global();
}
