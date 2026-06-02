<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        // menus — named collections of links
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `menus` (
                `id`         INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
                `name`       VARCHAR(255)  NOT NULL,
                `slug`       VARCHAR(255)  NOT NULL,
                `created_at` TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `menus_slug_unique` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // menu_items — individual links inside a menu
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `menu_items` (
                `id`        INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
                `menu_id`   INT UNSIGNED  NOT NULL,
                `parent_id` INT UNSIGNED  NULL DEFAULT NULL,
                `type`      ENUM('custom','page','post','category') NOT NULL DEFAULT 'custom',
                `object_id` INT UNSIGNED  NULL DEFAULT NULL COMMENT 'page/post/category id',
                `label`     VARCHAR(255)  NOT NULL,
                `url`       VARCHAR(1000) NULL DEFAULT NULL COMMENT 'For custom links',
                `target`    VARCHAR(20)   NOT NULL DEFAULT '_self',
                `sort_order` SMALLINT     NOT NULL DEFAULT 0,
                KEY `fk_mi_menu` (`menu_id`),
                KEY `fk_mi_parent` (`parent_id`),
                CONSTRAINT `fk_mi_menu`   FOREIGN KEY (`menu_id`)   REFERENCES `menus` (`id`) ON DELETE CASCADE,
                CONSTRAINT `fk_mi_parent` FOREIGN KEY (`parent_id`) REFERENCES `menu_items` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // menu_locations — theme-registered slots assigned to menus
        $connection->execute("
            CREATE TABLE IF NOT EXISTS `menu_locations` (
                `location` VARCHAR(100) NOT NULL PRIMARY KEY,
                `menu_id`  INT UNSIGNED NULL DEFAULT NULL,
                CONSTRAINT `fk_ml_menu` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $connection): void
    {
        $connection->execute('DROP TABLE IF EXISTS `menu_items`');
        $connection->execute('DROP TABLE IF EXISTS `menu_locations`');
        $connection->execute('DROP TABLE IF EXISTS `menus`');
    }
};
