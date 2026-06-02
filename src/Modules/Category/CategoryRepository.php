<?php

declare(strict_types=1);

namespace GoniCore\Modules\Category;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Shared\Contracts\RepositoryInterface;

final class CategoryRepository implements RepositoryInterface
{
    private const TABLE = 'categories';

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
        return $this->qb->table(self::TABLE)->orderBy('name')->get();
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
