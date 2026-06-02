<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration
{
    public function up(Connection $connection): void
    {
        $connection->execute(
            "INSERT IGNORE INTO `settings` (`key`, `value`, `autoload`) VALUES (?, ?, ?)",
            ['posts_page_id', '', 1]
        );
    }

    public function down(Connection $connection): void
    {
        $connection->execute("DELETE FROM `settings` WHERE `key` = 'posts_page_id'");
    }
};
