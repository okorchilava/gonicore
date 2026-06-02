<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `notifications` (
                `id`         INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
                `user_id`    INT UNSIGNED   NULL DEFAULT NULL COMMENT 'NULL = broadcast to all admins',
                `type`       VARCHAR(80)    NOT NULL,
                `title`      VARCHAR(255)   NOT NULL,
                `message`    TEXT           NULL DEFAULT NULL,
                `data`       JSON           NULL DEFAULT NULL,
                `icon`       VARCHAR(10)    NOT NULL DEFAULT '🔔',
                `read_at`    TIMESTAMP      NULL DEFAULT NULL,
                `created_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_notif_user`    (`user_id`),
                KEY `idx_notif_read`    (`user_id`, `read_at`),
                KEY `idx_notif_created` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS `notifications`');
    }
};
