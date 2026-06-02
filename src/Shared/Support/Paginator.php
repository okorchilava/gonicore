<?php

declare(strict_types=1);

namespace GoniCore\Shared\Support;

use GoniCore\Core\Database\QueryBuilder;

/**
 * Wraps a QueryBuilder in a standard paginated response envelope.
 *
 * Response shape:
 *   {
 *     "data": [...],
 *     "meta": {
 *       "total": 100,
 *       "per_page": 15,
 *       "current_page": 2,
 *       "last_page": 7,
 *       "from": 16,
 *       "to": 30
 *     }
 *   }
 */
final class Paginator
{
    private const MIN_PER_PAGE = 1;
    private const MAX_PER_PAGE = 100;

    /**
     * Execute count + data queries and return the paginated envelope.
     *
     * The QueryBuilder should already have WHERE clauses applied;
     * Paginator adds LIMIT/OFFSET on top.
     *
     * @return array{data: list<array<string, mixed>>, meta: array<string, mixed>}
     */
    public static function paginate(
        QueryBuilder $qb,
        int $page    = 1,
        int $perPage = 15,
    ): array {
        $page    = max(1, $page);
        $perPage = min(self::MAX_PER_PAGE, max(self::MIN_PER_PAGE, $perPage));

        $total    = $qb->count();
        $lastPage = $total > 0 ? (int) ceil($total / $perPage) : 1;
        $from     = $total > 0 ? ($page - 1) * $perPage + 1 : null;
        $to       = $total > 0 ? min($page * $perPage, $total) : null;

        $items = $qb
            ->limit($perPage)
            ->offset(($page - 1) * $perPage)
            ->get();

        return [
            'data' => $items,
            'meta' => [
                'total'        => $total,
                'per_page'     => $perPage,
                'current_page' => $page,
                'last_page'    => $lastPage,
                'from'         => $from,
                'to'           => $to,
            ],
        ];
    }
}
