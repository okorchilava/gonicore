<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `todos` (
                `id`          INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
                `user_id`     INT UNSIGNED   NOT NULL,
                `title`       VARCHAR(500)   NOT NULL,
                `completed`   TINYINT(1)     NOT NULL DEFAULT 0,
                `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_todos_user` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS `todos`');
    }
};
