<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `activity_log` (
                `id`          INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
                `user_id`     INT UNSIGNED   NULL DEFAULT NULL,
                `action`      VARCHAR(100)   NOT NULL,
                `entity_type` VARCHAR(60)    NULL DEFAULT NULL,
                `entity_id`   INT UNSIGNED   NULL DEFAULT NULL,
                `meta`        JSON           NULL DEFAULT NULL,
                `created_at`  TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_activity_user`   (`user_id`),
                KEY `idx_activity_entity` (`entity_type`, `entity_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS `activity_log`');
    }
};
