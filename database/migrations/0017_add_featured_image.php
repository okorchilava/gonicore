<?php
declare(strict_types=1);
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration {
    public function up(Connection $connection): void {
        $connection->execute("
            ALTER TABLE `posts`
                ADD COLUMN IF NOT EXISTS `featured_image` VARCHAR(1000) NULL DEFAULT NULL
                    AFTER `content`
        ");
    }
    public function down(Connection $connection): void {
        $connection->execute("ALTER TABLE `posts` DROP COLUMN IF EXISTS `featured_image`");
    }
};
