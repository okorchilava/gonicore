<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `languages` (
                `id`         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
                `code`       VARCHAR(10)   NOT NULL,
                `name`       VARCHAR(100)  NOT NULL,
                `native`     VARCHAR(100)  NOT NULL,
                `flag`       VARCHAR(10)   NOT NULL DEFAULT '🌐',
                `is_default` TINYINT(1)    NOT NULL DEFAULT 0,
                `is_active`  TINYINT(1)    NOT NULL DEFAULT 1,
                `sort_order` SMALLINT      NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `languages_code_unique` (`code`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Seed default languages
        $connection->execute("
            INSERT IGNORE INTO `languages` (`code`, `name`, `native`, `flag`, `is_default`, `is_active`, `sort_order`)
            VALUES
                ('en', 'English',   'English',  '🇬🇧', 1, 1, 0),
                ('ka', 'Georgian',  'ქართული',  '🇬🇪', 0, 1, 1)
        ");
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS `languages`');
    }
};
