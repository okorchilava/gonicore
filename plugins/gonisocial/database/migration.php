<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        // ── Settings ───────────────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gonisocial_settings` (
                `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `key`        VARCHAR(100) NOT NULL UNIQUE,
                `value`      TEXT         NOT NULL,
                `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Social profiles (Facebook page, Instagram account, etc.) ──────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gonisocial_profiles` (
                `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT PRIMARY KEY,
                `network`      VARCHAR(50)   NOT NULL,
                `display_name` VARCHAR(150)  NOT NULL DEFAULT '',
                `url`          VARCHAR(500)  NOT NULL,
                `handle`       VARCHAR(100)  NOT NULL DEFAULT '',
                `active`       TINYINT(1)    NOT NULL DEFAULT 1,
                `sort_order`   SMALLINT      NOT NULL DEFAULT 0,
                `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gonisocial_profiles_net_idx` (`network`, `active`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Default settings ───────────────────────────────────────────────────
        $defaults = [
            'enabled'           => '1',
            // OG / social meta
            'og_enabled'        => '1',
            'og_type'           => 'website',
            'og_site_name'      => '',
            'og_default_image'  => '',
            'twitter_card'      => 'summary_large_image',
            'twitter_handle'    => '',
            'facebook_app_id'   => '',
            // Share buttons
            'share_enabled'     => '1',
            'share_position'    => 'floating-left',
            'share_networks'    => 'facebook,twitter,whatsapp,telegram,linkedin',
            'share_hide_mobile' => '0',
        ];

        foreach ($defaults as $key => $value) {
            try {
                $conn->execute(
                    "INSERT IGNORE INTO `gonisocial_settings` (`key`, `value`) VALUES (?, ?)",
                    [$key, $value]
                );
            } catch (\Throwable) {}
        }
    }

    public function down(Connection $conn): void
    {
        $conn->execute("DROP TABLE IF EXISTS `gonisocial_profiles`");
        $conn->execute("DROP TABLE IF EXISTS `gonisocial_settings`");
    }
};
