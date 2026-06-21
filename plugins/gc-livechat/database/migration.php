<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;

/**
 * GC Live Chat — schema.
 *
 *   gc_chat_conversations — one row per visitor chat session.
 *       status: ai | waiting | operator | closed
 *   gc_chat_messages      — every message (visitor / ai / operator / system).
 *
 * Settings (provider, API key, model, prompts …) live in the core `settings`
 * table via gc_setting(), so no settings table is needed here.
 */
return new class {
    public function up(Connection $conn): void
    {
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gc_chat_conversations` (
                `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `token`          CHAR(40) NOT NULL,
                `visitor_name`   VARCHAR(120) NOT NULL DEFAULT '',
                `visitor_email`  VARCHAR(190) NOT NULL DEFAULT '',
                `status`         VARCHAR(20) NOT NULL DEFAULT 'ai',
                `operator_id`    INT UNSIGNED NULL DEFAULT NULL,
                `summary`        VARCHAR(255) NOT NULL DEFAULT '',
                `topic`          VARCHAR(60) NOT NULL DEFAULT '',
                `ip`             VARCHAR(45) NOT NULL DEFAULT '',
                `created_at`     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `last_at`        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `gcc_token_unique` (`token`),
                KEY `gcc_status_idx` (`status`, `last_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gc_chat_messages` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `conversation_id` INT UNSIGNED NOT NULL,
                `sender`          VARCHAR(12) NOT NULL DEFAULT 'visitor',
                `operator_id`     INT UNSIGNED NULL DEFAULT NULL,
                `body`            TEXT NOT NULL,
                `seen_operator`   TINYINT(1) NOT NULL DEFAULT 0,
                `created_at`      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                KEY `gcm_conv_idx` (`conversation_id`, `id`),
                CONSTRAINT `fk_gcm_conv` FOREIGN KEY (`conversation_id`)
                    REFERENCES `gc_chat_conversations`(`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $conn): void
    {
        $conn->execute('DROP TABLE IF EXISTS `gc_chat_messages`');
        $conn->execute('DROP TABLE IF EXISTS `gc_chat_conversations`');
    }
};
