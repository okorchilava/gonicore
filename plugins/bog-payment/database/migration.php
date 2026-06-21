<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;

return new class {
    public function up(Connection $conn): void
    {
        $conn->execute("
            CREATE TABLE IF NOT EXISTS `bog_transactions` (
                `id`                  INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `bog_order_id`        VARCHAR(100)  NOT NULL UNIQUE,
                `external_order_id`   VARCHAR(100)  NULL DEFAULT NULL COMMENT 'GoniStore order ID or custom reference',
                `amount`              DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                `currency`            VARCHAR(3)    NOT NULL DEFAULT 'GEL',
                `status`              VARCHAR(50)   NOT NULL DEFAULT 'created',
                `payment_method`      VARCHAR(50)   NOT NULL DEFAULT '',
                `payment_code`        VARCHAR(10)   NOT NULL DEFAULT '',
                `payer_identifier`    VARCHAR(255)  NOT NULL DEFAULT '' COMMENT 'masked card / email',
                `description`         VARCHAR(500)  NOT NULL DEFAULT '',
                `action_id`           VARCHAR(100)  NOT NULL DEFAULT '' COMMENT 'last action UUID',
                `raw_callback`        LONGTEXT      NULL DEFAULT NULL,
                `created_at`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at`          TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX `idx_external` (`external_order_id`),
                INDEX `idx_status`   (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    }

    public function down(Connection $conn): void
    {
        $conn->execute('DROP TABLE IF EXISTS `bog_transactions`');
    }
};
