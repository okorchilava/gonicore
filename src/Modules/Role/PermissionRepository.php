<?php

declare(strict_types=1);

namespace GoniCore\Modules\Role;

use GoniCore\Core\Database\QueryBuilder;

/**
 * Read access to the permission catalogue.
 *
 * Permissions are seeded by migrations and are not user-editable at runtime
 * (they map 1:1 to capabilities checked in code). The admin UI lists them and
 * lets roles be granted/revoked individual permissions.
 */
final class PermissionRepository
{
    private const TABLE = 'permissions';

    public function __construct(private readonly QueryBuilder $qb) {}

    /** @return list<array<string,mixed>> */
    public function findAll(): array
    {
        return $this->qb->table(self::TABLE)->orderBy('group')->orderBy('name')->get();
    }

    /**
     * All permissions grouped by their `group` column, preserving order.
     *
     * @return array<string, list<array<string,mixed>>>
     */
    public function groupedByGroup(): array
    {
        $grouped = [];
        foreach ($this->findAll() as $perm) {
            $grouped[(string) $perm['group']][] = $perm;
        }
        return $grouped;
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        return $this->qb->table(self::TABLE)->where('id', '=', $id)->first();
    }

    /** @return list<int> all valid permission ids (for validating UI input) */
    public function allIds(): array
    {
        return array_map(
            static fn(array $r): int => (int) $r['id'],
            $this->qb->table(self::TABLE)->select('id')->get()
        );
    }
}
