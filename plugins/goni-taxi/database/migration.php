<?php
declare(strict_types=1);
use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gtaxi_drivers` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`       VARCHAR(255) NOT NULL,
                `phone`      VARCHAR(50)  NOT NULL,
                `email`      VARCHAR(255) NOT NULL DEFAULT '',
                `car_model`  VARCHAR(255) NOT NULL DEFAULT '',
                `car_number` VARCHAR(50)  NOT NULL DEFAULT '',
                `car_type`   ENUM('sedan','minivan','suv','economy') NOT NULL DEFAULT 'sedan',
                `status`     ENUM('active','inactive','busy') NOT NULL DEFAULT 'active',
                `notes`      TEXT NOT NULL DEFAULT '',
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gtaxi_routes` (
                `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`          VARCHAR(255) NOT NULL,
                `from_location` VARCHAR(255) NOT NULL DEFAULT '',
                `to_location`   VARCHAR(255) NOT NULL DEFAULT '',
                `distance_km`   DECIMAL(8,1) NOT NULL DEFAULT 0.0,
                `price`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `car_type`      VARCHAR(50) NOT NULL DEFAULT '',
                `active`        TINYINT(1) NOT NULL DEFAULT 1,
                `sort_order`    INT NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gtaxi_rides` (
                `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `ride_number`       VARCHAR(30)  NOT NULL UNIQUE,
                `route_id`          INT UNSIGNED NULL DEFAULT NULL,
                `customer_name`     VARCHAR(255) NOT NULL,
                `customer_phone`    VARCHAR(50)  NOT NULL,
                `customer_email`    VARCHAR(255) NOT NULL DEFAULT '',
                `pickup_address`    VARCHAR(500) NOT NULL,
                `destination`       VARCHAR(500) NOT NULL,
                `scheduled_at`      DATETIME     NULL DEFAULT NULL,
                `passengers`        TINYINT UNSIGNED NOT NULL DEFAULT 1,
                `car_type`          VARCHAR(50)  NOT NULL DEFAULT 'sedan',
                `distance_km`       DECIMAL(8,1) NULL DEFAULT NULL,
                `estimated_price`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `actual_price`      DECIMAL(10,2) NULL DEFAULT NULL,
                `currency`          VARCHAR(5)   NOT NULL DEFAULT 'GEL',
                `status`            ENUM('pending','accepted','driver_assigned','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending',
                `driver_id`         INT UNSIGNED NULL DEFAULT NULL,
                `driver_note`       TEXT NOT NULL DEFAULT '',
                `customer_note`     TEXT NOT NULL DEFAULT '',
                `payment_method`    VARCHAR(50)  NOT NULL DEFAULT 'cash',
                `payment_status`    ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
                `transaction_id`    VARCHAR(255) NULL DEFAULT NULL,
                `ip_address`        VARCHAR(45)  NOT NULL DEFAULT '',
                `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `gtaxi_rides_status_idx` (`status`),
                INDEX `gtaxi_rides_driver_idx` (`driver_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gtaxi_settings` (
                `key`   VARCHAR(100) NOT NULL PRIMARY KEY,
                `value` LONGTEXT NOT NULL DEFAULT ''
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        foreach ([
            'currency'       => 'GEL',
            'currency_symbol'=> '₾',
            'page_slug'      => 'taxi',
            'brand_name'     => 'GoniTaxi',
            'phone'          => '',
            'base_fare'      => '5',
            'price_per_km'   => '1.5',
            'min_fare'       => '5',
        ] as $k => $v) {
            $pdo = $conn->pdo();
            $conn->execute("INSERT IGNORE INTO `gtaxi_settings` (`key`,`value`) VALUES (".$pdo->quote($k).",".$pdo->quote($v).")");
        }
    }

    public function down(Connection $conn): void
    {
        foreach (['gtaxi_rides','gtaxi_routes','gtaxi_drivers','gtaxi_settings'] as $t) {
            $conn->execute("DROP TABLE IF EXISTS `{$t}`");
        }
    }
};
