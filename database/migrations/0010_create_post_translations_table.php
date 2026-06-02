<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `post_translations` (
                `id`            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
                `post_id`       INT UNSIGNED  NOT NULL,
                `language_code` VARCHAR(10)   NOT NULL,
                `title`         VARCHAR(500)  NOT NULL,
                `slug`          VARCHAR(500)  NOT NULL,
                `content`       LONGTEXT      NOT NULL,
                `status`        ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
                `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `pt_post_lang` (`post_id`, `language_code`),
                KEY `idx_pt_lang_slug` (`language_code`, `slug`),
                CONSTRAINT `fk_pt_post`
                    FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS `post_translations`');
    }
};
