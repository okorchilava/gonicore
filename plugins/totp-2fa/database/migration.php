<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        $cols = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'totp_secret'");
        if ((int)($cols[0]['cnt'] ?? 0) > 0) {
            return;
        }
        $conn->execute(
            "ALTER TABLE users
             ADD COLUMN totp_secret  VARCHAR(64)  NULL    DEFAULT NULL AFTER password,
             ADD COLUMN totp_enabled TINYINT(1)   NOT NULL DEFAULT 0   AFTER totp_secret"
        );
    }
};
