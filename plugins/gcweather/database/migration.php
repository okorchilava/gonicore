<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        // ── Saved locations ────────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gcweather_locations` (
                `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `name`         VARCHAR(150)    NOT NULL
                               COMMENT 'Canonical name from geocoding API',
                `display_name` VARCHAR(150)    NOT NULL DEFAULT ''
                               COMMENT 'Overridable display name shown in widget',
                `country_code` VARCHAR(4)      NOT NULL DEFAULT '',
                `timezone`     VARCHAR(60)     NOT NULL DEFAULT 'UTC',
                `latitude`     DECIMAL(9,6)    NOT NULL,
                `longitude`    DECIMAL(9,6)    NOT NULL,
                `active`       TINYINT(1)      NOT NULL DEFAULT 1,
                `sort_order`   SMALLINT        NOT NULL DEFAULT 0,
                `created_at`   TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gcw_loc_active_idx` (`active`, `sort_order`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Cached API responses ───────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gcweather_cache` (
                `id`           INT UNSIGNED    NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `location_id`  INT UNSIGNED    NOT NULL UNIQUE,
                `weather_json` MEDIUMTEXT      NOT NULL
                               COMMENT 'Parsed & normalised weather data as JSON',
                `fetched_at`   DATETIME        NOT NULL,
                `expires_at`   DATETIME        NOT NULL,
                INDEX `gcw_cache_exp_idx` (`expires_at`),
                CONSTRAINT `fk_gcweather_cache_loc`
                    FOREIGN KEY (`location_id`) REFERENCES `gcweather_locations`(`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Settings ───────────────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gcweather_settings` (
                `key`   VARCHAR(80)  NOT NULL PRIMARY KEY,
                `value` TEXT         NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo  = $conn->pdo();
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO `gcweather_settings` (`key`, `value`) VALUES (?, ?)"
        );
        foreach ([
            ['temperature_unit',    'celsius'],
            ['windspeed_unit',      'kmh'],
            ['precipitation_unit',  'mm'],
            ['cache_minutes',       '30'],
            ['default_style',       'card'],
            ['forecast_days',       '7'],
            ['show_feels_like',     '1'],
            ['show_humidity',       '1'],
            ['show_wind',           '1'],
            ['show_pressure',       '0'],
            ['show_sunrise_sunset', '1'],
            ['show_hourly',         '1'],
            ['show_daily',          '1'],
        ] as [$k, $v]) {
            $stmt->execute([$k, $v]);
        }
    }

    public function down(Connection $conn): void
    {
        $conn->execute("DROP TABLE IF EXISTS `gcweather_cache`");
        $conn->execute("DROP TABLE IF EXISTS `gcweather_locations`");
        $conn->execute("DROP TABLE IF EXISTS `gcweather_settings`");
    }
};
