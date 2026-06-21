<?php
declare(strict_types=1);

use GoniDelivery\AdminController;
use GoniDelivery\DeliveryService;
use GoniDelivery\FrontendController;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Mail\MailService;
use GoniCore\Modules\Login\LoginService;

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GoniDelivery\\')) return;
    $file = $pluginDir . '/src/' . str_replace('\\', '/', substr($class, 13)) . '.php';
    if (is_file($file)) require_once $file;
});

// ── Migration v1 ─────────────────────────────────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $rows = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gd_orders'"
    );
    if ((int)($rows[0]['cnt'] ?? 0) === 0) {
        (require $pluginDir . '/database/migration.php')->up($conn);
    }
} catch (\Throwable) {}

// ── Migration v2: 3-sided ecosystem ──────────────────────────────────────────
try {
    $conn = $container->get(Connection::class);

    // Vendors table
    $cv = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_vendors'");
    if ((int)($cv[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_vendors` (
                `id`                   INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`                 VARCHAR(255) NOT NULL,
                `slug`                 VARCHAR(255) NOT NULL UNIQUE,
                `description`          TEXT NOT NULL DEFAULT '',
                `logo`                 VARCHAR(500) NOT NULL DEFAULT '',
                `cover_image`          VARCHAR(500) NOT NULL DEFAULT '',
                `phone`                VARCHAR(50)  NOT NULL DEFAULT '',
                `email`                VARCHAR(255) NOT NULL DEFAULT '',
                `address`              VARCHAR(500) NOT NULL DEFAULT '',
                `lat`                  DECIMAL(10,7) NULL DEFAULT NULL,
                `lng`                  DECIMAL(10,7) NULL DEFAULT NULL,
                `category`             VARCHAR(100) NOT NULL DEFAULT 'restaurant',
                `cuisine_tags`         VARCHAR(500) NOT NULL DEFAULT '',
                `menu_size`            VARCHAR(100) NOT NULL DEFAULT '',
                `open_time`            TIME NULL DEFAULT NULL,
                `close_time`           TIME NULL DEFAULT NULL,
                `days_open`            VARCHAR(20) NOT NULL DEFAULT '1234567',
                `prep_time_min`        TINYINT UNSIGNED NOT NULL DEFAULT 20,
                `min_order`            DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `delivery_fee`         DECIMAL(10,2) NOT NULL DEFAULT 3.00,
                `free_delivery_threshold` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `commission_pct`       DECIMAL(5,2)  NOT NULL DEFAULT 15.00,
                `status`               ENUM('active','inactive','busy') NOT NULL DEFAULT 'active',
                `is_featured`          TINYINT(1) NOT NULL DEFAULT 0,
                `rating`               DECIMAL(3,2) NOT NULL DEFAULT 0.00,
                `total_orders`         INT UNSIGNED NOT NULL DEFAULT 0,
                `vendor_token`         VARCHAR(64) NULL DEFAULT NULL UNIQUE,
                `created_at`           TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gv_status` (`status`),
                INDEX `gv_category` (`category`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // gd_vendors — add `menu_size` column if upgrading
    $cmvs = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_vendors' AND COLUMN_NAME='menu_size'");
    if ((int)($cmvs[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gd_vendors` ADD COLUMN `menu_size` VARCHAR(100) NOT NULL DEFAULT '' AFTER `cuisine_tags`");
    }

    // Product categories
    $cc = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_categories'");
    if ((int)($cc[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_categories` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `vendor_id`   INT UNSIGNED NOT NULL,
                `name`        VARCHAR(255) NOT NULL,
                `description` TEXT NOT NULL DEFAULT '',
                `image`       VARCHAR(500) NOT NULL DEFAULT '',
                `sort_order`  INT NOT NULL DEFAULT 0,
                `active`      TINYINT(1) NOT NULL DEFAULT 1,
                `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gc_vendor` (`vendor_id`, `active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // Products
    $cp = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_products'");
    if ((int)($cp[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_products` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `vendor_id`      INT UNSIGNED NOT NULL,
                `category_id`    INT UNSIGNED NULL DEFAULT NULL,
                `name`           VARCHAR(255) NOT NULL,
                `description`    TEXT NOT NULL DEFAULT '',
                `image`          VARCHAR(500) NOT NULL DEFAULT '',
                `price`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `compare_price`  DECIMAL(10,2) NULL DEFAULT NULL,
                `prep_time`      TINYINT UNSIGNED NULL DEFAULT NULL,
                `in_stock`       TINYINT(1) NOT NULL DEFAULT 1,
                `is_featured`    TINYINT(1) NOT NULL DEFAULT 0,
                `sort_order`     INT NOT NULL DEFAULT 0,
                `active`         TINYINT(1) NOT NULL DEFAULT 1,
                `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gp_vendor`   (`vendor_id`, `active`),
                INDEX `gp_category` (`category_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // Modifier groups
    $cmg = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_modifier_groups'");
    if ((int)($cmg[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_modifier_groups` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `product_id`  INT UNSIGNED NOT NULL,
                `name`        VARCHAR(255) NOT NULL,
                `type`        ENUM('choice','exclusion') NOT NULL DEFAULT 'choice',
                `required`    TINYINT(1) NOT NULL DEFAULT 0,
                `min_select`  TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `max_select`  TINYINT UNSIGNED NOT NULL DEFAULT 1,
                `sort_order`  INT NOT NULL DEFAULT 0,
                INDEX `gmg_product` (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // gd_modifier_groups — add `type` column if upgrading from older schema
    $cmgt = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_modifier_groups' AND COLUMN_NAME='type'");
    if ((int)($cmgt[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gd_modifier_groups` ADD COLUMN `type` ENUM('choice','exclusion','size') NOT NULL DEFAULT 'choice' AFTER `name`");
    }

    // gd_modifier_groups — expand ENUM to include 'size' if not already present
    $cmgts = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_modifier_groups' AND COLUMN_NAME='type' AND COLUMN_TYPE LIKE '%size%'");
    if ((int)($cmgts[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gd_modifier_groups` MODIFY COLUMN `type` ENUM('choice','exclusion','size') NOT NULL DEFAULT 'choice'");
    }

    // Modifier templates (vendor-level reusable groups)
    $ctpl = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_modifier_templates'");
    if ((int)($ctpl[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_modifier_templates` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `vendor_id`  INT UNSIGNED NOT NULL,
                `name`       VARCHAR(255) NOT NULL,
                `type`       ENUM('choice','exclusion','size') NOT NULL DEFAULT 'choice',
                `required`   TINYINT(1) NOT NULL DEFAULT 0,
                `max_select` INT NOT NULL DEFAULT 1,
                `sort_order` INT NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gtpl_vendor` (`vendor_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    $ctpli = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_modifier_template_items'");
    if ((int)($ctpli[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_modifier_template_items` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `template_id` INT UNSIGNED NOT NULL,
                `name`        VARCHAR(255) NOT NULL,
                `price`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `sort_order`  INT NOT NULL DEFAULT 0,
                INDEX `gtpli_tpl` (`template_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // Modifiers
    $cm = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_modifiers'");
    if ((int)($cm[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_modifiers` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `group_id`   INT UNSIGNED NOT NULL,
                `name`       VARCHAR(255) NOT NULL,
                `price`      DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `in_stock`   TINYINT(1) NOT NULL DEFAULT 1,
                `sort_order` INT NOT NULL DEFAULT 0,
                INDEX `gmod_group` (`group_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // Order items
    $coi = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_order_items'");
    if ((int)($coi[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_order_items` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `order_id`       INT UNSIGNED NOT NULL,
                `product_id`     INT UNSIGNED NOT NULL,
                `vendor_id`      INT UNSIGNED NOT NULL,
                `name`           VARCHAR(255) NOT NULL,
                `price`          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `quantity`       TINYINT UNSIGNED NOT NULL DEFAULT 1,
                `modifiers_json` JSON NULL DEFAULT NULL,
                `item_total`     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                INDEX `goi_order` (`order_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // Customers
    $ccust = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_customers'");
    if ((int)($ccust[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_customers` (
                `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`         VARCHAR(255) NOT NULL DEFAULT '',
                `phone`        VARCHAR(50) NOT NULL UNIQUE,
                `email`        VARCHAR(255) NOT NULL DEFAULT '',
                `home_address` VARCHAR(500) NOT NULL DEFAULT '',
                `home_lat`     DECIMAL(10,7) NULL DEFAULT NULL,
                `home_lng`     DECIMAL(10,7) NULL DEFAULT NULL,
                `work_address` VARCHAR(500) NOT NULL DEFAULT '',
                `refund_count` TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `is_blocked`   TINYINT(1) NOT NULL DEFAULT 0,
                `created_at`   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gc_phone` (`phone`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // OTP codes
    $cotp = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_otp_codes'");
    if ((int)($cotp[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_otp_codes` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `phone`      VARCHAR(50) NOT NULL,
                `code`       VARCHAR(10) NOT NULL,
                `expires_at` TIMESTAMP NOT NULL,
                `used`       TINYINT(1) NOT NULL DEFAULT 0,
                `attempts`   TINYINT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gotp` (`phone`, `used`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    // Extend gd_orders: vendor_id, customer_id, vendor_status, subtotal, delivery_fee, total, prep_time, coords, driver_token
    $ex = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_orders' AND COLUMN_NAME='vendor_id'");
    if ((int)($ex[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gd_orders`
            ADD COLUMN `vendor_id`           INT UNSIGNED NULL DEFAULT NULL AFTER `id`,
            ADD COLUMN `customer_id`         INT UNSIGNED NULL DEFAULT NULL AFTER `vendor_id`,
            ADD COLUMN `vendor_status`       ENUM('pending','accepted','preparing','ready') NOT NULL DEFAULT 'pending' AFTER `status`,
            ADD COLUMN `subtotal`            DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `price`,
            ADD COLUMN `delivery_fee`        DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `subtotal`,
            ADD COLUMN `discount`            DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `delivery_fee`,
            ADD COLUMN `prep_time_minutes`   TINYINT UNSIGNED NULL DEFAULT NULL,
            ADD COLUMN `courier_dispatched_at` TIMESTAMP NULL DEFAULT NULL,
            ADD COLUMN `pickup_lat`          DECIMAL(10,7) NULL DEFAULT NULL,
            ADD COLUMN `pickup_lng`          DECIMAL(10,7) NULL DEFAULT NULL,
            ADD COLUMN `delivery_lat`        DECIMAL(10,7) NULL DEFAULT NULL,
            ADD COLUMN `delivery_lng`        DECIMAL(10,7) NULL DEFAULT NULL,
            ADD COLUMN `driver_token`        VARCHAR(64) NULL DEFAULT NULL,
            ADD COLUMN `proof_photo`         VARCHAR(500) NOT NULL DEFAULT ''
        ");
    }

    // Extend gd_drivers: token, online, location
    $ed = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_drivers' AND COLUMN_NAME='driver_token'");
    if ((int)($ed[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gd_drivers`
            ADD COLUMN `driver_token`        VARCHAR(64) NULL DEFAULT NULL UNIQUE,
            ADD COLUMN `is_online`           TINYINT(1) NOT NULL DEFAULT 0,
            ADD COLUMN `current_lat`         DECIMAL(10,7) NULL DEFAULT NULL,
            ADD COLUMN `current_lng`         DECIMAL(10,7) NULL DEFAULT NULL,
            ADD COLUMN `location_updated_at` TIMESTAMP NULL DEFAULT NULL,
            ADD COLUMN `balance`             DECIMAL(10,2) NOT NULL DEFAULT 0.00,
            ADD COLUMN `total_deliveries`    INT UNSIGNED NOT NULL DEFAULT 0,
            ADD COLUMN `avg_rating`          DECIMAL(3,2) NOT NULL DEFAULT 0.00
        ");
        // Rename vehicle_type if needed for consistency
    }

} catch (\Throwable $e) {}

// ── Migration v3: Offers / Discounts ─────────────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $cof = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_offers'");
    if ((int)($cof[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_offers` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `vendor_id`   INT UNSIGNED NOT NULL,
                `name`        VARCHAR(255) NOT NULL,
                `description` VARCHAR(500) NOT NULL DEFAULT '',
                `type`        ENUM('percent','fixed','free_delivery') NOT NULL DEFAULT 'percent',
                `value`       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `applies_to`  ENUM('order','product','category') NOT NULL DEFAULT 'order',
                `target_id`   INT UNSIGNED NULL DEFAULT NULL,
                `min_order`   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `active`      TINYINT(1) NOT NULL DEFAULT 1,
                `start_date`  DATE NULL DEFAULT NULL,
                `end_date`    DATE NULL DEFAULT NULL,
                `created_at`  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `go_vendor` (`vendor_id`, `active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
} catch (\Throwable) {}

// ── Migration v4: Vendor Locations (Branches) + branch_id on orders ───────────
try {
    $conn = $container->get(Connection::class);
    // gd_vendor_locations
    $cvl = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_vendor_locations'");
    if ((int)($cvl[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_vendor_locations` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `vendor_id`  INT UNSIGNED NOT NULL,
                `name`       VARCHAR(255) NOT NULL DEFAULT 'Main',
                `address`    TEXT NOT NULL DEFAULT '',
                `lat`        DECIMAL(10,7) NULL DEFAULT NULL,
                `lng`        DECIMAL(10,7) NULL DEFAULT NULL,
                `phone`      VARCHAR(50) NOT NULL DEFAULT '',
                `active`     TINYINT(1) NOT NULL DEFAULT 1,
                `sort_order` INT NOT NULL DEFAULT 0,
                INDEX `vl_vendor` (`vendor_id`, `active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
    // branch_id column on gd_orders
    $cbc = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_orders' AND COLUMN_NAME='branch_id'");
    if ((int)($cbc[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gd_orders` ADD COLUMN `branch_id` INT UNSIGNED NULL DEFAULT NULL AFTER `vendor_id`");
    }
} catch (\Throwable) {}

// ── Migration v5: Combo Menus ─────────────────────────────────────────────────
try {
    $conn = $container->get(Connection::class);

    // gd_combos — vendor-level named groups
    // type: 'choice'=customer picks, 'included'=always in combo (informational), 'size'=pick size (price modifier per item)
    $cco = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_combos'");
    if ((int)($cco[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_combos` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `vendor_id`  INT UNSIGNED NOT NULL,
                `name`       VARCHAR(255) NOT NULL,
                `type`       ENUM('choice','included','size') NOT NULL DEFAULT 'choice',
                `required`   TINYINT(1) NOT NULL DEFAULT 0,
                `max_select` TINYINT UNSIGNED NOT NULL DEFAULT 1,
                `sort_order` INT NOT NULL DEFAULT 0,
                `active`     TINYINT(1) NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gcb_vendor` (`vendor_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        // Add `type` column if upgrading from initial v5
        $ctype = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_combos' AND COLUMN_NAME='type'");
        if ((int)($ctype[0]['cnt']??0) === 0) {
            $conn->execute("ALTER TABLE `gd_combos` ADD COLUMN `type` ENUM('choice','included','size') NOT NULL DEFAULT 'choice' AFTER `name`");
        }
    }

    // gd_combo_products — products inside a combo group; price_modifier = surcharge/discount for picking this item
    $ccp = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_combo_products'");
    if ((int)($ccp[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_combo_products` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `combo_id`       INT UNSIGNED NOT NULL,
                `product_id`     INT UNSIGNED NOT NULL,
                `price_modifier` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                `sort_order`     INT NOT NULL DEFAULT 0,
                UNIQUE KEY `uq_cp` (`combo_id`,`product_id`),
                INDEX `gcbp_combo`   (`combo_id`),
                INDEX `gcbp_product` (`product_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } else {
        // Add `price_modifier` column if upgrading from initial v5
        $cpm = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_combo_products' AND COLUMN_NAME='price_modifier'");
        if ((int)($cpm[0]['cnt']??0) === 0) {
            $conn->execute("ALTER TABLE `gd_combo_products` ADD COLUMN `price_modifier` DECIMAL(10,2) NOT NULL DEFAULT 0.00 AFTER `product_id`");
        }
    }

    // gd_product_combos — which combo groups are attached to which main product (e.g. "Burger" → "სასმელი")
    $cpco = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_product_combos'");
    if ((int)($cpco[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_product_combos` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `product_id` INT UNSIGNED NOT NULL,
                `combo_id`   INT UNSIGNED NOT NULL,
                `sort_order` INT NOT NULL DEFAULT 0,
                UNIQUE KEY `uq_pc` (`product_id`,`combo_id`),
                INDEX `gpc_product` (`product_id`),
                INDEX `gpc_combo`   (`combo_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
} catch (\Throwable) {}

// ── Migration v6: Order Offers (random courier dispatch) ─────────────────────
try {
    $conn = $container->get(Connection::class);

    // gd_orders — add track_token (opaque URL token) if missing
    $ctt = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_orders' AND COLUMN_NAME='track_token'");
    if ((int)($ctt[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gd_orders` ADD COLUMN `track_token` VARCHAR(32) NULL DEFAULT NULL UNIQUE AFTER `order_number`");
        // Backfill existing rows
        $existing = $conn->query("SELECT `id` FROM `gd_orders` WHERE `track_token` IS NULL");
        foreach ($existing as $eRow) {
            $conn->execute("UPDATE `gd_orders` SET `track_token` = ? WHERE `id` = ?", [bin2hex(random_bytes(16)), (int)$eRow['id']]);
        }
    }

    // gd_orders — add prep_ends_at (server-side absolute end time) if missing
    $cpea = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_orders' AND COLUMN_NAME='prep_ends_at'");
    if ((int)($cpea[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gd_orders` ADD COLUMN `prep_ends_at` DATETIME NULL DEFAULT NULL AFTER `prep_time_minutes`");
    }
} catch (\Throwable) {}

try {
    $conn = $container->get(Connection::class);
    $chk  = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_order_offers'");
    if ((int)($chk[0]['cnt']??0) === 0) {
        $conn->execute("
            CREATE TABLE `gd_order_offers` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `order_id`   INT UNSIGNED NOT NULL,
                `driver_id`  INT UNSIGNED NOT NULL,
                `expires_at` DATETIME     NOT NULL,
                `status`     ENUM('pending','accepted','declined','expired') NOT NULL DEFAULT 'pending',
                `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_driver_status` (`driver_id`, `status`),
                INDEX `idx_order_status`  (`order_id`,  `status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
} catch (\Throwable) {}

// ── Migration v7: portal_pin on vendors ──────────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    $chkPin = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='gd_vendors' AND COLUMN_NAME='portal_pin'");
    if ((int)($chkPin[0]['cnt']??0) === 0) {
        $conn->execute("ALTER TABLE `gd_vendors` ADD COLUMN `portal_pin` CHAR(4) NULL DEFAULT NULL AFTER `vendor_token`");
    }
} catch (\Throwable) {}

// ── Auto-create delivery page ─────────────────────────────────────────────────
try {
    $qb   = $container->get(QueryBuilder::class);
    $flag = $qb->table('gd_settings')->where('key', '=', 'pages_created')->first();
    if (!$flag || $flag['value'] !== '1') {
        $deliverySlug = 'delivery';
        try { $s = $qb->table('gd_settings')->where('key','=','page_slug')->first(); if ($s) $deliverySlug = $s['value']; } catch (\Throwable) {}
        if (!$qb->table('posts')->where('slug', '=', $deliverySlug)->first()) {
            $user = $qb->table('users')->orderBy('id','ASC')->first();
            $qb->table('posts')->insert(['type'=>'page','title'=>'Delivery','slug'=>$deliverySlug,'content'=>'','status'=>'published','author_id'=>$user?(int)$user['id']:1]);
        }
        if ($flag) $qb->table('gd_settings')->where('key','=','pages_created')->update(['value'=>'1']);
        else $qb->table('gd_settings')->insert(['key'=>'pages_created','value'=>'1']);
    }
} catch (\Throwable) {}

// ── DI ────────────────────────────────────────────────────────────────────────
$container->singleton(DeliveryService::class, static fn($c) => new DeliveryService($c->get(QueryBuilder::class)));
$container->bind(AdminController::class, static fn($c) => new AdminController(
    $c->get(DeliveryService::class), $c->get(QueryBuilder::class),
    $c->get(LoginService::class), $c->get(HookManager::class),
    $c->get(MailService::class),
    (string) $c->get(\GoniCore\Core\Config\Config::class)->get('app.name', 'GoniCore'),
));
$container->bind(FrontendController::class, static fn($c) => new FrontendController($c->get(DeliveryService::class)));

// ── Admin routes ──────────────────────────────────────────────────────────────
$router->group('/manage/delivery', static function ($r) use ($container): void {
    $r->get('',                              [AdminController::class, 'dashboard']);
    $r->get('/orders',                       [AdminController::class, 'orders']);
    $r->get('/orders/{id}',                  [AdminController::class, 'orderView']);
    $r->post('/orders/{id}/update',          [AdminController::class, 'orderUpdate']);
    $r->get('/drivers',                      [AdminController::class, 'drivers']);
    $r->post('/drivers/create',              [AdminController::class, 'driverCreate']);
    $r->post('/drivers/{id}/update',         [AdminController::class, 'driverUpdate']);
    $r->post('/drivers/{id}/delete',         [AdminController::class, 'driverDelete']);
    $r->get('/zones',                        [AdminController::class, 'zones']);
    $r->post('/zones/create',                [AdminController::class, 'zoneCreate']);
    $r->post('/zones/{id}/update',           [AdminController::class, 'zoneUpdate']);
    $r->post('/zones/{id}/delete',           [AdminController::class, 'zoneDelete']);
    $r->get('/settings',                     [AdminController::class, 'settingsForm']);
    $r->post('/settings',                    [AdminController::class, 'settingsSave']);
    $r->get('/livemap',                      [AdminController::class, 'liveMap']);
    // Vendors
    $r->get('/vendors',                      [AdminController::class, 'vendors']);
    $r->get('/vendors/create',               [AdminController::class, 'vendorCreate']);
    $r->post('/vendors/create',              [AdminController::class, 'vendorCreatePost']);
    $r->get('/vendors/{id}',                 [AdminController::class, 'vendorEdit']);
    $r->post('/vendors/{id}/update',         [AdminController::class, 'vendorUpdate']);
    $r->post('/vendors/{id}/delete',         [AdminController::class, 'vendorDelete']);
    $r->post('/vendors/{id}/token',          [AdminController::class, 'vendorRegenToken']);
    // Catalog
    $r->get('/vendors/{id}/catalog',         [AdminController::class, 'catalog']);
    $r->post('/vendors/{id}/categories/create',   [AdminController::class, 'categoryCreate']);
    $r->post('/categories/{id}/update',      [AdminController::class, 'categoryUpdate']);
    $r->post('/categories/{id}/delete',      [AdminController::class, 'categoryDelete']);
    $r->post('/vendors/{id}/products/create',[AdminController::class, 'productCreate']);
    $r->post('/products/{id}/update',        [AdminController::class, 'productUpdate']);
    $r->post('/products/{id}/delete',        [AdminController::class, 'productDelete']);
    $r->post('/products/{id}/stock',         [AdminController::class, 'productToggleStock']);
    $r->post('/products/{id}/modifiers',     [AdminController::class, 'modifierGroupCreate']);
    $r->post('/modifier-groups/{id}/delete', [AdminController::class, 'modifierGroupDelete']);
    $r->post('/modifiers/create',            [AdminController::class, 'modifierCreate']);
    $r->post('/modifiers/{id}/delete',       [AdminController::class, 'modifierDelete']);
});

// ── Frontend routes ───────────────────────────────────────────────────────────
try { $_slug = $container->get(DeliveryService::class)->setting('page_slug', 'delivery'); }
catch (\Throwable) { $_slug = 'delivery'; }

// Customer
$router->get('/' . $_slug,                           [FrontendController::class, 'index']);
$router->get('/' . $_slug . '/auth',                 [FrontendController::class, 'authPage']);
$router->post('/api/delivery/otp/send',              [FrontendController::class, 'apiOtpSend']);
$router->post('/api/delivery/otp/verify',            [FrontendController::class, 'apiOtpVerify']);
$router->get('/' . $_slug . '/logout',               [FrontendController::class, 'logout']);
$router->get('/' . $_slug . '/vendor/{slug}',        [FrontendController::class, 'vendorMenu']);
$router->post('/api/delivery/cart/add',              [FrontendController::class, 'apiCartAdd']);
$router->post('/api/delivery/cart/remove',           [FrontendController::class, 'apiCartRemove']);
$router->post('/api/delivery/cart/clear',            [FrontendController::class, 'apiCartClear']);
$router->get('/api/delivery/cart',                   [FrontendController::class, 'apiCartGet']);
$router->get('/api/delivery/livemap-data',           [FrontendController::class, 'apiLiveMapData']);
$router->get('/' . $_slug . '/cart',                 [FrontendController::class, 'cartPage']);
$router->post('/api/delivery/cart/update',           [FrontendController::class, 'apiCartUpdate']);
$router->get('/' . $_slug . '/checkout',             [FrontendController::class, 'checkout']);
$router->post('/' . $_slug . '/checkout',            [FrontendController::class, 'checkoutPost']);
$router->get('/' . $_slug . '/track/{token}',          [FrontendController::class, 'track']);
$router->get('/api/delivery/track/{token}/status',    [FrontendController::class, 'apiTrackStatus']);
$router->post('/delivery/bog-callback',              [FrontendController::class, 'bogCallback']);
// Legacy
$router->post('/' . $_slug . '/place',               [FrontendController::class, 'place']);
// Vendor portal
$router->get('/delivery/portal/{token}',                                   [FrontendController::class, 'vendorPortal']);
$router->post('/api/delivery/vendor/{token}/order/{id}/status',            [FrontendController::class, 'apiVendorOrderStatus']);
$router->post('/api/delivery/vendor/{token}/branch-auth',                   [FrontendController::class, 'apiBranchAuth']);
// Vendor admin
$router->get('/delivery/portal/{token}/admin',                              [FrontendController::class, 'vendorAdmin']);
$router->post('/api/delivery/portal/{token}/admin/update',                  [FrontendController::class, 'apiPortalAdminUpdate']);
$router->post('/api/delivery/portal/{token}/admin/upload',                  [FrontendController::class, 'apiPortalUpload']);
$router->get('/api/delivery/portal/{token}/branches',                       [FrontendController::class, 'apiPortalBranchesList']);
$router->post('/api/delivery/portal/{token}/branches',                      [FrontendController::class, 'apiPortalBranchCreate']);
$router->post('/api/delivery/portal/{token}/branches/{id}/update',          [FrontendController::class, 'apiPortalBranchUpdate']);
$router->post('/api/delivery/portal/{token}/branches/{id}/delete',          [FrontendController::class, 'apiPortalBranchDelete']);
// Vendor portal — catalog API
$router->get('/api/delivery/portal/{token}/catalog',                       [FrontendController::class, 'apiPortalCatalog']);
$router->post('/api/delivery/portal/{token}/categories',                   [FrontendController::class, 'apiPortalCategoryCreate']);
$router->post('/api/delivery/portal/{token}/categories/{id}/update',       [FrontendController::class, 'apiPortalCategoryUpdate']);
$router->post('/api/delivery/portal/{token}/categories/{id}/delete',       [FrontendController::class, 'apiPortalCategoryDelete']);
$router->post('/api/delivery/portal/{token}/products',                     [FrontendController::class, 'apiPortalProductCreate']);
$router->post('/api/delivery/portal/{token}/products/{id}/update',         [FrontendController::class, 'apiPortalProductUpdate']);
$router->post('/api/delivery/portal/{token}/products/{id}/delete',         [FrontendController::class, 'apiPortalProductDelete']);
$router->post('/api/delivery/portal/{token}/products/{id}/stock',          [FrontendController::class, 'apiPortalProductStock']);
// Vendor portal — offers API
$router->get('/api/delivery/portal/{token}/offers',                        [FrontendController::class, 'apiPortalOffersList']);
$router->post('/api/delivery/portal/{token}/offers',                       [FrontendController::class, 'apiPortalOfferCreate']);
$router->post('/api/delivery/portal/{token}/offers/{id}/update',           [FrontendController::class, 'apiPortalOfferUpdate']);
$router->post('/api/delivery/portal/{token}/offers/{id}/delete',           [FrontendController::class, 'apiPortalOfferDelete']);
// Vendor portal — combos API
$router->get('/api/delivery/portal/{token}/combos',                          [FrontendController::class, 'apiPortalCombosList']);
$router->post('/api/delivery/portal/{token}/combos',                         [FrontendController::class, 'apiPortalComboCreate']);
$router->post('/api/delivery/portal/{token}/combos/{id}/update',             [FrontendController::class, 'apiPortalComboUpdate']);
$router->post('/api/delivery/portal/{token}/combos/{id}/delete',             [FrontendController::class, 'apiPortalComboDelete']);
$router->post('/api/delivery/portal/{token}/combos/{id}/items',              [FrontendController::class, 'apiPortalComboSetItems']);
$router->get('/api/delivery/portal/{token}/products/{id}/combos',            [FrontendController::class, 'apiPortalProductCombos']);
$router->post('/api/delivery/portal/{token}/products/{id}/combos',           [FrontendController::class, 'apiPortalProductSyncCombos']);
// Vendor portal — combo meals API
$router->get('/api/delivery/portal/{token}/combo-meals',                              [FrontendController::class, 'apiPortalComboMealList']);
$router->post('/api/delivery/portal/{token}/combo-meals',                             [FrontendController::class, 'apiPortalComboMealCreate']);
$router->post('/api/delivery/portal/{token}/combo-meals/{id}/update',                 [FrontendController::class, 'apiPortalComboMealUpdate']);
$router->post('/api/delivery/portal/{token}/combo-meals/{id}/delete',                 [FrontendController::class, 'apiPortalComboMealDelete']);
// Vendor portal — product modifier groups
$router->get('/api/delivery/portal/{token}/products/{id}/modifiers',        [FrontendController::class, 'apiPortalProductModifiers']);
$router->post('/api/delivery/portal/{token}/products/{id}/modifiers/save',  [FrontendController::class, 'apiPortalProductModifiersSave']);
// Vendor portal — modifier templates
$router->get('/api/delivery/portal/{token}/modifier-templates',                          [FrontendController::class, 'apiPortalModifierTemplateList']);
$router->post('/api/delivery/portal/{token}/modifier-templates',                         [FrontendController::class, 'apiPortalModifierTemplateCreate']);
$router->post('/api/delivery/portal/{token}/modifier-templates/{id}/update',             [FrontendController::class, 'apiPortalModifierTemplateUpdate']);
$router->post('/api/delivery/portal/{token}/modifier-templates/{id}/delete',             [FrontendController::class, 'apiPortalModifierTemplateDelete']);
$router->post('/api/delivery/portal/{token}/modifier-templates/{id}/apply-all',          [FrontendController::class, 'apiPortalModifierTemplateApplyAll']);
// Courier portal
$router->get('/delivery/courier/{token}',                             [FrontendController::class, 'courierPortal']);
$router->get('/api/delivery/courier/{token}/poll',                    [FrontendController::class, 'apiCourierPoll']);
$router->post('/api/delivery/courier/{token}/offer/{offer_id}/accept',[FrontendController::class, 'apiCourierOfferAccept']);
$router->post('/api/delivery/courier/{token}/offer/{offer_id}/decline',[FrontendController::class, 'apiCourierOfferDecline']);
$router->post('/api/delivery/courier/{token}/order/{id}/status',      [FrontendController::class, 'apiCourierOrderStatus']);
$router->post('/api/delivery/courier/{token}/location',               [FrontendController::class, 'apiCourierLocation']);
$router->post('/api/delivery/courier/{token}/online',                 [FrontendController::class, 'apiCourierToggleOnline']);
unset($_slug);

// ── Sidebar nav ───────────────────────────────────────────────────────────────
gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $h    = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
    $isDel = str_starts_with($activeNav, 'delivery');
    $open  = $isDel ? ' open' : '';
    $sub   = static function(string $url, string $icon, string $label, string $key) use ($h, $activeNav): string {
        $cls = $activeNav === $key ? ' active' : '';
        return '<li class="nav-sub"><a href="'.$h($url).'" class="'.$cls.'">'
             . '<span class="nav-icon">'.$icon.'</span> '.$label.'</a></li>';
    };
    echo '<li>'
       . '<div class="nav-parent-toggle'.$open.'" onclick="navToggle(this)">'
       . '<span class="nav-icon">🛵</span> GoniDelivery'
       . '<span class="nav-arrow">▾</span>'
       . '</div>'
       . '<ul class="nav-children'.$open.'">'
       . $sub($base.'/manage/delivery',          '📊', 'Dashboard', 'delivery')
       . $sub($base.'/manage/delivery/orders',   '🗒', 'Orders',   'delivery-orders')
       . $sub($base.'/manage/delivery/vendors',  '🏪', 'Vendors',  'delivery-vendors')
       . $sub($base.'/manage/delivery/drivers',  '🛵', 'Couriers', 'delivery-drivers')
       . $sub($base.'/manage/delivery/zones',    '📍', 'Zones',    'delivery-zones')
       . $sub($base.'/manage/delivery/settings', '⚙',  'Settings', 'delivery-settings')
       . $sub($base.'/manage/delivery/livemap',  '🗺', 'Live Map', 'delivery-livemap')
       . '</ul></li>';
}, 30);

gc_filter('page.intercept', static function (mixed $ex, array $post, \GoniCore\Core\Http\Request $request) use ($container): mixed {
    try {
        $s = $container->get(DeliveryService::class)->setting('page_slug', 'delivery');
        if ($post['slug'] === $s) return \GoniCore\Core\Http\Response::redirect($request->basePath() . '/' . $s);
    } catch (\Throwable) {}
    return $ex;
});
