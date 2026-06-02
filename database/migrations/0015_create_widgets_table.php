<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `widgets` (
                `id`         INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
                `area`       VARCHAR(60)    NOT NULL,
                `type`       VARCHAR(60)    NOT NULL DEFAULT 'html',
                `title`      VARCHAR(255)   NULL DEFAULT NULL,
                `settings`   JSON           NULL DEFAULT NULL,
                `sort_order` SMALLINT       NOT NULL DEFAULT 0,
                `is_active`  TINYINT(1)     NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `idx_widgets_area` (`area`, `sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Seed a demo HTML widget
        $connection->execute("
            INSERT INTO `widgets` (`area`, `type`, `title`, `settings`, `sort_order`)
            VALUES ('sidebar', 'html', 'Welcome', '{\"html\":\"<p>Welcome to <strong>GoniCore</strong> — a fast, headless PHP CMS.</p>\"}', 0)
        ");
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS `widgets`');
    }
};
