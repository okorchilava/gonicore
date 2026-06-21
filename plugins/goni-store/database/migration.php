<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        // Product categories
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gs_categories` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `parent_id`   INT UNSIGNED NULL DEFAULT NULL,
                `name`        VARCHAR(255) NOT NULL,
                `slug`        VARCHAR(255) NOT NULL UNIQUE,
                `description` TEXT NOT NULL DEFAULT '',
                `image`       VARCHAR(500) NOT NULL DEFAULT '',
                `sort_order`  INT NOT NULL DEFAULT 0,
                `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Products
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gs_products` (
                `id`                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `category_id`       INT UNSIGNED NULL DEFAULT NULL,
                `name`              VARCHAR(255) NOT NULL,
                `slug`              VARCHAR(255) NOT NULL UNIQUE,
                `short_description` TEXT NOT NULL DEFAULT '',
                `description`       LONGTEXT NOT NULL DEFAULT '',
                `price`             DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `sale_price`        DECIMAL(12,2) NULL DEFAULT NULL,
                `sku`               VARCHAR(100) NOT NULL DEFAULT '',
                `stock`             INT NULL DEFAULT NULL COMMENT 'NULL = unlimited',
                `manage_stock`      TINYINT(1) NOT NULL DEFAULT 0,
                `weight`            DECIMAL(8,2) NULL DEFAULT NULL,
                `images`            LONGTEXT NOT NULL DEFAULT '[]' COMMENT 'JSON array of URLs',
                `gallery`           LONGTEXT NOT NULL DEFAULT '[]' COMMENT 'JSON array of URLs',
                `attributes`        LONGTEXT NOT NULL DEFAULT '{}' COMMENT 'JSON key-value attributes',
                `type`              VARCHAR(20) NOT NULL DEFAULT 'simple',
                `status`            VARCHAR(20) NOT NULL DEFAULT 'draft',
                `featured`          TINYINT(1) NOT NULL DEFAULT 0,
                `virtual`           TINYINT(1) NOT NULL DEFAULT 0,
                `downloadable`      TINYINT(1) NOT NULL DEFAULT 0,
                `download_url`      VARCHAR(500) NOT NULL DEFAULT '',
                `meta_title`        VARCHAR(255) NOT NULL DEFAULT '',
                `meta_description`  TEXT NOT NULL DEFAULT '',
                `sort_order`        INT NOT NULL DEFAULT 0,
                `created_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Product variations (for variable products)
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gs_variations` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `product_id` INT UNSIGNED NOT NULL,
                `sku`        VARCHAR(100) NOT NULL DEFAULT '',
                `price`      DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `sale_price` DECIMAL(12,2) NULL DEFAULT NULL,
                `stock`      INT NULL DEFAULT NULL,
                `attributes` LONGTEXT NOT NULL DEFAULT '{}' COMMENT 'JSON e.g. {\"color\":\"red\",\"size\":\"M\"}',
                `image`      VARCHAR(500) NOT NULL DEFAULT '',
                `active`     TINYINT(1) NOT NULL DEFAULT 1,
                CONSTRAINT `fk_gsv_product` FOREIGN KEY (`product_id`) REFERENCES `gs_products`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Orders
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gs_orders` (
                `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `order_number`     VARCHAR(50) NOT NULL UNIQUE,
                `customer_id`      INT UNSIGNED NULL DEFAULT NULL,
                `status`           VARCHAR(30) NOT NULL DEFAULT 'pending',
                `subtotal`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `tax`              DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `shipping_cost`    DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `discount`         DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `total`            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `currency`         VARCHAR(5) NOT NULL DEFAULT 'USD',
                `billing`          LONGTEXT NOT NULL DEFAULT '{}' COMMENT 'JSON address',
                `shipping`         LONGTEXT NOT NULL DEFAULT '{}' COMMENT 'JSON address',
                `payment_method`   VARCHAR(50) NOT NULL DEFAULT '',
                `payment_status`   VARCHAR(30) NOT NULL DEFAULT 'unpaid',
                `shipping_method`  VARCHAR(100) NOT NULL DEFAULT '',
                `notes`            TEXT NOT NULL DEFAULT '',
                `customer_note`    TEXT NOT NULL DEFAULT '',
                `coupon_code`      VARCHAR(50) NOT NULL DEFAULT '',
                `ip_address`       VARCHAR(45) NOT NULL DEFAULT '',
                `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Order items
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gs_order_items` (
                `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `order_id`     INT UNSIGNED NOT NULL,
                `product_id`   INT UNSIGNED NULL DEFAULT NULL,
                `variation_id` INT UNSIGNED NULL DEFAULT NULL,
                `name`         VARCHAR(255) NOT NULL,
                `sku`          VARCHAR(100) NOT NULL DEFAULT '',
                `quantity`     INT UNSIGNED NOT NULL DEFAULT 1,
                `price`        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `total`        DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `attributes`   LONGTEXT NOT NULL DEFAULT '{}',
                `meta`         LONGTEXT NOT NULL DEFAULT '{}',
                CONSTRAINT `fk_gsoi_order` FOREIGN KEY (`order_id`) REFERENCES `gs_orders`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Order status history
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gs_order_notes` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `order_id`   INT UNSIGNED NOT NULL,
                `note`       TEXT NOT NULL,
                `status`     VARCHAR(30) NOT NULL DEFAULT '',
                `is_customer`TINYINT(1) NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_gson_order` FOREIGN KEY (`order_id`) REFERENCES `gs_orders`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Coupons
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gs_coupons` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `code`            VARCHAR(50) NOT NULL UNIQUE,
                `type`            VARCHAR(20) NOT NULL DEFAULT 'percent' COMMENT 'percent|fixed',
                `value`           DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `min_order`       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `max_uses`        INT NULL DEFAULT NULL,
                `used`            INT NOT NULL DEFAULT 0,
                `expires_at`      DATE NULL DEFAULT NULL,
                `active`          TINYINT(1) NOT NULL DEFAULT 1,
                `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Store settings
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gs_settings` (
                `key`   VARCHAR(100) NOT NULL PRIMARY KEY,
                `value` LONGTEXT NOT NULL DEFAULT ''
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Default settings
        $defaults = [
            'currency'          => 'USD',
            'currency_symbol'   => '$',
            'currency_position' => 'before',
            'thousand_sep'      => ',',
            'decimal_sep'       => '.',
            'decimals'          => '2',
            'shop_page_slug'    => 'shop',
            'cart_page_slug'    => 'cart',
            'checkout_page_slug'=> 'checkout',
            'tax_rate'          => '0',
            'tax_included'      => '0',
            'free_shipping_min' => '0',
            'shipping_cost'     => '0',
            'order_email'       => '',
            'from_email'        => '',
            'products_per_page' => '12',
            'shop_layout'       => 'grid',
            'allow_guest_checkout' => '1',
        ];
        $pdo = $conn->pdo();
        foreach ($defaults as $k => $v) {
            $conn->execute(
                "INSERT IGNORE INTO `gs_settings` (`key`, `value`) VALUES (".$pdo->quote($k).",".$pdo->quote($v).")"
            );
        }
    }

    public function down(Connection $conn): void
    {
        foreach (['gs_order_notes','gs_order_items','gs_orders','gs_variations','gs_products','gs_categories','gs_coupons','gs_settings'] as $t) {
            $conn->execute("DROP TABLE IF EXISTS `{$t}`");
        }
    }
};
