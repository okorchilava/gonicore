<?php

declare(strict_types=1);

namespace GoniCore\Core\Database;

/**
 * Contract for all database migrations.
 *
 * Each migration file should return an anonymous class implementing this interface:
 *
 *   return new class implements Migration {
 *       public function up(Connection $connection): void   { ... }
 *       public function down(Connection $connection): void { ... }
 *   };
 */
interface Migration
{
    /** Apply the migration (forward direction). */
    public function up(Connection $connection): void;

    /** Reverse the migration. */
    public function down(Connection $connection): void;
}
