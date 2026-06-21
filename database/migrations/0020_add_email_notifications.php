<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $cols = $connection->query("SHOW COLUMNS FROM `users` LIKE 'email_notifications'");
        if (empty($cols)) {
            $connection->execute("
                ALTER TABLE `users`
                ADD COLUMN `email_notifications` TINYINT(1) NOT NULL DEFAULT 1
                    COMMENT '1 = receive login/security emails, 0 = opted out'
                    AFTER `phone`
            ");
        }
    }

    public function down(Connection $connection): void
    {
        $connection->execute("ALTER TABLE `users` DROP COLUMN IF EXISTS `email_notifications`");
    }
};
