<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

/**
 * Adds optional login identifiers (username, phone) and
 * a remember-me token column to the users table.
 */
return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            ALTER TABLE `users`
                ADD COLUMN `username`       VARCHAR(100)  NULL DEFAULT NULL
                    AFTER `name`,
                ADD COLUMN `phone`          VARCHAR(30)   NULL DEFAULT NULL
                    AFTER `username`,
                ADD COLUMN `remember_token` VARCHAR(100)  NULL DEFAULT NULL
                    AFTER `password`,
                ADD UNIQUE KEY `users_username_unique` (`username`),
                ADD UNIQUE KEY `users_phone_unique`    (`phone`)
        ");
    }

    public function down(Connection $connection): void
    {
        $connection->execute("
            ALTER TABLE `users`
                DROP INDEX `users_username_unique`,
                DROP INDEX `users_phone_unique`,
                DROP COLUMN `username`,
                DROP COLUMN `phone`,
                DROP COLUMN `remember_token`
        ");
    }
};
