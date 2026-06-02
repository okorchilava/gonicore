<?php

declare(strict_types=1);

namespace GoniCore\Modules\Manage;

use GoniCore\Core\Database\QueryBuilder;

final class TodoRepository
{
    private const TABLE = 'todos';

    public function __construct(private readonly QueryBuilder $qb) {}

    /** @return list<array<string, mixed>> */
    public function allForUser(int $userId): array
    {
        return $this->qb->table(self::TABLE)
            ->where('user_id', '=', $userId)
            ->orderBy('completed', 'ASC')
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public function create(int $userId, string $title): int|string
    {
        return $this->qb->table(self::TABLE)->insert([
            'user_id'   => $userId,
            'title'     => $title,
            'completed' => 0,
        ]);
    }

    public function toggle(int $id, int $userId): void
    {
        $row = $this->qb->table(self::TABLE)
            ->where('id', '=', $id)
            ->where('user_id', '=', $userId)
            ->first();

        if ($row === null) return;

        $this->qb->table(self::TABLE)
            ->where('id', '=', $id)
            ->update(['completed' => $row['completed'] ? 0 : 1]);
    }

    public function delete(int $id, int $userId): void
    {
        $this->qb->table(self::TABLE)
            ->where('id', '=', $id)
            ->where('user_id', '=', $userId)
            ->delete();
    }
}
