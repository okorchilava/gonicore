<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $rows = [
            ['mail_driver',          'php'],
            ['mail_from_address',    ''],
            ['mail_from_name',       'GoniCore'],
            ['admin_email',          ''],
            ['mail_smtp_host',       ''],
            ['mail_smtp_port',       '587'],
            ['mail_smtp_user',       ''],
            ['mail_smtp_pass',       ''],
            ['mail_smtp_encryption', 'tls'],
            // Which events trigger admin email
            ['notify_post_new',      '1'],
            ['notify_user_register', '1'],
            ['notify_comment_new',   '1'],
        ];

        foreach ($rows as [$key, $value]) {
            $connection->execute(
                "INSERT IGNORE INTO `settings` (`key`, `value`, `autoload`) VALUES (?, ?, 1)",
                [$key, $value]
            );
        }
    }

    public function down(Connection $connection): void
    {
        $keys = [
            'mail_driver','mail_from_address','mail_from_name','admin_email',
            'mail_smtp_host','mail_smtp_port','mail_smtp_user','mail_smtp_pass','mail_smtp_encryption',
            'notify_post_new','notify_user_register','notify_comment_new',
        ];
        $placeholders = implode(',', array_fill(0, count($keys), '?'));
        $connection->execute("DELETE FROM `settings` WHERE `key` IN ({$placeholders})", $keys);
    }
};
