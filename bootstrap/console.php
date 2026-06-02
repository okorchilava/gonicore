<?php

declare(strict_types=1);

use GoniCore\Core\Config\Config;
use GoniCore\Core\Config\Env;
use GoniCore\Core\Console\Commands\MigrateCommand;
use GoniCore\Core\Console\Commands\UserCreateCommand;
use GoniCore\Core\Console\ConsoleKernel;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migrator;

// ============================================================
// Environment — silently skip if .env is absent
// (run `php bin/gonicore install` to create it)
// ============================================================

$envFile = __DIR__ . '/../.env';
if (is_file($envFile)) {
    Env::load($envFile);
}

$config = new Config();
$config->loadFile(__DIR__ . '/../config/database.php', 'database');

// ============================================================
// Services
// ============================================================

$connection = Connection::fromConfig($config->require('database'));
$migrator   = new Migrator($connection);

$migrateCmd    = new MigrateCommand($migrator, __DIR__ . '/../database/migrations');
$userCreateCmd = new UserCreateCommand($connection);

// ============================================================
// Kernel — register commands
// ============================================================

$kernel = new ConsoleKernel();
$kernel->register('migrate',          [$migrateCmd,    'migrate']);
$kernel->register('migrate:rollback', [$migrateCmd,    'rollback']);
$kernel->register('user:create',      [$userCreateCmd, 'run']);

return $kernel;
