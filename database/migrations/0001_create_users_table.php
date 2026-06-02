<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `users` (
                `id`         INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
                `name`       VARCHAR(255)    NOT NULL,
                `email`      VARCHAR(255)    NOT NULL,
                `password`   VARCHAR(255)    NOT NULL,
                `role`       ENUM('admin','editor','viewer') NOT NULL DEFAULT 'viewer',
                `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY `users_email_unique` (`email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS `users`');
    }
};
