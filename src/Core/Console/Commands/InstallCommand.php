<?php

declare(strict_types=1);

namespace GoniCore\Core\Console\Commands;

use GoniCore\Core\Config\Env;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\Migrator;
use Throwable;

/**
 * Interactive GoniCore installer.
 *
 * Intentionally self-contained — does NOT rely on bootstrap/app.php
 * so it can run before .env exists.
 *
 * Steps:
 *   [1/6] System requirements check
 *   [2/6] .env wizard (interactive)
 *   [3/6] Storage directory setup
 *   [4/6] Database connection test
 *   [5/6] Migrations
 *   [6/6] Admin user creation
 */
final class InstallCommand
{
    private const MIN_PHP    = '8.2.0';
    private const REQUIRED_EXTENSIONS = [
        'pdo', 'pdo_mysql', 'mbstring', 'fileinfo', 'json', 'openssl',
    ];

    public function __construct(private readonly string $projectRoot) {}

    // -------------------------------------------------------------------------
    // Entry point
    // -------------------------------------------------------------------------

    /** @param list<string> $args */
    public function run(array $args): void
    {
        $this->banner();

        $this->section(1, 6, 'Checking system requirements');
        $this->checkRequirements();

        $this->section(2, 6, 'Environment configuration');
        $this->configureEnv();

        $this->section(3, 6, 'Storage directories');
        $this->setupStorage();

        $this->section(4, 6, 'Database connection');
        $connection = $this->testConnection();

        $this->section(5, 6, 'Running migrations');
        $this->runMigrations($connection);

        $this->section(6, 6, 'Admin user');
        $this->createAdmin($connection);

        $this->done();
    }

    // -------------------------------------------------------------------------
    // Steps
    // -------------------------------------------------------------------------

    private function checkRequirements(): void
    {
        // PHP version
        if (version_compare(PHP_VERSION, self::MIN_PHP, '<')) {
            $this->fail('PHP ' . self::MIN_PHP . '+ required. Current: ' . PHP_VERSION);
        }
        $this->ok('PHP ' . PHP_VERSION);

        // Extensions
        foreach (self::REQUIRED_EXTENSIONS as $ext) {
            if (!extension_loaded($ext)) {
                $this->fail("Required PHP extension not loaded: {$ext}\n"
                    . "  → Enable it in php.ini and restart Apache.");
            }
            $this->ok("ext/{$ext}");
        }

        // Composer vendor directory
        if (!is_dir($this->path('vendor'))) {
            $this->fail(
                "Composer dependencies not installed.\n"
                . "  → Run: composer install"
            );
        }
        $this->ok('Composer dependencies (vendor/)');
    }

    private function configureEnv(): void
    {
        $envFile    = $this->path('.env');
        $envExample = $this->path('.env.example');

        if (is_file($envFile)) {
            $this->ok('.env already exists — using existing values');
            Env::load($envFile);
            return;
        }

        if (!is_file($envExample)) {
            $this->fail('.env.example not found. Repository may be incomplete.');
        }

        $this->line('  No .env file found. Let\'s create one.');
        $this->line('');

        $base      = (string) file_get_contents($envExample);
        $appUrl    = $this->ask('Application URL',  'http://localhost');
        $dbDriver  = $this->ask('DB driver (mysql/pgsql/sqlite)', 'mysql');
        $dbHost    = $this->ask('DB host',           '127.0.0.1');
        $dbPort    = $this->ask('DB port',           '3306');
        $dbName    = $this->ask('DB name',           'gonicore');
        $dbUser    = $this->ask('DB username',       'root');
        $dbPass    = $this->askSecret('DB password');

        // Auto-generate a strong JWT secret
        $jwtSecret = bin2hex(random_bytes(32));

        $base = $this->envSet($base, 'APP_ENV',    'production');
        $base = $this->envSet($base, 'APP_DEBUG',  'false');
        $base = $this->envSet($base, 'APP_URL',    $appUrl);
        $base = $this->envSet($base, 'DB_DRIVER',  $dbDriver);
        $base = $this->envSet($base, 'DB_HOST',    $dbHost);
        $base = $this->envSet($base, 'DB_PORT',    $dbPort);
        $base = $this->envSet($base, 'DB_NAME',    $dbName);
        $base = $this->envSet($base, 'DB_USER',    $dbUser);
        $base = $this->envSet($base, 'DB_PASS',    $dbPass);
        $base = $this->envSet($base, 'JWT_SECRET', $jwtSecret);

        file_put_contents($envFile, $base);
        Env::load($envFile);

        $this->ok('.env created');
        $this->ok('JWT_SECRET auto-generated (64-char hex)');
    }

    private function setupStorage(): void
    {
        $dirs = ['storage/media', 'storage/logs'];

        foreach ($dirs as $rel) {
            $dir = $this->path($rel);

            if (!is_dir($dir)) {
                if (!mkdir($dir, 0755, true)) {
                    $this->fail("Cannot create directory: {$dir}");
                }
                $this->ok("Created {$rel}/");
            } else {
                $this->ok("{$rel}/ already exists");
            }

            if (!is_writable($dir)) {
                $this->fail("Directory is not writable: {$dir}\n  → Fix permissions: chmod 755 {$dir}");
            }
        }
    }

    private function testConnection(): Connection
    {
        try {
            $cfg = [
                'driver'   => Env::get('DB_DRIVER',  'mysql'),
                'host'     => Env::get('DB_HOST',    '127.0.0.1'),
                'port'     => (int) Env::get('DB_PORT', '3306'),
                'dbname'   => Env::require('DB_NAME'),
                'username' => Env::get('DB_USER',    'root'),
                'password' => Env::get('DB_PASS',    ''),
                'charset'  => Env::get('DB_CHARSET', 'utf8mb4'),
            ];

            $conn = Connection::fromConfig($cfg);
            $conn->pdo(); // trigger lazy connection

            $this->ok('Connected → ' . $cfg['driver'] . '://' . $cfg['host'] . '/' . $cfg['dbname']);
            return $conn;

        } catch (Throwable $e) {
            $this->fail(
                "Database connection failed:\n  " . $e->getMessage() . "\n"
                . "\n  → Check DB_HOST, DB_USER, DB_PASS and DB_NAME in .env"
            );
        }
    }

    private function runMigrations(Connection $connection): void
    {
        $migrator = new Migrator($connection);
        $dir      = $this->path('database/migrations');

        $before = $migrator->ran();
        $migrator->migrate($dir);
        $after  = $migrator->ran();
        $new    = array_diff($after, $before);

        if (empty($new)) {
            $this->ok('All migrations already applied');
            return;
        }

        foreach ($new as $file) {
            $this->ok("Migrated: {$file}");
        }
    }

    private function createAdmin(Connection $connection): void
    {
        $existing = $connection->queryOne(
            "SELECT id FROM `users` WHERE `role` = 'admin' LIMIT 1"
        );

        if ($existing !== null) {
            $this->ok('Admin already exists — skipping');
            return;
        }

        $this->line('  Create the first admin account:');
        $this->line('');

        $name = $this->ask('  Name');
        $email = $this->ask('  Email');

        while (true) {
            $password = $this->askSecret('  Password (min 8 chars)');
            if (strlen($password) >= 8) {
                break;
            }
            $this->line('  ✗ Password too short. Try again.');
        }

        $connection->execute(
            "INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES (?, ?, ?, 'admin')",
            [$name, $email, password_hash($password, PASSWORD_BCRYPT)],
        );

        $this->ok("Admin created: {$email}");
    }

    // -------------------------------------------------------------------------
    // Output helpers
    // -------------------------------------------------------------------------

    private function banner(): void
    {
        $this->line('');
        $this->line('  ╔══════════════════════════════════════╗');
        $this->line('  ║         GoniCore  Installer          ║');
        $this->line('  ╚══════════════════════════════════════╝');
        $this->line('');
    }

    private function done(): void
    {
        $url = Env::get('APP_URL', 'http://localhost');
        $this->line('');
        $this->line('  ╔══════════════════════════════════════╗');
        $this->line('  ║      Installation complete!  ✓       ║');
        $this->line('  ╚══════════════════════════════════════╝');
        $this->line('');
        $this->line("  API base URL : {$url}/api/v1");
        $this->line('  Health check : ' . $url . '/api/v1/health');
        $this->line('');
        $this->line('  Next steps:');
        $this->line('  1. Point your web server document root to public/');
        $this->line('  2. POST /api/v1/auth/login  to get a JWT token');
        $this->line('');
    }

    private function section(int $n, int $total, string $label): void
    {
        $this->line('');
        $this->line("  [{$n}/{$total}] {$label}...");
        $this->line('  ' . str_repeat('─', 40));
    }

    private function ok(string $msg): void
    {
        $this->line("  ✓  {$msg}");
    }

    private function fail(string $msg): never
    {
        $this->line('');
        $this->line("  ✗  ERROR: {$msg}");
        $this->line('');
        exit(1);
    }

    private function line(string $text): void
    {
        echo $text . PHP_EOL;
    }

    // -------------------------------------------------------------------------
    // Input helpers
    // -------------------------------------------------------------------------

    private function ask(string $prompt, string $default = ''): string
    {
        $hint = $default !== '' ? " [{$default}]" : '';
        echo "  {$prompt}{$hint}: ";
        $raw = trim((string) fgets(STDIN));
        return $raw !== '' ? $raw : $default;
    }

    /**
     * Read a value without echoing it (Unix only; falls back on Windows).
     */
    private function askSecret(string $prompt): string
    {
        echo "  {$prompt}: ";

        $isUnix = DIRECTORY_SEPARATOR === '/';

        if ($isUnix) {
            @system('stty -echo');
        }

        $value = trim((string) fgets(STDIN));

        if ($isUnix) {
            @system('stty echo');
            echo PHP_EOL;
        }

        return $value;
    }

    // -------------------------------------------------------------------------
    // .env helpers
    // -------------------------------------------------------------------------

    /**
     * Replace or append a KEY=value line in an .env string.
     */
    private function envSet(string $content, string $key, string $value): string
    {
        $escaped = str_contains($value, ' ') ? "\"{$value}\"" : $value;
        $line    = "{$key}={$escaped}";

        if (preg_match('/^' . preg_quote($key, '/') . '\s*=/m', $content)) {
            return (string) preg_replace(
                '/^' . preg_quote($key, '/') . '\s*=.*/m',
                $line,
                $content,
            );
        }

        return rtrim($content) . PHP_EOL . $line . PHP_EOL;
    }

    private function path(string $rel): string
    {
        return rtrim($this->projectRoot, DIRECTORY_SEPARATOR)
            . DIRECTORY_SEPARATOR
            . ltrim($rel, DIRECTORY_SEPARATOR);
    }
}
