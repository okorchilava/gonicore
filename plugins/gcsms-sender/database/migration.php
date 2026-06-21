<?php
declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gcsmssender_settings` (
                `key`   VARCHAR(100) NOT NULL PRIMARY KEY,
                `value` LONGTEXT     NOT NULL DEFAULT ''
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $conn->execute("
            CREATE TABLE IF NOT EXISTS `gcsmssender_logs` (
                `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `destination`     VARCHAR(20)  NOT NULL DEFAULT '',
                `content`         TEXT         NOT NULL,
                `message_id`      VARCHAR(50)  NULL DEFAULT NULL,
                `sms_no`          TINYINT      NOT NULL DEFAULT 1,
                `priority`        TINYINT      NOT NULL DEFAULT 0,
                `qnt`             TINYINT      NULL DEFAULT NULL,
                `http_code`       SMALLINT     NOT NULL DEFAULT 0,
                `delivery_status` TINYINT      NULL DEFAULT NULL COMMENT '0=pending,1=delivered,2=undelivered',
                `response`        TEXT         NULL DEFAULT NULL,
                `created_at`      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `gcsmssender_logs_dest_idx` (`destination`),
                INDEX `gcsmssender_logs_mid_idx`  (`message_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        $pdo = $conn->pdo();
        foreach (['api_key' => '', 'smsno' => '2'] as $k => $v) {
            $conn->execute(
                "INSERT IGNORE INTO `gcsmssender_settings` (`key`, `value`) VALUES ("
                . $pdo->quote($k) . ', ' . $pdo->quote($v) . ')'
            );
        }
    }

    public function down(Connection $conn): void
    {
        $conn->execute("DROP TABLE IF EXISTS `gcsmssender_logs`");
        $conn->execute("DROP TABLE IF EXISTS `gcsmssender_settings`");
    }
};
