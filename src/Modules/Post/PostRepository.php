<?php

declare(strict_types=1);

namespace GoniCore\Modules\Post;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Shared\Contracts\RepositoryInterface;

final class PostRepository implements RepositoryInterface
{
    private const TABLE = 'posts';

    public function __construct(private readonly QueryBuilder $qb) {}

    public function findById(int|string $id): ?array
    {
        return $this->qb->table(self::TABLE)->where('id', '=', $id)->first();
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->qb->table(self::TABLE)->where('slug', '=', $slug)->first();
    }

    public function findAll(): array
    {
        return $this->qb->table(self::TABLE)->orderBy('created_at', 'DESC')->get();
    }

    /**
     * Return a base QueryBuilder for the posts table.
     * Controllers/services can add further WHERE clauses before calling get().
     */
    public function query(): QueryBuilder
    {
        return $this->qb->table(self::TABLE);
    }

    /** @param array<string, mixed> $data */
    public function save(array $data): int|string
    {
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
            $this->qb->table(self::TABLE)->where('id', '=', $id)->update($data);
            return $id;
        }

        return $this->qb->table(self::TABLE)->insert($data);
    }

    public function delete(int|string $id): bool
    {
        return $this->qb->table(self::TABLE)->where('id', '=', $id)->delete() > 0;
    }
}
