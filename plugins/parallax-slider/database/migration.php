<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `ps_sliders` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `name`       VARCHAR(255) NOT NULL DEFAULT 'Untitled Slider',
                `settings`   LONGTEXT     NOT NULL DEFAULT '{}',
                `active`     TINYINT(1)   NOT NULL DEFAULT 1,
                `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `ps_slides` (
                `id`               INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `slider_id`        INT UNSIGNED NOT NULL,
                `title`            VARCHAR(255) NOT NULL DEFAULT '',
                `order_index`      INT          NOT NULL DEFAULT 0,
                `bg_type`          VARCHAR(20)  NOT NULL DEFAULT 'color',
                `bg_value`         TEXT         NOT NULL DEFAULT '',
                `bg_overlay`       FLOAT        NOT NULL DEFAULT 0,
                `bg_overlay_color` VARCHAR(20)  NOT NULL DEFAULT '#000000',
                `duration`         INT          NOT NULL DEFAULT 6000,
                `link`             VARCHAR(500) NOT NULL DEFAULT '',
                `link_target`      VARCHAR(10)  NOT NULL DEFAULT '_self',
                `kenburns`         TINYINT(1)   NOT NULL DEFAULT 0,
                `active`           TINYINT(1)   NOT NULL DEFAULT 1,
                `created_at`       TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT `fk_ps_slides_slider`
                    FOREIGN KEY (`slider_id`) REFERENCES `ps_sliders`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `ps_layers` (
                `id`                 INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `slide_id`           INT UNSIGNED NOT NULL,
                `type`               VARCHAR(20)  NOT NULL DEFAULT 'text',
                `content`            LONGTEXT     NOT NULL DEFAULT '',
                `x`                  FLOAT        NOT NULL DEFAULT 50,
                `y`                  FLOAT        NOT NULL DEFAULT 50,
                `width`              VARCHAR(20)  NOT NULL DEFAULT 'auto',
                `height`             VARCHAR(20)  NOT NULL DEFAULT 'auto',
                `depth`              FLOAT        NOT NULL DEFAULT 0.5,
                `anim_in`            VARCHAR(50)  NOT NULL DEFAULT 'fadeIn',
                `anim_out`           VARCHAR(50)  NOT NULL DEFAULT 'fadeOut',
                `anim_delay`         INT          NOT NULL DEFAULT 300,
                `anim_duration`      INT          NOT NULL DEFAULT 700,
                `anim_out_delay`     INT          NOT NULL DEFAULT 0,
                `settings`           LONGTEXT     NOT NULL DEFAULT '{}',
                `order_index`        INT          NOT NULL DEFAULT 0,
                CONSTRAINT `fk_ps_layers_slide`
                    FOREIGN KEY (`slide_id`) REFERENCES `ps_slides`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $conn): void
    {
        $conn->execute('DROP TABLE IF EXISTS `ps_layers`');
        $conn->execute('DROP TABLE IF EXISTS `ps_slides`');
        $conn->execute('DROP TABLE IF EXISTS `ps_sliders`');
    }
};
