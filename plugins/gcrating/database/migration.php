<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        // ── Visitor sessions ───────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gcrating_sessions` (
                `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `session_hash`  VARCHAR(32)      NOT NULL UNIQUE
                                COMMENT 'Random ID from sessionStorage — one per browser tab session',
                `visitor_hash`  VARCHAR(32)      NOT NULL
                                COMMENT 'Random ID from localStorage — unique visitor across sessions',
                `device`        ENUM('desktop','mobile','tablet') NOT NULL DEFAULT 'desktop',
                `browser`       VARCHAR(60)      NOT NULL DEFAULT '',
                `os`            VARCHAR(60)      NOT NULL DEFAULT '',
                `source_type`   ENUM('direct','search','social','referral','internal') NOT NULL DEFAULT 'direct',
                `referrer`      VARCHAR(500)     NOT NULL DEFAULT '',
                `referrer_host` VARCHAR(120)     NOT NULL DEFAULT '',
                `utm_source`    VARCHAR(100)     NOT NULL DEFAULT '',
                `utm_medium`    VARCHAR(100)     NOT NULL DEFAULT '',
                `utm_campaign`  VARCHAR(100)     NOT NULL DEFAULT '',
                `pages_viewed`  SMALLINT UNSIGNED NOT NULL DEFAULT 1,
                `total_time`    MEDIUMINT UNSIGNED NOT NULL DEFAULT 0
                                COMMENT 'Total seconds across all pageviews in this session',
                `created_at`    DATETIME         NOT NULL,
                `updated_at`    DATETIME         NOT NULL,
                INDEX `gcr_visitor_idx`   (`visitor_hash`),
                INDEX `gcr_created_idx`   (`created_at`),
                INDEX `gcr_source_idx`    (`source_type`),
                INDEX `gcr_date_vis_idx`  (`created_at`, `visitor_hash`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Individual page views ──────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gcrating_pageviews` (
                `id`          INT UNSIGNED      NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `session_id`  INT UNSIGNED      NOT NULL,
                `url`         VARCHAR(500)      NOT NULL,
                `title`       VARCHAR(300)      NOT NULL DEFAULT '',
                `time_spent`  MEDIUMINT UNSIGNED NOT NULL DEFAULT 0
                              COMMENT 'Seconds spent on this page (updated via sendBeacon)',
                `created_at`  DATETIME          NOT NULL,
                INDEX `gcr_pv_session_idx` (`session_id`),
                INDEX `gcr_pv_url_idx`     (`url`(120)),
                INDEX `gcr_pv_created_idx` (`created_at`),
                CONSTRAINT `fk_gcrating_pv_session`
                    FOREIGN KEY (`session_id`) REFERENCES `gcrating_sessions`(`id`)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Plugin settings ────────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gcrating_settings` (
                `key`   VARCHAR(80)  NOT NULL PRIMARY KEY,
                `value` TEXT         NOT NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Default settings
        $pdo  = $conn->pdo();
        $stmt = $pdo->prepare(
            "INSERT IGNORE INTO `gcrating_settings` (`key`, `value`) VALUES (?, ?)"
        );
        foreach ([
            ['enabled',        '1'],
            ['exclude_admin',  '1'],
            ['anonymize_ip',   '1'],
            ['exclude_bots',   '1'],
            ['retention_days', '365'],
            ['exclude_ips',    ''],
        ] as [$k, $v]) {
            $stmt->execute([$k, $v]);
        }
    }

    public function down(Connection $conn): void
    {
        $conn->execute("DROP TABLE IF EXISTS `gcrating_pageviews`");
        $conn->execute("DROP TABLE IF EXISTS `gcrating_sessions`");
        $conn->execute("DROP TABLE IF EXISTS `gcrating_settings`");
    }
};
