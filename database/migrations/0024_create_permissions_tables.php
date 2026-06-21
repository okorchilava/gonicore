<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

/**
 * Creates the `permissions` catalogue and the `role_permissions` pivot
 * (many-to-many between roles and permissions), then seeds the default
 * permission set and wires the built-in roles to it:
 *
 *   admin  → every permission
 *   editor → content permissions (posts / pages / categories / media / widgets / menus)
 *   viewer → read-only (*.view) content permissions
 *
 * MySQL 5.7 compatible: InnoDB foreign keys with ON DELETE CASCADE, composite
 * primary key on the pivot, INSERT IGNORE for idempotent re-runs.
 */
return new class implements Migration
{
    /** @return array<int, array{0:string,1:string,2:string}>  [name, group, label] */
    private function catalogue(): array
    {
        return [
            // Posts
            ['posts.view',        'posts',      'View posts'],
            ['posts.create',      'posts',      'Create posts'],
            ['posts.edit',        'posts',      'Edit posts'],
            ['posts.delete',      'posts',      'Delete posts'],
            // Pages
            ['pages.view',        'pages',      'View pages'],
            ['pages.create',      'pages',      'Create pages'],
            ['pages.edit',        'pages',      'Edit pages'],
            ['pages.delete',      'pages',      'Delete pages'],
            // Categories
            ['categories.view',   'categories', 'View categories'],
            ['categories.create', 'categories', 'Create categories'],
            ['categories.edit',   'categories', 'Edit categories'],
            ['categories.delete', 'categories', 'Delete categories'],
            // Media
            ['media.view',        'media',      'View media'],
            ['media.upload',      'media',      'Upload media'],
            ['media.delete',      'media',      'Delete own media'],
            ['media.delete_any',  'media',      "Delete anyone's media"],
            // Widgets
            ['widgets.view',      'widgets',    'View widgets'],
            ['widgets.manage',    'widgets',    'Manage widgets'],
            // Menus
            ['menus.view',        'menus',      'View menus'],
            ['menus.manage',      'menus',      'Manage menus'],
            // Languages
            ['languages.view',    'languages',  'View languages'],
            ['languages.manage',  'languages',  'Manage languages'],
            // Users
            ['users.view',        'users',      'View users'],
            ['users.create',      'users',      'Create users'],
            ['users.edit',        'users',      'Edit users'],
            ['users.delete',      'users',      'Delete users'],
            // Settings
            ['settings.view',     'settings',   'View settings'],
            ['settings.manage',   'settings',   'Manage settings'],
            // Plugins
            ['plugins.view',      'plugins',    'View plugins'],
            ['plugins.manage',    'plugins',    'Manage plugins'],
            // Roles & permissions
            ['roles.view',        'roles',      'View roles'],
            ['roles.manage',      'roles',      'Manage roles & permissions'],
        ];
    }

    public function up(Connection $connection): void
    {
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `permissions` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`       VARCHAR(100) NOT NULL,
                `group`      VARCHAR(50)  NOT NULL DEFAULT 'general',
                `label`      VARCHAR(150) NOT NULL,
                `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `permissions_name_unique` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $connection->execute("
            CREATE TABLE IF NOT EXISTS `role_permissions` (
                `role_id`       INT UNSIGNED NOT NULL,
                `permission_id` INT UNSIGNED NOT NULL,
                PRIMARY KEY (`role_id`, `permission_id`),
                KEY `rp_permission_idx` (`permission_id`),
                CONSTRAINT `fk_rp_role`
                    FOREIGN KEY (`role_id`)       REFERENCES `roles`(`id`)       ON DELETE CASCADE,
                CONSTRAINT `fk_rp_permission`
                    FOREIGN KEY (`permission_id`) REFERENCES `permissions`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // 1. Seed the permission catalogue.
        foreach ($this->catalogue() as [$name, $group, $label]) {
            $connection->execute(
                "INSERT IGNORE INTO `permissions` (`name`, `group`, `label`) VALUES (?, ?, ?)",
                [$name, $group, $label]
            );
        }

        // 2. Resolve role + permission id maps.
        $roleId = [];
        foreach ($connection->query("SELECT id, name FROM `roles`") as $r) {
            $roleId[(string) $r['name']] = (int) $r['id'];
        }
        $permId = [];
        foreach ($connection->query("SELECT id, name FROM `permissions`") as $p) {
            $permId[(string) $p['name']] = (int) $p['id'];
        }

        $allPerms = array_keys($permId);

        // editor: everything except users / settings / plugins / roles management.
        $editorGroups = ['posts', 'pages', 'categories', 'media', 'widgets', 'menus'];
        $editorPerms  = array_values(array_filter($allPerms, function (string $name) use ($editorGroups): bool {
            $group = explode('.', $name)[0];
            if ($name === 'media.delete_any') {
                return false; // editors only delete their own media
            }
            return in_array($group, $editorGroups, true);
        }));
        $editorPerms[] = 'languages.view';

        // viewer: read-only content.
        $viewerPerms = ['posts.view', 'pages.view', 'categories.view', 'media.view'];

        $assignments = [
            'admin'  => $allPerms,
            'editor' => $editorPerms,
            'viewer' => $viewerPerms,
        ];

        foreach ($assignments as $roleName => $perms) {
            if (!isset($roleId[$roleName])) {
                continue;
            }
            foreach ($perms as $permName) {
                if (!isset($permId[$permName])) {
                    continue;
                }
                $connection->execute(
                    "INSERT IGNORE INTO `role_permissions` (`role_id`, `permission_id`) VALUES (?, ?)",
                    [$roleId[$roleName], $permId[$permName]]
                );
            }
        }
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS `role_permissions`');
        $connection->execute('DROP TABLE IF EXISTS `permissions`');
    }
};
