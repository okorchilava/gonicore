<?php

declare(strict_types=1);

namespace GoniCore\Core\Database;

use RuntimeException;

/**
 * Runs and tracks database migrations.
 *
 * Migrations are PHP files in a directory that each return an anonymous class
 * implementing the Migration interface.  They are executed in filename order
 * (alphabetical / numeric prefix recommended: 0001_create_users_table.php).
 *
 * A `_migrations` table is created automatically to record which files
 * have already been run.
 */
final class Migrator
{
    private const MIGRATIONS_TABLE = '_migrations';

    public function __construct(private readonly Connection $connection) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Run all pending migrations in $directory.
     */
    public function migrate(string $directory): void
    {
        $this->ensureMigrationsTable();

        foreach ($this->pending($directory) as $filename => $migration) {
            $migration->up($this->connection);
            $this->record($filename);
        }
    }

    /**
     * Roll back the last $steps migrations (default: 1).
     */
    public function rollback(string $directory, int $steps = 1): void
    {
        $this->ensureMigrationsTable();

        $ran = $this->connection->query(
            'SELECT migration FROM `' . self::MIGRATIONS_TABLE . '` ORDER BY id DESC LIMIT ?',
            [$steps],
        );

        foreach ($ran as $row) {
            $filename = (string) $row['migration'];
            $file     = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $filename;

            if (!is_file($file)) {
                throw new RuntimeException("Migration file not found for rollback: {$file}");
            }

            $migration = require $file;
            $this->assertMigration($migration, $file);

            $migration->down($this->connection);

            $this->connection->execute(
                'DELETE FROM `' . self::MIGRATIONS_TABLE . '` WHERE migration = ?',
                [$filename],
            );
        }
    }

    /**
     * Return the list of migration filenames that have already been applied.
     *
     * @return list<string>
     */
    public function ran(): array
    {
        $rows = $this->connection->query(
            'SELECT migration FROM `' . self::MIGRATIONS_TABLE . '` ORDER BY id ASC'
        );

        return array_column($rows, 'migration');
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    /**
     * @return array<string, Migration>  filename → Migration instance, sorted by filename.
     */
    private function pending(string $directory): array
    {
        if (!is_dir($directory)) {
            throw new RuntimeException("Migrations directory not found: {$directory}");
        }

        $files = glob(rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . '*.php') ?: [];
        sort($files);

        $ran     = $this->ran();
        $pending = [];

        foreach ($files as $file) {
            $basename = basename($file);

            if (in_array($basename, $ran, true)) {
                continue;
            }

            $migration = require $file;
            $this->assertMigration($migration, $file);

            $pending[$basename] = $migration;
        }

        return $pending;
    }

    private function record(string $filename): void
    {
        $this->connection->execute(
            'INSERT INTO `' . self::MIGRATIONS_TABLE . '` (migration) VALUES (?)',
            [$filename],
        );
    }

    private function ensureMigrationsTable(): void
    {
        $this->connection->execute(
            'CREATE TABLE IF NOT EXISTS `' . self::MIGRATIONS_TABLE . '` (
                `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `migration` VARCHAR(255) NOT NULL,
                `ran_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `migration_unique` (`migration`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4'
        );
    }

    private function assertMigration(mixed $migration, string $file): void
    {
        if (!$migration instanceof Migration) {
            throw new RuntimeException(
                "Migration file must return an instance of Migration: {$file}"
            );
        }
    }
}
