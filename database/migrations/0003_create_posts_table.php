<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `posts` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `title`       VARCHAR(500)  NOT NULL,
                `slug`        VARCHAR(500)  NOT NULL,
                `content`     LONGTEXT      NOT NULL,
                `status`      ENUM('draft','published','archived') NOT NULL DEFAULT 'draft',
                `author_id`   INT UNSIGNED  NOT NULL,
                `category_id` INT UNSIGNED  NULL DEFAULT NULL,
                `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE  KEY `posts_slug_unique`  (`slug`),
                INDEX       `posts_status_idx`   (`status`),
                INDEX       `posts_author_idx`   (`author_id`),
                CONSTRAINT  `fk_posts_author`
                    FOREIGN KEY (`author_id`)   REFERENCES `users`      (`id`) ON DELETE CASCADE,
                CONSTRAINT  `fk_posts_category`
                    FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS `posts`');
    }
};
