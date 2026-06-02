<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `settings` (
                `key`        VARCHAR(120)  NOT NULL,
                `value`      LONGTEXT      NULL DEFAULT NULL,
                `autoload`   TINYINT(1)    NOT NULL DEFAULT 1,
                `updated_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Seed default settings
        $defaults = [
            ['site_name',        'GoniCore',              1],
            ['site_tagline',     'A modern headless CMS', 1],
            ['site_url',         '',                      1],
            ['posts_per_page',   '9',                     1],
            ['homepage_type',    'posts',                 1],
            ['homepage_page_id', '',                      1],
            ['timezone',         'Asia/Tbilisi',          1],
            ['date_format',      'M j, Y',                1],
            ['time_format',      'H:i',                   1],
        ];

        foreach ($defaults as [$key, $val, $auto]) {
            $connection->execute(
                "INSERT IGNORE INTO `settings` (`key`, `value`, `autoload`) VALUES (?, ?, ?)",
                [$key, $val, $auto]
            );
        }
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS `settings`');
    }
};
