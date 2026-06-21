<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gcsms_settings` (
                `key`   VARCHAR(100) NOT NULL PRIMARY KEY,
                `value` LONGTEXT     NOT NULL DEFAULT ''
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gcsms_logs` (
                `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `type`       ENUM('single','bulk','otp') NOT NULL DEFAULT 'single',
                `phone`      VARCHAR(100) NOT NULL DEFAULT '',
                `message`    TEXT         NOT NULL,
                `message_id` VARCHAR(50)  NULL DEFAULT NULL,
                `status`     VARCHAR(30)  NOT NULL DEFAULT 'sent',
                `response`   TEXT         NULL DEFAULT NULL,
                `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gcsms_logs_status_idx` (`status`),
                INDEX `gcsms_logs_phone_idx`  (`phone`(50)),
                INDEX `gcsms_logs_msgid_idx`  (`message_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        // Inbound short-number replies received via the inbound webhook.
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gcsms_inbound` (
                `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `from_number` VARCHAR(100) NOT NULL DEFAULT '',
                `to_number`   VARCHAR(100) NOT NULL DEFAULT '',
                `message`     TEXT         NOT NULL,
                `no_sms`      TINYINT(1)   NOT NULL DEFAULT 0,
                `sent_at`     DATETIME     NULL DEFAULT NULL,
                `is_read`     TINYINT(1)   NOT NULL DEFAULT 0,
                `created_at`  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gcsms_inbound_read_idx`    (`is_read`),
                INDEX `gcsms_inbound_created_idx` (`created_at`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo = $conn->pdo();
        $seed = [
            'api_key'       => '',
            'sender_name'   => '',
            'webhook_token' => '', // pasted by the admin from the gosms.ge panel
        ];
        foreach ($seed as $k => $v) {
            $conn->execute(
                "INSERT IGNORE INTO `gcsms_settings` (`key`, `value`) VALUES ("
                . $pdo->quote($k) . "," . $pdo->quote($v) . ")"
            );
        }
    }

    public function down(Connection $conn): void
    {
        $conn->execute("DROP TABLE IF EXISTS `gcsms_inbound`");
        $conn->execute("DROP TABLE IF EXISTS `gcsms_logs`");
        $conn->execute("DROP TABLE IF EXISTS `gcsms_settings`");
    }
};
