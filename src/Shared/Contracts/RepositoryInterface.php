<?php

declare(strict_types=1);

namespace GoniCore\Shared\Contracts;

/**
 * Base contract for all data-access repositories in GoniCore.
 *
 * Implementations are free to use the QueryBuilder, raw PDO, or any
 * other persistence mechanism — this interface only defines the public API.
 *
 * Example:
 *   final class PostRepository implements RepositoryInterface
 *   {
 *       public function __construct(private readonly QueryBuilder $qb) {}
 *
 *       public function findById(int|string $id): ?array
 *       {
 *           return $this->qb->table('posts')->where('id', '=', $id)->first();
 *       }
 *       // ...
 *   }
 */
interface RepositoryInterface
{
    /**
     * Find a single record by its primary key.
     *
     * @return array<string, mixed>|null  The record, or null if not found.
     */
    public function findById(int|string $id): ?array;

    /**
     * Return all records.
     *
     * @return list<array<string, mixed>>
     */
    public function findAll(): array;

    /**
     * Persist a record (INSERT or UPDATE) and return its primary key.
     *
     * @param  array<string, mixed> $data
     * @return int|string           The ID of the saved record.
     */
    public function save(array $data): int|string;

    /**
     * Delete a record by its primary key.
     *
     * @return bool  True if a row was deleted, false if nothing matched.
     */
    public function delete(int|string $id): bool;
}
