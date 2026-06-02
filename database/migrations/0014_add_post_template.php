<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute("
            ALTER TABLE `posts`
                ADD COLUMN `template` VARCHAR(60) NOT NULL DEFAULT 'default' AFTER `type`
        ");
    }

    public function down(Connection $connection): void
    {
        $connection->execute("ALTER TABLE `posts` DROP COLUMN `template`");
    }
};
