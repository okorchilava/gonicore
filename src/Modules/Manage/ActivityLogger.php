<?php

declare(strict_types=1);

namespace GoniCore\Modules\Manage;

use GoniCore\Core\Database\QueryBuilder;

final class ActivityLogger
{
    private const TABLE = 'activity_log';

    public function __construct(private readonly QueryBuilder $qb) {}

    public function log(
        string  $action,
        ?int    $userId     = null,
        ?string $entityType = null,
        ?int    $entityId   = null,
        array   $meta       = [],
    ): void {
        $this->qb->table(self::TABLE)->insert([
            'user_id'     => $userId,
            'action'      => $action,
            'entity_type' => $entityType,
            'entity_id'   => $entityId,
            'meta'        => !empty($meta) ? json_encode($meta) : null,
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function recent(int $limit = 20): array
    {
        $rows = $this->qb->table(self::TABLE)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();

        if (empty($rows)) return [];

        // Collect unique user IDs and fetch names in one query
        $userIds = array_values(array_unique(array_filter(array_column($rows, 'user_id'))));
        $userMap = [];

        if (!empty($userIds)) {
            foreach ($userIds as $uid) {
                $u = $this->qb->table('users')
                    ->select('id', 'name', 'email')
                    ->where('id', '=', $uid)
                    ->first();
                if ($u) $userMap[(int) $uid] = $u;
            }
        }

        foreach ($rows as &$row) {
            $uid = isset($row['user_id']) ? (int) $row['user_id'] : null;
            $row['user_name']  = $uid && isset($userMap[$uid]) ? $userMap[$uid]['name']  : null;
            $row['user_email'] = $uid && isset($userMap[$uid]) ? $userMap[$uid]['email'] : null;
        }
        unset($row);

        return $rows;
    }
}
