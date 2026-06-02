<?php

declare(strict_types=1);

namespace GoniCore\Core\Console;

/**
 * Minimal CLI dispatcher.
 *
 * Register commands with register(), then call run($argv).
 *
 * Usage:
 *   php bin/gonicore migrate
 *   php bin/gonicore migrate:rollback 2
 */
final class ConsoleKernel
{
    /** @var array<string, callable(list<string>): void> */
    private array $commands = [];

    /**
     * Register a command name → handler mapping.
     *
     * @param callable(list<string>): void $handler
     */
    public function register(string $name, callable $handler): void
    {
        $this->commands[$name] = $handler;
    }

    /**
     * Parse $argv and dispatch to the appropriate command.
     *
     * @param list<string> $argv  PHP's $argv (argv[0] = script path).
     */
    public function run(array $argv): never
    {
        $command = $argv[1] ?? 'help';
        $args    = array_slice($argv, 2);

        if ($command === 'help' || !isset($this->commands[$command])) {
            if ($command !== 'help') {
                $this->writeln("Unknown command: {$command}");
                $this->writeln('');
            }
            $this->printHelp();
            exit($command === 'help' ? 0 : 1);
        }

        ($this->commands[$command])($args);
        exit(0);
    }

    // -------------------------------------------------------------------------

    private function printHelp(): void
    {
        $this->writeln('GoniCore CLI');
        $this->writeln('');
        $this->writeln('Available commands:');

        foreach (array_keys($this->commands) as $name) {
            $this->writeln("  {$name}");
        }
    }

    private function writeln(string $message): void
    {
        echo $message . PHP_EOL;
    }
}
