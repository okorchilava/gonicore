<?php

declare(strict_types=1);

namespace GoniCore\Modules\Login;

/**
 * Thin wrapper around PHP sessions.
 * Call start() once per request before reading or writing session data.
 */
final class SessionManager
{
    private const USER_KEY  = 'gc_user_id';
    private const FLASH_KEY = 'gc_flash';
    private const CSRF_KEY  = 'gc_csrf';

    private int $lifetimeSeconds = 0;

    public function configure(int $lifetimeMinutes): void
    {
        $this->lifetimeSeconds = max(0, $lifetimeMinutes) * 60;
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('gc_session');
            session_set_cookie_params([
                'lifetime' => $this->lifetimeSeconds > 0 ? $this->lifetimeSeconds : 0,
                'path'     => '/',
                'secure'   => self::isHttps(),
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            if ($this->lifetimeSeconds > 0) {
                ini_set('session.gc_maxlifetime', (string) $this->lifetimeSeconds);
            }
            session_start();
        }
    }

    public static function isHttps(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')
            || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443;
    }

    public function clearUserId(): void
    {
        $this->start();
        unset($_SESSION[self::USER_KEY]);
    }

    // ── Auth ──────────────────────────────────────────────────────────────────

    public function setUserId(int $id): void
    {
        $this->start();
        session_regenerate_id(true);
        $_SESSION[self::USER_KEY] = $id;
    }

    public function getUserId(): ?int
    {
        $this->start();
        $v = $_SESSION[self::USER_KEY] ?? null;
        return $v !== null ? (int) $v : null;
    }

    public function isLoggedIn(): bool
    {
        return $this->getUserId() !== null;
    }

    public function destroy(): void
    {
        $this->start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }

    // ── CSRF ──────────────────────────────────────────────────────────────────

    /** Lazily generate a per-session CSRF token. */
    public function csrfToken(): string
    {
        $this->start();
        if (empty($_SESSION[self::CSRF_KEY])) {
            $_SESSION[self::CSRF_KEY] = bin2hex(random_bytes(24));
        }
        return (string) $_SESSION[self::CSRF_KEY];
    }

    public function verifyCsrf(?string $token): bool
    {
        $this->start();
        $stored = (string) ($_SESSION[self::CSRF_KEY] ?? '');
        return $stored !== '' && is_string($token) && hash_equals($stored, $token);
    }

    // ── Generic session storage ───────────────────────────────────────────────

    public function put(string $key, mixed $value): void
    {
        $this->start();
        $_SESSION[$key] = $value;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->start();
        return $_SESSION[$key] ?? $default;
    }

    public function forget(string $key): void
    {
        $this->start();
        unset($_SESSION[$key]);
    }

    // ── Flash messages ────────────────────────────────────────────────────────

    public function flash(string $key, string $message): void
    {
        $this->start();
        $_SESSION[self::FLASH_KEY][$key] = $message;
    }

    public function getFlash(string $key): ?string
    {
        $this->start();
        $msg = $_SESSION[self::FLASH_KEY][$key] ?? null;
        unset($_SESSION[self::FLASH_KEY][$key]);
        return $msg;
    }
}
