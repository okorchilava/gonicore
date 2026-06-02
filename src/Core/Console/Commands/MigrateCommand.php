<?php

declare(strict_types=1);

namespace GoniCore\Core\Console\Commands;

use GoniCore\Core\Database\Migrator;

final class MigrateCommand
{
    public function __construct(
        private readonly Migrator $migrator,
        private readonly string   $migrationsDirectory,
    ) {}

    /**
     * Run all pending migrations.
     *
     * @param list<string> $args  (unused)
     */
    public function migrate(array $args): void
    {
        echo '[Migrator] Checking for pending migrations...' . PHP_EOL;

        $before = $this->migrator->ran();
        $this->migrator->migrate($this->migrationsDirectory);
        $after  = $this->migrator->ran();

        $new = array_diff($after, $before);

        if (empty($new)) {
            echo '[Migrator] Nothing to migrate.' . PHP_EOL;
            return;
        }

        foreach ($new as $filename) {
            echo "[Migrator] Migrated: {$filename}" . PHP_EOL;
        }

        echo '[Migrator] Done.' . PHP_EOL;
    }

    /**
     * Roll back the last N migrations (default: 1).
     *
     * @param list<string> $args  Optional: first element is the step count.
     */
    public function rollback(array $args): void
    {
        $steps  = isset($args[0]) ? max(1, (int) $args[0]) : 1;
        $before = $this->migrator->ran();

        echo "[Migrator] Rolling back {$steps} migration(s)..." . PHP_EOL;
        $this->migrator->rollback($this->migrationsDirectory, $steps);

        $after   = $this->migrator->ran();
        $removed = array_diff($before, $after);

        foreach ($removed as $filename) {
            echo "[Migrator] Rolled back: {$filename}" . PHP_EOL;
        }

        echo '[Migrator] Done.' . PHP_EOL;
    }
}
