<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        // ── Settings table ─────────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `goniseo_settings` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `key`        VARCHAR(100) NOT NULL UNIQUE,
                `value`      TEXT         NOT NULL,
                `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Per-URL meta overrides ─────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `goniseo_meta` (
                `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `url_path`       VARCHAR(500)  NOT NULL UNIQUE
                                 COMMENT 'Path relative to app root, e.g. /about',
                `title`          VARCHAR(200)  NOT NULL DEFAULT '',
                `description`    VARCHAR(500)  NOT NULL DEFAULT '',
                `keywords`       VARCHAR(300)  NOT NULL DEFAULT '',
                `og_title`       VARCHAR(200)  NOT NULL DEFAULT '',
                `og_description` VARCHAR(500)  NOT NULL DEFAULT '',
                `og_image`       VARCHAR(500)  NOT NULL DEFAULT '',
                `canonical`      VARCHAR(500)  NOT NULL DEFAULT '',
                `robots`         VARCHAR(50)   NOT NULL DEFAULT ''
                                 COMMENT 'e.g. index,follow or noindex,nofollow',
                `json_ld`        LONGTEXT      NOT NULL DEFAULT '',
                `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
                                 ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Custom sitemap entries ─────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `goniseo_sitemap` (
                `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `url`         VARCHAR(500) NOT NULL,
                `priority`    DECIMAL(2,1) NOT NULL DEFAULT 0.5,
                `changefreq`  VARCHAR(10)  NOT NULL DEFAULT 'weekly',
                `lastmod`     DATE         NULL     DEFAULT NULL,
                `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Default settings ───────────────────────────────────────────────────
        $defaults = [
            'enabled'             => '1',
            'title_format'        => '{title} | {site_name}',
            'site_name'           => '',
            'default_description' => '',
            'default_keywords'    => '',
            'default_og_image'    => '',
            'default_robots'      => 'index,follow',
            'google_verify'       => '',
            'bing_verify'         => '',
            'robots_txt'          => "User-agent: *\nAllow: /",
            'manage_robots'       => '1',
        ];

        foreach ($defaults as $key => $value) {
            try {
                $conn->execute(
                    "INSERT IGNORE INTO `goniseo_settings` (`key`, `value`) VALUES (?, ?)",
                    [$key, $value]
                );
            } catch (\Throwable) {}
        }
    }

    public function down(Connection $conn): void
    {
        $conn->execute("DROP TABLE IF EXISTS `goniseo_sitemap`");
        $conn->execute("DROP TABLE IF EXISTS `goniseo_meta`");
        $conn->execute("DROP TABLE IF EXISTS `goniseo_settings`");
    }
};
