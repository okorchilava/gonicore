<?php
declare(strict_types=1);
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

return new class implements Migration {
    public function up(Connection $connection): void {
        $connection->execute("
            ALTER TABLE `posts`
                ADD COLUMN IF NOT EXISTS `use_builder` TINYINT(1) NOT NULL DEFAULT 0 AFTER `featured_image`,
                ADD COLUMN IF NOT EXISTS `builder_data` LONGTEXT NULL DEFAULT NULL AFTER `use_builder`
        ");
    }
    public function down(Connection $connection): void {
        $connection->execute("ALTER TABLE `posts` DROP COLUMN IF EXISTS `use_builder`, DROP COLUMN IF EXISTS `builder_data`");
    }
};
