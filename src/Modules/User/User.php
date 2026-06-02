<?php

declare(strict_types=1);

namespace GoniCore\Modules\User;

use DateTimeImmutable;

/**
 * Immutable User value object.
 * The password hash is intentionally excluded from toArray() / serialization.
 */
final readonly class User
{
    public function __construct(
        public int               $id,
        public string            $name,
        public string            $email,
        public string            $role,
        public DateTimeImmutable $createdAt,
    ) {}

    /**
     * Hydrate a User from a raw database row.
     *
     * @param array<string, mixed> $row
     */
    public static function fromRow(array $row): self
    {
        return new self(
            id:        (int)    $row['id'],
            name:      (string) $row['name'],
            email:     (string) $row['email'],
            role:      (string) $row['role'],
            createdAt: new DateTimeImmutable((string) $row['created_at']),
        );
    }

    /**
     * Return a safe public representation (no password).
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'email'      => $this->email,
            'role'       => $this->role,
            'created_at' => $this->createdAt->format('c'),
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isEditor(): bool
    {
        return in_array($this->role, ['admin', 'editor'], true);
    }
}
