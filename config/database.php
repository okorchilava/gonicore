<?php

declare(strict_types=1);

/**
 * Database configuration.
 *
 * Values are read from environment variables loaded via Env::load().
 * This file must NOT contain hard-coded credentials — use .env for those.
 *
 * Compatible with Connection::fromConfig().
 *
 * @see \GoniCore\Core\Database\Connection::fromConfig()
 * @see \GoniCore\Core\Config\Env
 */

use GoniCore\Core\Config\Env;

return [
    /*
    |--------------------------------------------------------------------------
    | Default connection
    |--------------------------------------------------------------------------
    | PDO driver to use. Supported: 'mysql', 'pgsql', 'sqlite'.
    */
    'driver' => Env::get('DB_DRIVER', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Host & port
    |--------------------------------------------------------------------------
    */
    'host' => Env::get('DB_HOST', '127.0.0.1'),
    'port' => (int) Env::get('DB_PORT', '3306'),

    /*
    |--------------------------------------------------------------------------
    | Database name
    |--------------------------------------------------------------------------
    | For SQLite this is the absolute path to the .sqlite file.
    */
    'dbname' => Env::require('DB_NAME'),

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    */
    'username' => Env::get('DB_USER', 'root'),
    'password' => Env::get('DB_PASS', ''),

    /*
    |--------------------------------------------------------------------------
    | Character set  (MySQL / MariaDB only)
    |--------------------------------------------------------------------------
    */
    'charset' => Env::get('DB_CHARSET', 'utf8mb4'),

    /*
    |--------------------------------------------------------------------------
    | Extra PDO options
    |--------------------------------------------------------------------------
    | Keyed by PDO::ATTR_* constants. These are merged on top of the secure
    | defaults set inside Connection (ERRMODE_EXCEPTION, FETCH_ASSOC, …).
    */
    'options' => [],
];
