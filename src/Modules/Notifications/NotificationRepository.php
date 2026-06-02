<?php

declare(strict_types=1);

namespace GoniCore\Modules\Notifications;

use GoniCore\Core\Database\QueryBuilder;

final class NotificationRepository
{
    private const TABLE = 'notifications';

    public function __construct(private readonly QueryBuilder $qb) {}

    // ── Write ─────────────────────────────────────────────────────────────────

    public function create(
        string  $type,
        string  $title,
        ?string $message = null,
        ?int    $userId  = null,
        string  $icon    = '🔔',
        array   $data    = [],
    ): int {
        return (int) $this->qb->table(self::TABLE)->insert([
            'user_id' => $userId,
            'type'    => $type,
            'title'   => $title,
            'message' => $message,
            'icon'    => $icon,
            'data'    => !empty($data) ? json_encode($data) : null,
        ]);
    }

    public function markRead(int $id, int $userId): void
    {
        $this->qb->table(self::TABLE)
            ->where('id', '=', $id)
            ->where('user_id', '=', $userId)
            ->update(['read_at' => date('Y-m-d H:i:s')]);
    }

    public function markAllRead(int $userId): void
    {
        // mark user-specific + broadcasts
        $this->qb->table(self::TABLE)
            ->where('user_id', '=', $userId)
            ->where('read_at', '=', null)
            ->update(['read_at' => date('Y-m-d H:i:s')]);

        $this->qb->table(self::TABLE)
            ->where('user_id', '=', null)
            ->where('read_at', '=', null)
            ->update(['read_at' => date('Y-m-d H:i:s')]);
    }

    // ── Read ──────────────────────────────────────────────────────────────────

    /** @return list<array<string, mixed>> */
    public function forUser(int $userId, int $limit = 30): array
    {
        $own  = $this->qb->table(self::TABLE)
            ->where('user_id', '=', $userId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();

        $broadcast = $this->qb->table(self::TABLE)
            ->where('user_id', '=', null)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();

        $merged = array_merge($own, $broadcast);

        usort($merged, fn($a, $b) => strtotime((string)$b['created_at']) <=> strtotime((string)$a['created_at']));

        return array_slice($merged, 0, $limit);
    }

    public function unreadCount(int $userId): int
    {
        $own = $this->qb->table(self::TABLE)
            ->where('user_id', '=', $userId)
            ->where('read_at', '=', null)
            ->count();

        $broadcast = $this->qb->table(self::TABLE)
            ->where('user_id', '=', null)
            ->where('read_at', '=', null)
            ->count();

        return $own + $broadcast;
    }
}
