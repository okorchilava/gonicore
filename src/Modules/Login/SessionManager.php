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

    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('gc_session');
            session_start();
        }
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
