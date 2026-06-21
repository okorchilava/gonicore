<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        // ── Popups ──────────────────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS gcpopup_popups (
                id              INT UNSIGNED    NOT NULL AUTO_INCREMENT,
                name            VARCHAR(150)    NOT NULL DEFAULT '',
                title           VARCHAR(400)    NOT NULL DEFAULT '',
                subtitle        VARCHAR(400)    NOT NULL DEFAULT '',

                image_url       VARCHAR(600)    NOT NULL DEFAULT '',
                image_alt       VARCHAR(200)    NOT NULL DEFAULT '',
                image_bg_color  VARCHAR(20)     NOT NULL DEFAULT '#e0fdf4',

                badge_text      VARCHAR(200)    NOT NULL DEFAULT '',
                badge_color     VARCHAR(20)     NOT NULL DEFAULT '#d1fae5',
                badge_text_color VARCHAR(20)    NOT NULL DEFAULT '#065f46',

                btn_text        VARCHAR(100)    NOT NULL DEFAULT '',
                btn_url         VARCHAR(600)    NOT NULL DEFAULT '',
                btn_color       VARCHAR(20)     NOT NULL DEFAULT '#2563eb',
                btn_text_color  VARCHAR(20)     NOT NULL DEFAULT '#ffffff',

                footer_text     VARCHAR(200)    NOT NULL DEFAULT '',
                footer_link_text VARCHAR(100)   NOT NULL DEFAULT '',
                footer_link_url  VARCHAR(600)   NOT NULL DEFAULT '',

                trigger_type    ENUM('load','scroll','exit','manual') NOT NULL DEFAULT 'load',
                trigger_delay   SMALLINT UNSIGNED NOT NULL DEFAULT 3,
                trigger_scroll  TINYINT UNSIGNED  NOT NULL DEFAULT 50,

                show_frequency  ENUM('always','once_session','once_day','once_ever') NOT NULL DEFAULT 'once_session',
                target_pages    VARCHAR(2000)   NOT NULL DEFAULT '',

                popup_width     SMALLINT UNSIGNED NOT NULL DEFAULT 420,
                overlay_opacity TINYINT UNSIGNED  NOT NULL DEFAULT 60,
                animation       ENUM('slide','fade','zoom') NOT NULL DEFAULT 'slide',
                close_on_overlay TINYINT UNSIGNED NOT NULL DEFAULT 1,
                show_close_btn   TINYINT UNSIGNED NOT NULL DEFAULT 1,

                active          TINYINT UNSIGNED NOT NULL DEFAULT 1,
                sort_order      SMALLINT         NOT NULL DEFAULT 0,
                created_at      TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,

                PRIMARY KEY (id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // ── Feature list items ──────────────────────────────────────────────────
        $conn->execute("
            CREATE TABLE IF NOT EXISTS gcpopup_items (
                id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
                popup_id    INT UNSIGNED NOT NULL,
                icon        VARCHAR(30)  NOT NULL DEFAULT '',
                text        VARCHAR(400) NOT NULL DEFAULT '',
                sort_order  SMALLINT     NOT NULL DEFAULT 0,

                PRIMARY KEY (id),
                KEY idx_popup (popup_id),
                CONSTRAINT fk_gcpopup_items_popup
                    FOREIGN KEY (popup_id) REFERENCES gcpopup_popups (id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }
};
