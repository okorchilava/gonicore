<?php

declare(strict_types=1);

namespace GoniCore\Core\Database;

use PDO;
use PDOException;
use PDOStatement;
use Throwable;

/**
 * Lazy PDO wrapper.
 *
 * The underlying PDO connection is not opened until the first
 * actual database operation — avoiding overhead when the current
 * request never touches the database.
 *
 * Usage via DI container (preferred):
 *   $db = Connection::fromConfig($config['database']);
 *
 * Direct construction:
 *   $db = new Connection('mysql:host=127.0.0.1;dbname=goni;charset=utf8mb4', 'user', 'secret');
 */
final class Connection
{
    private ?PDO $pdo = null;

    /** PDO options applied on top of the secure defaults. */
    private readonly array $resolvedOptions;

    public function __construct(
        private readonly string $dsn,
        private readonly string $username,
        private readonly string $password,
        array $options = [],
    ) {
        $this->resolvedOptions = array_replace(
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ],
            $options,
        );
    }

    // -------------------------------------------------------------------------
    // Factory
    // -------------------------------------------------------------------------

    /**
     * Build a Connection from a flat config array.
     *
     * Expected keys:
     *   driver   (default: mysql)
     *   host     (default: 127.0.0.1)
     *   port     (default: 3306)
     *   dbname
     *   charset  (default: utf8mb4)
     *   username
     *   password
     *   options  (PDO options array, optional)
     */
    public static function fromConfig(array $config): self
    {
        $driver  = $config['driver']  ?? 'mysql';
        $host    = $config['host']    ?? '127.0.0.1';
        $port    = $config['port']    ?? 3306;
        $dbname  = $config['dbname']  ?? '';
        $charset = $config['charset'] ?? 'utf8mb4';

        $dsn = match ($driver) {
            'mysql'  => "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}",
            'pgsql'  => "pgsql:host={$host};port={$port};dbname={$dbname}",
            'sqlite' => "sqlite:{$dbname}",
            default  => throw new DatabaseException("Unsupported PDO driver: {$driver}"),
        };

        return new self(
            $dsn,
            (string) ($config['username'] ?? ''),
            (string) ($config['password'] ?? ''),
            (array)  ($config['options']  ?? []),
        );
    }

    // -------------------------------------------------------------------------
    // Low-level PDO access
    // -------------------------------------------------------------------------

    /**
     * Return the raw PDO instance, opening the connection on first call.
     */
    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $this->open();
        }

        return $this->pdo;
    }

    // -------------------------------------------------------------------------
    // Query API
    // -------------------------------------------------------------------------

    /**
     * Prepare a statement. Throws DatabaseException on failure.
     */
    public function prepare(string $sql): PDOStatement
    {
        try {
            $stmt = $this->pdo()->prepare($sql);

            // PDO in emulation mode may return false instead of throwing.
            if ($stmt === false) {
                throw new DatabaseException("Failed to prepare statement: {$sql}");
            }

            return $stmt;
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Prepare and execute a statement, returning the PDOStatement.
     *
     * @param array<int|string, mixed> $bindings
     */
    public function execute(string $sql, array $bindings = []): PDOStatement
    {
        $stmt = $this->prepare($sql);

        try {
            $stmt->execute($bindings);
        } catch (PDOException $e) {
            throw new DatabaseException($e->getMessage(), (int) $e->getCode(), $e);
        }

        return $stmt;
    }

    /**
     * Execute and return all rows as an associative array.
     *
     * @param  array<int|string, mixed>   $bindings
     * @return array<int, array<string, mixed>>
     */
    public function query(string $sql, array $bindings = []): array
    {
        return $this->execute($sql, $bindings)->fetchAll();
    }

    /**
     * Execute and return a single row, or null if no match.
     *
     * @param  array<int|string, mixed>  $bindings
     * @return array<string, mixed>|null
     */
    public function queryOne(string $sql, array $bindings = []): ?array
    {
        $row = $this->execute($sql, $bindings)->fetch();

        return $row !== false ? $row : null;
    }

    /**
     * Execute a scalar query (e.g. COUNT) and return the first column value.
     *
     * @param array<int|string, mixed> $bindings
     */
    public function scalar(string $sql, array $bindings = []): mixed
    {
        $row = $this->execute($sql, $bindings)->fetch(PDO::FETCH_NUM);

        return $row !== false ? $row[0] : null;
    }

    /**
     * Return the last auto-increment ID produced by an INSERT.
     */
    public function lastInsertId(): string
    {
        return $this->pdo()->lastInsertId();
    }

    // -------------------------------------------------------------------------
    // Transactions
    // -------------------------------------------------------------------------

    /**
     * Run $callback inside a transaction.
     *
     * The return value of $callback is forwarded to the caller.
     * Any Throwable automatically triggers a rollback and re-throws.
     *
     * @template T
     * @param  callable(self): T $callback
     * @return T
     */
    public function transact(callable $callback): mixed
    {
        $this->pdo()->beginTransaction();

        try {
            $result = $callback($this);
            $this->pdo()->commit();

            return $result;
        } catch (Throwable $e) {
            $this->pdo()->rollBack();
            throw $e;
        }
    }

    // -------------------------------------------------------------------------
    // Internal
    // -------------------------------------------------------------------------

    private function open(): void
    {
        try {
            $this->pdo = new PDO(
                $this->dsn,
                $this->username,
                $this->password,
                $this->resolvedOptions,
            );
        } catch (PDOException $e) {
            throw new DatabaseException(
                'Database connection failed: ' . $e->getMessage(),
                (int) $e->getCode(),
                $e,
            );
        }
    }
}
