<?php

declare(strict_types=1);

namespace GoniCore\Modules\User;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Shared\Contracts\RepositoryInterface;

final class UserRepository implements RepositoryInterface
{
    private const TABLE = 'users';

    public function __construct(private readonly QueryBuilder $qb) {}

    public function findById(int|string $id): ?array
    {
        return $this->qb->table(self::TABLE)->where('id', '=', $id)->first();
    }

    public function findByEmail(string $email): ?array
    {
        return $this->qb->table(self::TABLE)->where('email', '=', $email)->first();
    }

    /**
     * Find a user by email, username, or phone — whichever matches.
     */
    public function findByIdentifier(string $identifier): ?array
    {
        $row = $this->qb->table(self::TABLE)->where('email', '=', $identifier)->first();
        if ($row !== null) return $row;

        $row = $this->qb->table(self::TABLE)->where('username', '=', $identifier)->first();
        if ($row !== null) return $row;

        return $this->qb->table(self::TABLE)->where('phone', '=', $identifier)->first();
    }

    public function setRememberToken(int $userId, string $tokenHash): void
    {
        $this->qb->table(self::TABLE)
            ->where('id', '=', $userId)
            ->update(['remember_token' => $tokenHash]);
    }

    public function clearRememberToken(int $userId): void
    {
        $this->qb->table(self::TABLE)
            ->where('id', '=', $userId)
            ->update(['remember_token' => null]);
    }

    public function findAll(): array
    {
        return $this->qb->table(self::TABLE)
            ->select('id', 'name', 'username', 'email', 'role', 'created_at')
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    /**
     * INSERT when $data has no 'id' key; UPDATE otherwise.
     *
     * @param array<string, mixed> $data
     */
    public function save(array $data): int|string
    {
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id'], $data['password']); // never overwrite password via generic save
            $this->qb->table(self::TABLE)->where('id', '=', $id)->update($data);
            return $id;
        }

        return $this->qb->table(self::TABLE)->insert($data);
    }

    /**
     * Update only the password field for a given user.
     */
    public function updatePassword(int $id, string $passwordHash): void
    {
        $this->qb->table(self::TABLE)
            ->where('id', '=', $id)
            ->update(['password' => $passwordHash]);
    }

    public function delete(int|string $id): bool
    {
        return $this->qb->table(self::TABLE)->where('id', '=', $id)->delete() > 0;
    }
}
