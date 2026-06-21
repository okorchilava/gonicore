<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gt_events` (
                `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `title`             VARCHAR(500) NOT NULL,
                `slug`              VARCHAR(500) NOT NULL UNIQUE,
                `short_description` TEXT NOT NULL DEFAULT '',
                `description`       LONGTEXT NOT NULL DEFAULT '',
                `location`          VARCHAR(500) NOT NULL DEFAULT '',
                `venue`             VARCHAR(500) NOT NULL DEFAULT '',
                `organizer`         VARCHAR(255) NOT NULL DEFAULT '',
                `category`          VARCHAR(50)  NOT NULL DEFAULT 'other',
                `event_date`        DATETIME NOT NULL,
                `event_end_date`    DATETIME NULL DEFAULT NULL,
                `image`             VARCHAR(1000) NOT NULL DEFAULT '',
                `status`            ENUM('draft','published','cancelled') NOT NULL DEFAULT 'draft',
                `featured`          TINYINT(1) NOT NULL DEFAULT 0,
                `sort_order`        INT NOT NULL DEFAULT 0,
                `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gt_ticket_types` (
                `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `event_id`      INT UNSIGNED NOT NULL,
                `name`          VARCHAR(255) NOT NULL,
                `description`   TEXT NOT NULL DEFAULT '',
                `price`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `quantity`      INT UNSIGNED NULL DEFAULT NULL COMMENT 'NULL = unlimited',
                `sold`          INT UNSIGNED NOT NULL DEFAULT 0,
                `max_per_order` INT UNSIGNED NULL DEFAULT NULL,
                `status`        ENUM('active','inactive') NOT NULL DEFAULT 'active',
                `sort_order`    INT NOT NULL DEFAULT 0,
                `created_at`    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_gtt_event` FOREIGN KEY (`event_id`) REFERENCES `gt_events`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gt_bookings` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `booking_number`  VARCHAR(30) NOT NULL UNIQUE,
                `event_id`        INT UNSIGNED NOT NULL,
                `customer_name`   VARCHAR(255) NOT NULL,
                `customer_email`  VARCHAR(255) NOT NULL,
                `customer_phone`  VARCHAR(50) NOT NULL DEFAULT '',
                `total`           DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `currency`        VARCHAR(5) NOT NULL DEFAULT 'GEL',
                `status`          ENUM('pending','confirmed','cancelled','refunded') NOT NULL DEFAULT 'pending',
                `payment_method`  VARCHAR(50) NOT NULL DEFAULT 'cash',
                `payment_status`  ENUM('unpaid','paid','refunded') NOT NULL DEFAULT 'unpaid',
                `transaction_id`  VARCHAR(255) NULL DEFAULT NULL,
                `customer_note`   TEXT NOT NULL DEFAULT '',
                `ip_address`      VARCHAR(45) NOT NULL DEFAULT '',
                `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `gt_bookings_event_idx` (`event_id`),
                INDEX `gt_bookings_email_idx` (`customer_email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gt_booking_tickets` (
                `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `booking_id`       INT UNSIGNED NOT NULL,
                `ticket_type_id`   INT UNSIGNED NULL DEFAULT NULL,
                `ticket_type_name` VARCHAR(255) NOT NULL,
                `quantity`         INT UNSIGNED NOT NULL DEFAULT 1,
                `unit_price`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `total`            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                CONSTRAINT `fk_gtbt_booking` FOREIGN KEY (`booking_id`) REFERENCES `gt_bookings`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gt_settings` (
                `key`   VARCHAR(100) NOT NULL PRIMARY KEY,
                `value` LONGTEXT NOT NULL DEFAULT ''
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gt_categories` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `slug`       VARCHAR(100) NOT NULL UNIQUE,
                `label`      VARCHAR(255) NOT NULL,
                `icon`       VARCHAR(8)   NOT NULL DEFAULT '🎟',
                `accent`     VARCHAR(20)  NOT NULL DEFAULT '#a78bfa',
                `grad_from`  VARCHAR(20)  NOT NULL DEFAULT '#0a0812',
                `grad_to`    VARCHAR(20)  NOT NULL DEFAULT '#4c1d95',
                `sort_order` INT          NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gt_organizers` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `slug`        VARCHAR(200) NOT NULL UNIQUE,
                `name`        VARCHAR(255) NOT NULL,
                `description` TEXT         NOT NULL DEFAULT '',
                `logo`        VARCHAR(1000) NOT NULL DEFAULT '',
                `website`     VARCHAR(1000) NOT NULL DEFAULT '',
                `sort_order`  INT           NOT NULL DEFAULT 0,
                `cover`       VARCHAR(1000) NOT NULL DEFAULT '',
                `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // organizer_id on gt_events (fresh install: table was just created above, no IF NOT EXISTS needed)
        $conn->execute("
            ALTER TABLE `gt_events` ADD COLUMN `organizer_id` INT UNSIGNED NULL DEFAULT NULL
        ");

        $defaults = [
            'currency'          => 'GEL',
            'currency_symbol'   => '₾',
            'events_page_slug'  => 'events',
            'from_email'        => '',
        ];
        $pdo = $conn->pdo();
        foreach ($defaults as $k => $v) {
            $conn->execute(
                "INSERT IGNORE INTO `gt_settings` (`key`, `value`) VALUES (".$pdo->quote($k).",".$pdo->quote($v).")"
            );
        }
    }

    public function down(Connection $conn): void
    {
        foreach (['gt_booking_tickets','gt_bookings','gt_ticket_types','gt_events','gt_settings'] as $t) {
            $conn->execute("DROP TABLE IF EXISTS `{$t}`");
        }
    }
};
