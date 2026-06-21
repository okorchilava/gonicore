<?php
declare(strict_types=1);
use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gapp_services` (
                `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`             VARCHAR(255) NOT NULL,
                `description`      TEXT NOT NULL DEFAULT '',
                `duration_minutes` INT UNSIGNED NOT NULL DEFAULT 60,
                `price`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `color`            VARCHAR(20) NOT NULL DEFAULT '#4f46e5',
                `image`            VARCHAR(1000) NOT NULL DEFAULT '',
                `status`           ENUM('active','inactive') NOT NULL DEFAULT 'active',
                `sort_order`       INT NOT NULL DEFAULT 0,
                `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gapp_staff` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`       VARCHAR(255) NOT NULL,
                `email`      VARCHAR(255) NOT NULL DEFAULT '',
                `phone`      VARCHAR(50)  NOT NULL DEFAULT '',
                `bio`        TEXT NOT NULL DEFAULT '',
                `image`      VARCHAR(1000) NOT NULL DEFAULT '',
                `title`      VARCHAR(255) NOT NULL DEFAULT '',
                `status`     ENUM('active','inactive') NOT NULL DEFAULT 'active',
                `sort_order` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gapp_staff_services` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `staff_id`   INT UNSIGNED NOT NULL,
                `service_id` INT UNSIGNED NOT NULL,
                UNIQUE KEY `gapp_ss_unique` (`staff_id`,`service_id`),
                CONSTRAINT `fk_gapp_ss_staff`   FOREIGN KEY (`staff_id`)   REFERENCES `gapp_staff`(`id`)    ON DELETE CASCADE,
                CONSTRAINT `fk_gapp_ss_service` FOREIGN KEY (`service_id`) REFERENCES `gapp_services`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gapp_working_hours` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `staff_id`    INT UNSIGNED NOT NULL,
                `day_of_week` TINYINT UNSIGNED NOT NULL COMMENT '0=Sun,1=Mon,...,6=Sat',
                `start_time`  TIME NOT NULL DEFAULT '09:00:00',
                `end_time`    TIME NOT NULL DEFAULT '18:00:00',
                `is_day_off`  TINYINT(1) NOT NULL DEFAULT 0,
                UNIQUE KEY `gapp_wh_unique` (`staff_id`,`day_of_week`),
                CONSTRAINT `fk_gapp_wh_staff` FOREIGN KEY (`staff_id`) REFERENCES `gapp_staff`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gapp_appointments` (
                `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `appointment_number` VARCHAR(30)  NOT NULL UNIQUE,
                `service_id`         INT UNSIGNED NOT NULL,
                `staff_id`           INT UNSIGNED NOT NULL,
                `customer_name`      VARCHAR(255) NOT NULL,
                `customer_email`     VARCHAR(255) NOT NULL,
                `customer_phone`     VARCHAR(50)  NOT NULL DEFAULT '',
                `appointment_date`   DATE         NOT NULL,
                `start_time`         TIME         NOT NULL,
                `end_time`           TIME         NOT NULL,
                `price`              DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `currency`           VARCHAR(5)   NOT NULL DEFAULT 'GEL',
                `status`             ENUM('pending','confirmed','cancelled','completed','no_show') NOT NULL DEFAULT 'pending',
                `payment_method`     VARCHAR(50)  NOT NULL DEFAULT 'on_site',
                `payment_status`     ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
                `transaction_id`     VARCHAR(255) NULL DEFAULT NULL,
                `customer_note`      TEXT NOT NULL DEFAULT '',
                `admin_note`         TEXT NOT NULL DEFAULT '',
                `ip_address`         VARCHAR(45)  NOT NULL DEFAULT '',
                `created_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `gapp_appt_date_idx` (`appointment_date`),
                INDEX `gapp_appt_staff_idx` (`staff_id`),
                INDEX `gapp_appt_status_idx` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gapp_settings` (
                `key`   VARCHAR(100) NOT NULL PRIMARY KEY,
                `value` LONGTEXT NOT NULL DEFAULT ''
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo = $conn->pdo();
        foreach ([
            'currency'         => 'GEL',
            'currency_symbol'  => '₾',
            'page_slug'        => 'book',
            'brand_name'       => 'Book Appointment',
            'slot_interval'    => '30',
            'advance_days'     => '30',
            'min_advance_hours'=> '1',
        ] as $k => $v) {
            $conn->execute(
                "INSERT IGNORE INTO `gapp_settings` (`key`,`value`) VALUES (".$pdo->quote($k).",".$pdo->quote($v).")"
            );
        }
    }

    public function down(Connection $conn): void
    {
        foreach (['gapp_appointments','gapp_working_hours','gapp_staff_services','gapp_staff','gapp_services','gapp_settings'] as $t) {
            $conn->execute("DROP TABLE IF EXISTS `{$t}`");
        }
    }
};
