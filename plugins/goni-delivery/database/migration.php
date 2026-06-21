<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gd_drivers` (
                `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`         VARCHAR(255) NOT NULL,
                `phone`        VARCHAR(50)  NOT NULL DEFAULT '',
                `email`        VARCHAR(255) NOT NULL DEFAULT '',
                `vehicle_type` VARCHAR(100) NOT NULL DEFAULT '',
                `vehicle_num`  VARCHAR(50)  NOT NULL DEFAULT '',
                `status`       ENUM('active','inactive') NOT NULL DEFAULT 'active',
                `notes`        TEXT NOT NULL DEFAULT '',
                `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gd_zones` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`       VARCHAR(255) NOT NULL,
                `price`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `min_order`  DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `eta_minutes`INT UNSIGNED NOT NULL DEFAULT 30,
                `active`     TINYINT(1) NOT NULL DEFAULT 1,
                `sort_order` INT NOT NULL DEFAULT 0
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gd_orders` (
                `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `order_number`      VARCHAR(30) NOT NULL UNIQUE,
                `type`              ENUM('courier','food') NOT NULL DEFAULT 'courier',
                `sender_name`       VARCHAR(255) NOT NULL DEFAULT '',
                `sender_phone`      VARCHAR(50)  NOT NULL,
                `sender_email`      VARCHAR(255) NOT NULL DEFAULT '',
                `pickup_address`    VARCHAR(500) NOT NULL DEFAULT '',
                `pickup_city`       VARCHAR(255) NOT NULL DEFAULT '',
                `recipient_name`    VARCHAR(255) NOT NULL DEFAULT '',
                `recipient_phone`   VARCHAR(50)  NOT NULL DEFAULT '',
                `delivery_address`  VARCHAR(500) NOT NULL,
                `delivery_city`     VARCHAR(255) NOT NULL DEFAULT '',
                `zone_id`           INT UNSIGNED NULL DEFAULT NULL,
                `package_type`      VARCHAR(100) NOT NULL DEFAULT '',
                `package_weight`    DECIMAL(8,2) NULL DEFAULT NULL,
                `package_desc`      TEXT NOT NULL DEFAULT '',
                `price`             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `currency`          VARCHAR(5)   NOT NULL DEFAULT 'GEL',
                `payment_method`    VARCHAR(50)  NOT NULL DEFAULT 'cash',
                `payment_status`    ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
                `transaction_id`    VARCHAR(255) NULL DEFAULT NULL,
                `status`            ENUM('pending','accepted','picked_up','in_transit','delivered','cancelled') NOT NULL DEFAULT 'pending',
                `driver_id`         INT UNSIGNED NULL DEFAULT NULL,
                `driver_note`       TEXT NOT NULL DEFAULT '',
                `customer_note`     TEXT NOT NULL DEFAULT '',
                `scheduled_at`      DATETIME NULL DEFAULT NULL,
                `delivered_at`      DATETIME NULL DEFAULT NULL,
                `ip_address`        VARCHAR(45) NOT NULL DEFAULT '',
                `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `gd_orders_status_idx` (`status`),
                INDEX `gd_orders_driver_idx` (`driver_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gd_settings` (
                `key`   VARCHAR(100) NOT NULL PRIMARY KEY,
                `value` LONGTEXT NOT NULL DEFAULT ''
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        foreach ([
            'currency'        => 'GEL',
            'currency_symbol' => '₾',
            'page_slug'       => 'delivery',
            'brand_name'      => 'GoniDelivery',
            'phone'           => '',
            'min_order'       => '0',
            'base_fee'        => '3',
            'food_enabled'    => '1',
            'courier_enabled' => '1',
        ] as $k => $v) {
            $pdo = $conn->pdo();
            $conn->execute("INSERT IGNORE INTO `gd_settings` (`key`,`value`) VALUES (".$pdo->quote($k).",".$pdo->quote($v).")");
        }
    }

    public function down(Connection $conn): void
    {
        foreach (['gd_orders','gd_zones','gd_drivers','gd_settings'] as $t) {
            $conn->execute("DROP TABLE IF EXISTS `{$t}`");
        }
    }
};
