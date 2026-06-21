<?php

declare(strict_types=1);

namespace GoniCore\Modules\Role;

use GoniCore\Modules\User\UserRepository;

/**
 * Central authorization gate.
 *
 * Resolves a user (or role) to its concrete set of permission names and answers
 * can()/roleCan() questions. Results are memoised per request. Permission
 * resolution is authoritative: it reads only the roles/permissions tables. If
 * those tables are missing the query fails loudly — run the migrations.
 *
 *   $auth->can($userId, 'posts.delete');
 *   $auth->roleCanByName('admin', 'users.edit');
 */
final class AuthorizationService
{
    /** @var array<int, list<string>> roleId => permission names */
    private array $cache = [];

    public function __construct(
        private readonly RoleRepository $roles,
        private readonly UserRepository $users,
    ) {}

    /** @return list<string> */
    public function permissionsForRole(int $roleId): array
    {
        return $this->cache[$roleId] ??= $this->roles->permissionNames($roleId);
    }

    public function roleCan(?int $roleId, string $permission): bool
    {
        if ($roleId === null || $roleId <= 0) {
            return false;
        }
        return in_array($permission, $this->permissionsForRole($roleId), true);
    }

    public function roleCanByName(?string $roleName, string $permission): bool
    {
        if ($roleName === null || $roleName === '') {
            return false;
        }
        $role = $this->roles->findByName($roleName);
        return $role !== null && $this->roleCan((int) $role['id'], $permission);
    }

    public function can(?int $userId, string $permission): bool
    {
        if ($userId === null || $userId <= 0) {
            return false;
        }
        $user = $this->users->findById($userId);
        return $user !== null && $this->userCan($user, $permission);
    }

    /** @param array<string,mixed> $user */
    public function userCan(array $user, string $permission): bool
    {
        $roleId = isset($user['role_id']) ? (int) $user['role_id'] : 0;
        if ($roleId > 0) {
            return $this->roleCan($roleId, $permission);
        }
        return $this->roleCanByName((string) ($user['role'] ?? ''), $permission);
    }

    /**
     * @param array<string,mixed> $user
     * @return list<string>
     */
    public function permissionsForUser(array $user): array
    {
        $roleId = isset($user['role_id']) ? (int) $user['role_id'] : 0;
        if ($roleId > 0) {
            return $this->permissionsForRole($roleId);
        }
        $role = $this->roles->findByName((string) ($user['role'] ?? ''));
        return $role !== null ? $this->permissionsForRole((int) $role['id']) : [];
    }
}
