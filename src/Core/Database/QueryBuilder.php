<?php

declare(strict_types=1);

namespace GoniCore\Core\Database;

use InvalidArgumentException;

/**
 * Fluent, immutable SQL query builder.
 *
 * Every mutating method returns a cloned instance so builders
 * can be safely forked and reused without side effects.
 *
 * Usage:
 *   $qb = new QueryBuilder($connection);
 *
 *   $posts = $qb->table('posts')
 *               ->select('id', 'title', 'created_at')
 *               ->where('status', '=', 'published')
 *               ->orderBy('created_at', 'DESC')
 *               ->limit(10)
 *               ->get();
 *
 *   $id = $qb->table('posts')->insert(['title' => 'Hello', 'status' => 'draft']);
 */
final class QueryBuilder
{
    private string $table = '';

    /** @var list<string> */
    private array $selects = ['*'];

    /**
     * @var list<array{
     *   clause:   string,
     *   bindings: list<mixed>,
     *   boolean:  string
     * }>
     */
    private array $wheres = [];

    /** @var list<array{column: string, direction: string}> */
    private array $orderBys = [];

    private ?int $limit  = null;
    private ?int $offset = null;

    private const ALLOWED_OPERATORS = ['=', '!=', '<>', '<', '>', '<=', '>=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN'];
    private const ALLOWED_DIRECTIONS = ['ASC', 'DESC'];

    public function __construct(private readonly Connection $connection) {}

    // -------------------------------------------------------------------------
    // Table & column selection
    // -------------------------------------------------------------------------

    public function table(string $table): static
    {
        $clone        = clone $this;
        $clone->table = $table;
        return $clone;
    }

    public function select(string ...$columns): static
    {
        $clone          = clone $this;
        $clone->selects = $columns ?: ['*'];
        return $clone;
    }

    // -------------------------------------------------------------------------
    // WHERE clauses
    // -------------------------------------------------------------------------

    /**
     * @throws InvalidArgumentException  for unknown operators.
     */
    public function where(string $column, string $operator, mixed $value): static
    {
        return $this->addWhere($column, $operator, $value, 'AND');
    }

    public function orWhere(string $column, string $operator, mixed $value): static
    {
        return $this->addWhere($column, $operator, $value, 'OR');
    }

    // -------------------------------------------------------------------------
    // Ordering, limit, offset
    // -------------------------------------------------------------------------

    public function orderBy(string $column, string $direction = 'ASC'): static
    {
        $direction = strtoupper($direction);

        if (!in_array($direction, self::ALLOWED_DIRECTIONS, true)) {
            throw new InvalidArgumentException(
                "Invalid ORDER BY direction \"{$direction}\". Use ASC or DESC."
            );
        }

        $clone             = clone $this;
        $clone->orderBys[] = ['column' => $column, 'direction' => $direction];
        return $clone;
    }

    public function limit(int $limit): static
    {
        $clone        = clone $this;
        $clone->limit = $limit;
        return $clone;
    }

    public function offset(int $offset): static
    {
        $clone         = clone $this;
        $clone->offset = $offset;
        return $clone;
    }

    // -------------------------------------------------------------------------
    // Read operations
    // -------------------------------------------------------------------------

    /**
     * Execute a SELECT and return all matching rows.
     *
     * @return list<array<string, mixed>>
     */
    public function get(): array
    {
        [$sql, $bindings] = $this->compileSelect();
        return $this->connection->query($sql, $bindings);
    }

    /**
     * Execute a SELECT LIMIT 1 and return the first row, or null.
     *
     * @return array<string, mixed>|null
     */
    public function first(): ?array
    {
        return $this->limit(1)->get()[0] ?? null;
    }

    /**
     * Return the total number of rows matching the current WHERE clauses.
     */
    public function count(): int
    {
        $clone          = clone $this;
        $clone->selects = ['COUNT(*) AS aggregate'];
        $clone->orderBys = [];
        $clone->limit   = null;
        $clone->offset  = null;

        [$sql, $bindings] = $clone->compileSelect();
        return (int) $this->connection->scalar($sql, $bindings);
    }

    // -------------------------------------------------------------------------
    // Write operations
    // -------------------------------------------------------------------------

    /**
     * Insert a new row and return the last insert ID.
     *
     * @param  array<string, mixed> $data  Column → value map.
     */
    public function insert(array $data): string
    {
        $this->assertTable();

        if (empty($data)) {
            throw new InvalidArgumentException('insert() requires at least one column value.');
        }

        $columns      = implode(', ', array_map($this->quoteIdentifier(...), array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        $sql = "INSERT INTO {$this->quoteIdentifier($this->table)} ({$columns}) VALUES ({$placeholders})";

        $this->connection->execute($sql, array_values($data));
        return $this->connection->lastInsertId();
    }

    /**
     * Update rows matching the current WHERE clauses.
     *
     * @param  array<string, mixed> $data  Column → value map.
     * @return int  Number of affected rows.
     */
    public function update(array $data): int
    {
        $this->assertTable();

        if (empty($data)) {
            throw new InvalidArgumentException('update() requires at least one column value.');
        }

        $sets = implode(', ', array_map(
            fn(string $col): string => $this->quoteIdentifier($col) . ' = ?',
            array_keys($data),
        ));

        [$whereClause, $whereBindings] = $this->compileWhere();

        $sql      = "UPDATE {$this->quoteIdentifier($this->table)} SET {$sets}";
        $bindings = array_values($data);

        if ($whereClause !== '') {
            $sql      .= " WHERE {$whereClause}";
            $bindings  = array_merge($bindings, $whereBindings);
        }

        return $this->connection->execute($sql, $bindings)->rowCount();
    }

    /**
     * Delete rows matching the current WHERE clauses.
     *
     * @return int  Number of affected rows.
     */
    public function delete(): int
    {
        $this->assertTable();

        [$whereClause, $whereBindings] = $this->compileWhere();

        $sql = "DELETE FROM {$this->quoteIdentifier($this->table)}";

        if ($whereClause !== '') {
            $sql .= " WHERE {$whereClause}";
        }

        return $this->connection->execute($sql, $whereBindings)->rowCount();
    }

    // -------------------------------------------------------------------------
    // SQL compilation
    // -------------------------------------------------------------------------

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function compileSelect(): array
    {
        $this->assertTable();

        $cols = implode(', ', $this->selects);
        $sql  = "SELECT {$cols} FROM {$this->quoteIdentifier($this->table)}";

        [$whereClause, $bindings] = $this->compileWhere();

        if ($whereClause !== '') {
            $sql .= " WHERE {$whereClause}";
        }

        if (!empty($this->orderBys)) {
            $parts = array_map(
                fn(array $o): string => $this->quoteIdentifier($o['column']) . ' ' . $o['direction'],
                $this->orderBys,
            );
            $sql .= ' ORDER BY ' . implode(', ', $parts);
        }

        if ($this->limit !== null) {
            $sql .= " LIMIT {$this->limit}";
        }

        if ($this->offset !== null) {
            $sql .= " OFFSET {$this->offset}";
        }

        return [$sql, $bindings];
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function compileWhere(): array
    {
        if (empty($this->wheres)) {
            return ['', []];
        }

        $parts    = [];
        $bindings = [];

        foreach ($this->wheres as $i => $where) {
            $prefix    = $i > 0 ? $where['boolean'] . ' ' : '';
            $parts[]   = $prefix . $where['clause'];
            $bindings  = array_merge($bindings, $where['bindings']);
        }

        return [implode(' ', $parts), $bindings];
    }

    private function addWhere(string $column, string $operator, mixed $value, string $boolean): static
    {
        $operator = strtoupper($operator);

        if (!in_array($operator, self::ALLOWED_OPERATORS, true)) {
            throw new InvalidArgumentException(
                "Invalid WHERE operator \"{$operator}\"."
            );
        }

        $clone          = clone $this;
        $clone->wheres[] = [
            'clause'   => $this->quoteIdentifier($column) . " {$operator} ?",
            'bindings' => [$value],
            'boolean'  => $boolean,
        ];

        return $clone;
    }

    private function quoteIdentifier(string $name): string
    {
        // Backtick quoting (MySQL / MariaDB / SQLite compatible).
        // Strip existing backticks to prevent double-quoting.
        return '`' . str_replace('`', '', $name) . '`';
    }

    private function assertTable(): void
    {
        if ($this->table === '') {
            throw new \LogicException('No table set. Call table() before executing a query.');
        }
    }
}
