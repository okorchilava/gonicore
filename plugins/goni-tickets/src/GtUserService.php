<?php
declare(strict_types=1);

namespace GoniTickets;

use GoniCore\Core\Database\QueryBuilder;

final class GtUserService
{
    private const SESSION_KEY = 'gt_uid';

    public function __construct(private readonly QueryBuilder $qb) {}

    // ── Register ──────────────────────────────────────────────────────────────

    /** @return array<string,mixed>|string  user row on success, error string on failure */
    public function register(string $email, string $name, string $password, string $phone = ''): array|string
    {
        $email = mb_strtolower(trim($email));
        $name  = trim($name);
        $phone = trim($phone);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) return 'ელ-ფოსტა არასწორია.';
        if (mb_strlen($name) < 2)                       return 'სახელი მინიმუმ 2 სიმბოლოა.';
        if (mb_strlen($password) < 6)                   return 'პაროლი მინიმუმ 6 სიმბოლოა.';

        if ($this->qb->table('gt_users')->where('email', '=', $email)->first()) {
            return 'ეს ელ-ფოსტა უკვე დარეგისტრირებულია.';
        }

        $id = (int) $this->qb->table('gt_users')->insert([
            'email'         => $email,
            'name'          => $name,
            'phone'         => $phone,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        $this->writeSession($id);
        return $this->qb->table('gt_users')->where('id', '=', $id)->first() ?: [];
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    /** @return array<string,mixed>|string  user row on success, error string on failure */
    public function login(string $email, string $password): array|string
    {
        $email = mb_strtolower(trim($email));
        $user  = $this->qb->table('gt_users')->where('email', '=', $email)->first();

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            return 'ელ-ფოსტა ან პაროლი არასწორია.';
        }

        $this->writeSession((int) $user['id']);
        return $user;
    }

    // ── Session ───────────────────────────────────────────────────────────────

    public function logout(): void
    {
        $this->boot();
        unset($_SESSION[self::SESSION_KEY]);
    }

    public function isLoggedIn(): bool
    {
        $this->boot();
        return isset($_SESSION[self::SESSION_KEY]);
    }

    public function currentUser(): ?array
    {
        $this->boot();
        $id = $_SESSION[self::SESSION_KEY] ?? null;
        if ($id === null) return null;
        return $this->qb->table('gt_users')->where('id', '=', (int) $id)->first() ?: null;
    }

    private function writeSession(int $id): void
    {
        $this->boot();
        $_SESSION[self::SESSION_KEY] = $id;
    }

    private function boot(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
    }
}
