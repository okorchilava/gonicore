<?php

declare(strict_types=1);

namespace GoniCore\Modules\Auth;

use GoniCore\Core\Http\HttpException;
use GoniCore\Modules\User\User;
use GoniCore\Modules\User\UserRepository;
use RuntimeException;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly JwtService     $jwt,
    ) {}

    // -------------------------------------------------------------------------
    // Registration
    // -------------------------------------------------------------------------

    /**
     * Register a new user and return a token pair.
     *
     * @param  array{name: string, email: string, password: string, role?: string} $data
     * @return array{user: array<string, mixed>, token: string}
     * @throws HttpException  409 if the email is already registered.
     */
    public function register(array $data): array
    {
        if ($this->users->findByEmail((string) $data['email']) !== null) {
            throw new HttpException(409, 'This email address is already registered.');
        }

        $id = $this->users->save([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'password' => password_hash((string) $data['password'], PASSWORD_BCRYPT),
            'role'     => $data['role'] ?? 'viewer',
        ]);

        $row = $this->users->findById($id);

        if ($row === null) {
            throw new RuntimeException('Failed to retrieve newly created user.');
        }

        $user = User::fromRow($row);

        return [
            'user'  => $user->toArray(),
            'token' => $this->jwt->encode(['sub' => $user->id, 'role' => $user->role]),
        ];
    }

    // -------------------------------------------------------------------------
    // Login
    // -------------------------------------------------------------------------

    /**
     * Authenticate via email + password and return a signed JWT.
     *
     * @return array{user: array<string, mixed>, token: string}
     * @throws HttpException  401 on bad credentials.
     */
    public function login(string $email, string $password): array
    {
        $row = $this->users->findByEmail($email);

        // Use password_verify even on a fake hash to prevent timing attacks.
        $hash = $row['password'] ?? '$2y$10$invalidhashtopreventtimingattac';

        if ($row === null || !password_verify($password, (string) $hash)) {
            throw new HttpException(401, 'Invalid email or password.');
        }

        $user = User::fromRow($row);

        return [
            'user'  => $user->toArray(),
            'token' => $this->jwt->encode(['sub' => $user->id, 'role' => $user->role]),
        ];
    }

    // -------------------------------------------------------------------------
    // Current user
    // -------------------------------------------------------------------------

    /**
     * Resolve the authenticated user from a decoded JWT `sub` claim.
     *
     * @throws HttpException  401 if the user was deleted after the token was issued.
     */
    public function me(int $userId): User
    {
        $row = $this->users->findById($userId);

        if ($row === null) {
            throw new HttpException(401, 'Authenticated user no longer exists.');
        }

        return User::fromRow($row);
    }
}
