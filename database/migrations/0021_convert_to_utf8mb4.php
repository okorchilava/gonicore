<?php

declare(strict_types=1);

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migration;

/**
 * Ensures the entire database uses utf8mb4 / utf8mb4_unicode_ci.
 *
 * Why this is needed
 * ──────────────────
 * Earlier installs or manually-created databases may have been provisioned
 * with latin1 or plain utf8 (MySQL's crippled 3-byte variant), which silently
 * truncates emoji and many non-BMP characters. ALTER TABLE … CONVERT TO
 * rewrites every column so the collation is authoritative at the column level,
 * not just the table default.
 *
 * MySQL 5.7 / MariaDB 10.x compatibility
 * ────────────────────────────────────────
 * • No ADD COLUMN IF NOT EXISTS (not supported before MySQL 8.0 / MariaDB 10.0).
 * • We skip tables already at utf8mb4_unicode_ci via INFORMATION_SCHEMA to
 *   avoid a no-op ALTER that would still briefly lock the table.
 * • CONVERT TO CHARACTER SET is available in MySQL 5.5+ and is safe to run on
 *   a live table (it does a fast metadata rewrite for most column types).
 */
return new class implements Migration
{
    public function up(Connection $connection): void
    {
        // 1. Set the database-level default (affects new tables from here on).
        $connection->execute(
            "ALTER DATABASE CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
        );

        // 2. Find every table in this schema that is NOT already utf8mb4_unicode_ci.
        $tables = $connection->query("
            SELECT TABLE_NAME
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA  = DATABASE()
              AND TABLE_TYPE    = 'BASE TABLE'
              AND (
                  TABLE_COLLATION != 'utf8mb4_unicode_ci'
               OR TABLE_COLLATION IS NULL
              )
        ");

        foreach ($tables as $row) {
            $table = $row['TABLE_NAME'];
            // CONVERT TO rewrites every character column and updates the
            // table default in one statement — safe on MySQL 5.7+.
            $connection->execute("
                ALTER TABLE `{$table}`
                    CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci
            ");
        }
    }

    public function down(Connection $connection): void
    {
        // Reverting a charset conversion is destructive and almost never
        // desirable; this down() is intentionally a no-op.
    }
};
