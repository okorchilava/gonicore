<?php

declare(strict_types=1);

namespace GoniCore\Core\Console\Commands;

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;

/**
 * Create a new user from the CLI.
 *
 * Usage:
 *   php bin/gonicore user:create
 */
final class UserCreateCommand
{
    public function __construct(private readonly Connection $connection) {}

    /** @param list<string> $args */
    public function run(array $args): void
    {
        $this->line('');
        $this->line('  Create a new user');
        $this->line('  ' . str_repeat('─', 30));

        $name  = $this->ask('Name');
        $email = $this->ask('Email');
        $role  = $this->ask('Role (admin/editor/viewer)', 'viewer');

        if (!in_array($role, ['admin', 'editor', 'viewer'], true)) {
            $this->line("  ✗ Invalid role '{$role}'. Use admin, editor, or viewer.");
            exit(1);
        }

        // Check email uniqueness
        $qb       = new QueryBuilder($this->connection);
        $existing = $qb->table('users')->where('email', '=', $email)->count();

        if ($existing > 0) {
            $this->line("  ✗ Email '{$email}' is already registered.");
            exit(1);
        }

        while (true) {
            $password = $this->askSecret('Password (min 8 chars)');
            if (strlen($password) >= 8) {
                break;
            }
            $this->line('  ✗ Password too short. Try again.');
        }

        $this->connection->execute(
            'INSERT INTO `users` (`name`, `email`, `password`, `role`) VALUES (?, ?, ?, ?)',
            [$name, $email, password_hash($password, PASSWORD_BCRYPT), $role],
        );

        $this->line('');
        $this->line("  ✓ User created: {$email} (role: {$role})");
        $this->line('');
    }

    private function ask(string $prompt, string $default = ''): string
    {
        $hint = $default !== '' ? " [{$default}]" : '';
        echo "  {$prompt}{$hint}: ";
        $raw = trim((string) fgets(STDIN));
        return $raw !== '' ? $raw : $default;
    }

    private function askSecret(string $prompt): string
    {
        echo "  {$prompt}: ";
        $isUnix = DIRECTORY_SEPARATOR === '/';
        if ($isUnix) { @system('stty -echo'); }
        $value = trim((string) fgets(STDIN));
        if ($isUnix) { @system('stty echo'); echo PHP_EOL; }
        return $value;
    }

    private function line(string $text): void
    {
        echo $text . PHP_EOL;
    }
}
