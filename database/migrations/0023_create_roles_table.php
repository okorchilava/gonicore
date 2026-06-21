<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

/**
 * Creates the `roles` table and seeds the three built-in system roles.
 *
 * Roles replace the old free-standing ENUM on users.role. A role is a named
 * bundle of permissions (see 0024). System roles (is_system = 1) cannot be
 * deleted from the admin UI.
 *
 * MySQL 5.7 compatible: no JSON columns, no functional defaults, INSERT IGNORE
 * used for idempotent seeding.
 */
return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `roles` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`        VARCHAR(50)  NOT NULL,
                `label`       VARCHAR(100) NOT NULL,
                `description` VARCHAR(255) NULL DEFAULT NULL,
                `is_system`   TINYINT(1)   NOT NULL DEFAULT 0,
                `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `roles_name_unique` (`name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Seed the built-in system roles.
        $roles = [
            ['admin',  'Administrator', 'Full access to everything.',          1],
            ['editor', 'Editor',        'Manage content (posts, pages, media).', 1],
            ['viewer', 'Viewer',        'Read-only access to content.',         1],
        ];

        foreach ($roles as [$name, $label, $desc, $system]) {
            $connection->execute(
                "INSERT IGNORE INTO `roles` (`name`, `label`, `description`, `is_system`)
                 VALUES (?, ?, ?, ?)",
                [$name, $label, $desc, $system]
            );
        }
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS `roles`');
    }
};
