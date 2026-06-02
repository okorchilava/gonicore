<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            ALTER TABLE `posts`
                ADD COLUMN `type`      ENUM('post','page') NOT NULL DEFAULT 'post' AFTER `id`,
                ADD COLUMN `excerpt`   TEXT                NULL DEFAULT NULL       AFTER `content`,
                ADD COLUMN `parent_id` INT UNSIGNED        NULL DEFAULT NULL       AFTER `category_id`,
                ADD INDEX `posts_type_idx` (`type`)
        ");
    }

    public function down(Connection $connection): void
    {
        $connection->execute("
            ALTER TABLE `posts`
                DROP INDEX `posts_type_idx`,
                DROP COLUMN `type`,
                DROP COLUMN `excerpt`,
                DROP COLUMN `parent_id`
        ");
    }
};
