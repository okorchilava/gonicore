<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;

/**
 * GC Testimonials — schema.
 *
 * Runs when the plugin is ACTIVATED (PluginManager::activate → up()) and is
 * dropped on delete (down()). Two tables:
 *   gc_testimonial_campaigns — named placements (e.g. "Home page"), referenced
 *                              by slug from the shortcodes.
 *   gc_testimonials          — the reviews themselves (moderated via is_public).
 */
return new class {
    public function up(Connection $conn): void
    {
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gc_testimonial_campaigns` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`       VARCHAR(255) NOT NULL,
                `slug`       VARCHAR(150) NOT NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `gct_campaign_slug_unique` (`slug`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gc_testimonials` (
                `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `campaign_id`      INT UNSIGNED NOT NULL DEFAULT 0,
                `client_name`      VARCHAR(255) NOT NULL,
                `client_role`      VARCHAR(255) NOT NULL DEFAULT '',
                `testimonial_text` TEXT NOT NULL,
                `rating`           TINYINT UNSIGNED NOT NULL DEFAULT 5,
                `is_public`        TINYINT(1) NOT NULL DEFAULT 0,
                `created_at`       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `gct_campaign_public_idx` (`campaign_id`, `is_public`),
                KEY `gct_public_idx` (`is_public`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $conn): void
    {
        // gc_testimonials first (no FK, but keep a sane order).
        $conn->execute('DROP TABLE IF EXISTS `gc_testimonials`');
        $conn->execute('DROP TABLE IF EXISTS `gc_testimonial_campaigns`');
    }
};
