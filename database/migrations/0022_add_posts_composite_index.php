<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

/**
 * Adds a composite covering index on (type, status, created_at) to the posts
 * table and drops the now-redundant single-column indexes.
 *
 * INFORMATION_SCHEMA guards keep both up() and down() safe to re-run on
 * MySQL 5.7, which does not support "DROP INDEX IF EXISTS".
 */
return new class implements Migration
{
    /** @return list<string> */
    private function indexNames(Connection $connection): array
    {
        $rows = $connection->query("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME   = 'posts'
        ");
        return array_column($rows, 'INDEX_NAME');
    }

    public function up(Connection $connection): void
    {
        $names = $this->indexNames($connection);

        foreach (['posts_type_idx', 'posts_status_idx'] as $idx) {
            if (in_array($idx, $names, true)) {
                $connection->execute("ALTER TABLE `posts` DROP INDEX `{$idx}`");
            }
        }

        if (!in_array('posts_list_idx', $names, true)) {
            $connection->execute("
                ALTER TABLE `posts`
                    ADD INDEX `posts_list_idx` (`type`, `status`, `created_at`)
            ");
        }
    }

    public function down(Connection $connection): void
    {
        $names = $this->indexNames($connection);

        if (in_array('posts_list_idx', $names, true)) {
            $connection->execute("ALTER TABLE `posts` DROP INDEX `posts_list_idx`");
        }
        if (!in_array('posts_type_idx', $names, true)) {
            $connection->execute("ALTER TABLE `posts` ADD INDEX `posts_type_idx`   (`type`)");
        }
        if (!in_array('posts_status_idx', $names, true)) {
            $connection->execute("ALTER TABLE `posts` ADD INDEX `posts_status_idx` (`status`)");
        }
    }
};
