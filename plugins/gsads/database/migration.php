<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        // ── Ad zones (placements) ──────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gsads_zones` (
                `id`          INT UNSIGNED      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name`        VARCHAR(150)      NOT NULL,
                `slug`        VARCHAR(100)      NOT NULL UNIQUE,
                `description` VARCHAR(500)      NOT NULL DEFAULT '',
                `width`       SMALLINT UNSIGNED NULL DEFAULT NULL
                              COMMENT 'pixel width hint for image ads (0 = flexible)',
                `height`      SMALLINT UNSIGNED NULL DEFAULT NULL,
                `active`      TINYINT(1)        NOT NULL DEFAULT 1,
                `created_at`  TIMESTAMP         NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Individual ads ─────────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gsads_ads` (
                `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `zone_id`     INT UNSIGNED  NOT NULL,
                `name`        VARCHAR(200)  NOT NULL,
                `type`        ENUM('image','html','text') NOT NULL DEFAULT 'image',

                -- image type
                `image_url`   TEXT          NOT NULL DEFAULT '',
                -- text + image types
                `link_url`    TEXT          NOT NULL DEFAULT '',
                -- html type
                `html_code`   LONGTEXT      NOT NULL DEFAULT '',
                -- text type
                `ad_title`    VARCHAR(300)  NOT NULL DEFAULT '',
                `ad_body`     TEXT          NOT NULL DEFAULT '',

                `opens_blank` TINYINT(1)    NOT NULL DEFAULT 1
                              COMMENT '1 = open link in new tab',
                `weight`      TINYINT UNSIGNED NOT NULL DEFAULT 10
                              COMMENT 'rotation weight 1–255 (higher = more likely)',
                `starts_at`   DATE          NULL DEFAULT NULL,
                `ends_at`     DATE          NULL DEFAULT NULL,
                `active`      TINYINT(1)    NOT NULL DEFAULT 1,
                `impressions` INT UNSIGNED  NOT NULL DEFAULT 0,
                `clicks`      INT UNSIGNED  NOT NULL DEFAULT 0,
                `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                              ON UPDATE CURRENT_TIMESTAMP,

                INDEX `gsads_ads_zone_idx`   (`zone_id`),
                INDEX `gsads_ads_active_idx` (`active`, `zone_id`),
                CONSTRAINT `gsads_ads_zone_fk`
                    FOREIGN KEY (`zone_id`) REFERENCES `gsads_zones`(`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $conn): void
    {
        $conn->execute("DROP TABLE IF EXISTS `gsads_ads`");
        $conn->execute("DROP TABLE IF EXISTS `gsads_zones`");
    }
};
