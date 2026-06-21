<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

/**
 * Records failed login attempts so the login flow can throttle brute-force /
 * password-spraying. One row per failed attempt; rows are matched by IP and
 * identifier within a sliding time window and pruned opportunistically.
 */
return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `login_attempts` (
                `id`           INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
                `ip`           VARCHAR(45)   NOT NULL,
                `identifier`   VARCHAR(190)  NOT NULL,
                `attempted_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_la_ip_time`         (`ip`, `attempted_at`),
                KEY `idx_la_ip_ident_time`   (`ip`, `identifier`, `attempted_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS `login_attempts`');
    }
};
