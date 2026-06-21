<?php

declare(strict_types=1);

namespace GoniCore\Modules\Role;

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;

/**
 * Data access for roles and the role ⇆ permission pivot.
 */
final class RoleRepository
{
    private const TABLE = 'roles';

    public function __construct(
        private readonly QueryBuilder $qb,
        private readonly Connection   $db,
    ) {}

    /** @return list<array<string,mixed>> */
    public function findAll(): array
    {
        return $this->qb->table(self::TABLE)->orderBy('name')->get();
    }

    /** @return array<string,mixed>|null */
    public function findById(int $id): ?array
    {
        return $this->qb->table(self::TABLE)->where('id', '=', $id)->first();
    }

    /** @return array<string,mixed>|null */
    public function findByName(string $name): ?array
    {
        return $this->qb->table(self::TABLE)->where('name', '=', $name)->first();
    }

    /** @param array<string,mixed> $data */
    public function create(array $data): int
    {
        return (int) $this->qb->table(self::TABLE)->insert($data);
    }

    /** @param array<string,mixed> $data */
    public function update(int $id, array $data): void
    {
        $this->qb->table(self::TABLE)->where('id', '=', $id)->update($data);
    }

    public function delete(int $id): void
    {
        // role_permissions rows are removed by the ON DELETE CASCADE foreign key.
        $this->qb->table(self::TABLE)->where('id', '=', $id)->delete();
    }

    /** Number of users currently assigned to this role. */
    public function userCount(int $id): int
    {
        return $this->qb->table('users')->where('role_id', '=', $id)->count();
    }

    // ── Pivot ──────────────────────────────────────────────────────────────────

    /** @return list<int>  permission ids granted to the role */
    public function permissionIds(int $roleId): array
    {
        $rows = $this->qb->table('role_permissions')
            ->select('permission_id')
            ->where('role_id', '=', $roleId)
            ->get();

        return array_map(static fn(array $r): int => (int) $r['permission_id'], $rows);
    }

    /** @return list<string>  permission names granted to the role */
    public function permissionNames(int $roleId): array
    {
        $rows = $this->db->query(
            "SELECT p.`name`
               FROM `role_permissions` rp
               JOIN `permissions` p ON p.`id` = rp.`permission_id`
              WHERE rp.`role_id` = ?",
            [$roleId]
        );

        return array_map(static fn(array $r): string => (string) $r['name'], $rows);
    }

    /**
     * Replace the role's permission set with exactly $permissionIds.
     *
     * @param list<int> $permissionIds
     */
    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $this->db->transact(function (Connection $db) use ($roleId, $permissionIds): void {
            $db->execute("DELETE FROM `role_permissions` WHERE `role_id` = ?", [$roleId]);

            foreach (array_unique(array_map('intval', $permissionIds)) as $pid) {
                $db->execute(
                    "INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (?, ?)",
                    [$roleId, $pid]
                );
            }
        });
    }
}
