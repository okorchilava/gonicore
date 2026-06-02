<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `media` (
                `id`            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
                `filename`      VARCHAR(255)  NOT NULL,
                `original_name` VARCHAR(255)  NOT NULL,
                `mime_type`     VARCHAR(127)  NOT NULL,
                `size`          INT UNSIGNED  NOT NULL COMMENT 'File size in bytes',
                `path`          VARCHAR(500)  NOT NULL COMMENT 'Path relative to storage root',
                `uploaded_by`   INT UNSIGNED  NOT NULL,
                `created_at`    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX      `media_uploader_idx`  (`uploaded_by`),
                INDEX      `media_mime_idx`      (`mime_type`),
                CONSTRAINT `fk_media_uploader`
                    FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS `media`');
    }
};
