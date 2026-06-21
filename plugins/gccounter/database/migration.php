<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        // ── Counter groups ─────────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gccounter_groups` (
                `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name`        VARCHAR(150)  NOT NULL,
                `slug`        VARCHAR(150)  NOT NULL UNIQUE
                              COMMENT 'Used in shortcode: [gccounter slug=\"my-slug\"]',
                `columns`     TINYINT UNSIGNED NOT NULL DEFAULT 4
                              COMMENT '2–6 grid columns',
                `duration_ms` SMALLINT UNSIGNED NOT NULL DEFAULT 2000
                              COMMENT 'Animation duration in milliseconds',
                `separator`   VARCHAR(1)    NOT NULL DEFAULT ','
                              COMMENT 'Thousands separator: , or . or empty',
                `align`       VARCHAR(10)   NOT NULL DEFAULT 'center',
                `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Counter items ──────────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gccounter_items` (
                `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `group_id`    INT UNSIGNED  NOT NULL,
                `number`      BIGINT        NOT NULL DEFAULT 0,
                `prefix`      VARCHAR(20)   NOT NULL DEFAULT '',
                `suffix`      VARCHAR(20)   NOT NULL DEFAULT '',
                `label`       VARCHAR(200)  NOT NULL DEFAULT '',
                `description` VARCHAR(500)  NOT NULL DEFAULT '',
                `color`       VARCHAR(20)   NOT NULL DEFAULT '#10B27C',
                `sort_order`  SMALLINT      NOT NULL DEFAULT 0,
                `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gccounter_items_group_idx` (`group_id`, `sort_order`),
                CONSTRAINT `fk_gccounter_items_group`
                    FOREIGN KEY (`group_id`) REFERENCES `gccounter_groups`(`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $conn): void
    {
        $conn->execute("DROP TABLE IF EXISTS `gccounter_items`");
        $conn->execute("DROP TABLE IF EXISTS `gccounter_groups`");
    }
};
