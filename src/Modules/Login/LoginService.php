<?php

declare(strict_types=1);

namespace GoniCore\Modules\Login;

use GoniCore\Modules\User\UserRepository;

/**
 * Handles credential verification, session creation, remember-me tokens
 * and logout.
 */
final class LoginService
{
    private const REMEMBER_COOKIE  = 'gc_remember';
    private const REMEMBER_DAYS    = 30;

    public function __construct(
        private readonly UserRepository $users,
        private readonly SessionManager $session,
    ) {}

    // ── Login ─────────────────────────────────────────────────────────────────

    /**
     * Attempt login with an identifier (email / username / phone) + password.
     *
     * @return array{success: bool, error: ?string}
     */
    public function attempt(string $identifier, string $password, bool $remember = false): array
    {
        $row = $this->users->findByIdentifier($identifier);

        // Use a fake hash to prevent timing attacks when the user is not found.
        $hash = $row['password'] ?? '$2y$10$invalidhashtopreventtimingattac';

        if ($row === null || !password_verify($password, (string) $hash)) {
            return ['success' => false, 'error' => 'Invalid credentials. Please try again.'];
        }

        $userId = (int) $row['id'];
        $this->session->setUserId($userId);

        if ($remember) {
            $this->setRememberCookie($userId);
        }

        return ['success' => true, 'error' => null];
    }

    // ── Remember-me ───────────────────────────────────────────────────────────

    /**
     * Generate a secure random token, store its hash in DB, set cookie.
     */
    private function setRememberCookie(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        $hash  = hash('sha256', $token);

        $this->users->setRememberToken($userId, $hash);

        setcookie(
            self::REMEMBER_COOKIE,
            $userId . '|' . $token,
            time() + (86400 * self::REMEMBER_DAYS),
            '/',
            '',
            false, // set to true in production (HTTPS)
            true,  // HttpOnly
        );
    }

    /**
     * If no active session exists, check for a valid remember-me cookie
     * and restore the session automatically.
     */
    public function tryRememberLogin(): void
    {
        if ($this->session->isLoggedIn()) {
            return;
        }

        $cookie = $_COOKIE[self::REMEMBER_COOKIE] ?? null;
        if ($cookie === null) {
            return;
        }

        [$userId, $token] = array_pad(explode('|', $cookie, 2), 2, '');
        if (!$userId || !$token) {
            return;
        }

        $row = $this->users->findById((int) $userId);
        if ($row === null) {
            return;
        }

        $storedHash = $row['remember_token'] ?? '';
        if (!hash_equals((string) $storedHash, hash('sha256', $token))) {
            return;
        }

        // Valid token — restore session and rotate cookie
        $this->session->setUserId((int) $userId);
        $this->setRememberCookie((int) $userId);
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function logout(): void
    {
        $userId = $this->session->getUserId();

        if ($userId !== null) {
            $this->users->clearRememberToken($userId);
        }

        $this->session->destroy();

        // Expire the remember cookie
        setcookie(self::REMEMBER_COOKIE, '', time() - 3600, '/');
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    public function isLoggedIn(): bool
    {
        return $this->session->isLoggedIn();
    }

    public function currentUserId(): ?int
    {
        return $this->session->getUserId();
    }
}
