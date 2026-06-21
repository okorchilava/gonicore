<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

/**
 * Links users to the new roles table.
 *
 *  • Converts users.role from an ENUM to VARCHAR(50) so custom role names
 *    created in the Role Manager are accepted (the ENUM only allowed the three
 *    built-ins). The string column is kept for backward compatibility / display.
 *  • Adds users.role_id (FK → roles.id, ON DELETE SET NULL) as the authoritative
 *    link used for permission resolution.
 *  • Backfills role_id from the existing role name.
 *
 * Guarded with INFORMATION_SCHEMA so it is safe to re-run (MySQL 5.7 has no
 * "ADD COLUMN IF NOT EXISTS").
 */
return new class implements Migration
{
    private function columnExists(Connection $connection, string $column): bool
    {
        $rows = $connection->query(
            "SELECT COLUMN_NAME
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = DATABASE()
                AND TABLE_NAME   = 'users'
                AND COLUMN_NAME  = ?",
            [$column]
        );
        return $rows !== [];
    }

    private function constraintExists(Connection $connection, string $name): bool
    {
        $rows = $connection->query(
            "SELECT CONSTRAINT_NAME
               FROM information_schema.TABLE_CONSTRAINTS
              WHERE TABLE_SCHEMA    = DATABASE()
                AND TABLE_NAME      = 'users'
                AND CONSTRAINT_NAME = ?",
            [$name]
        );
        return $rows !== [];
    }

    public function up(Connection $connection): void
    {
        // 1. Relax the ENUM to a VARCHAR so custom roles are storable.
        $connection->execute(
            "ALTER TABLE `users` MODIFY COLUMN `role` VARCHAR(50) NOT NULL DEFAULT 'viewer'"
        );

        // 2. Add role_id if missing.
        if (!$this->columnExists($connection, 'role_id')) {
            $connection->execute(
                "ALTER TABLE `users`
                    ADD COLUMN `role_id` INT UNSIGNED NULL DEFAULT NULL AFTER `role`"
            );
        }

        // 3. Backfill role_id from the role name.
        $connection->execute(
            "UPDATE `users` u
                JOIN `roles` r ON r.`name` = u.`role`
                SET u.`role_id` = r.`id`
              WHERE u.`role_id` IS NULL"
        );

        // 4. Add the foreign key (ON DELETE SET NULL keeps users if a role is removed).
        if (!$this->constraintExists($connection, 'fk_users_role')) {
            $connection->execute(
                "ALTER TABLE `users`
                    ADD CONSTRAINT `fk_users_role`
                    FOREIGN KEY (`role_id`) REFERENCES `roles`(`id`) ON DELETE SET NULL"
            );
        }
    }

    public function down(Connection $connection): void
    {
        if ($this->constraintExists($connection, 'fk_users_role')) {
            $connection->execute("ALTER TABLE `users` DROP FOREIGN KEY `fk_users_role`");
        }
        if ($this->columnExists($connection, 'role_id')) {
            $connection->execute("ALTER TABLE `users` DROP COLUMN `role_id`");
        }
        // Restore the original ENUM definition.
        $connection->execute(
            "ALTER TABLE `users`
                MODIFY COLUMN `role` ENUM('admin','editor','viewer') NOT NULL DEFAULT 'viewer'"
        );
    }
};
