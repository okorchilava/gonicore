<?php

declare(strict_types=1);

namespace GoniCore\Modules\Login;

use GoniCore\Core\Database\QueryBuilder;

/**
 * Brute-force / password-spraying protection for the login flow.
 *
 * Policy (deny-by-default, strict):
 *   • A single IP + identifier pair is locked after MAX_PER_IDENTIFIER failed
 *     attempts within WINDOW_SECONDS.
 *   • A single IP is locked after MAX_PER_IP failed attempts within
 *     WINDOW_SECONDS (stops spraying many accounts from one host).
 *
 * The check is fail-closed: if the throttle store is unreachable the query
 * throws and the login request fails — it never silently allows unlimited
 * attempts.
 */
final class LoginThrottle
{
    private const TABLE              = 'login_attempts';
    private const WINDOW_SECONDS     = 900;  // 15 minutes
    private const MAX_PER_IDENTIFIER = 5;
    private const MAX_PER_IP         = 20;

    public function __construct(private readonly QueryBuilder $qb) {}

    /** True when this IP+identifier (or the IP alone) has exceeded the limit. */
    public function isLocked(string $ip, string $identifier): bool
    {
        $since = $this->windowStart();

        $byPair = $this->qb->table(self::TABLE)
            ->where('ip', '=', $ip)
            ->where('identifier', '=', $identifier)
            ->where('attempted_at', '>=', $since)
            ->count();
        if ($byPair >= self::MAX_PER_IDENTIFIER) {
            return true;
        }

        $byIp = $this->qb->table(self::TABLE)
            ->where('ip', '=', $ip)
            ->where('attempted_at', '>=', $since)
            ->count();

        return $byIp >= self::MAX_PER_IP;
    }

    /** Record one failed attempt. */
    public function recordFailure(string $ip, string $identifier): void
    {
        $this->qb->table(self::TABLE)->insert([
            'ip'         => substr($ip, 0, 45),
            'identifier' => substr($identifier, 0, 190),
        ]);
        $this->prune();
    }

    /** Clear attempts for a pair after a successful login. */
    public function clear(string $ip, string $identifier): void
    {
        $this->qb->table(self::TABLE)
            ->where('ip', '=', $ip)
            ->where('identifier', '=', $identifier)
            ->delete();
    }

    private function windowStart(): string
    {
        return date('Y-m-d H:i:s', time() - self::WINDOW_SECONDS);
    }

    /** Remove rows older than the window so the table cannot grow unbounded. */
    private function prune(): void
    {
        $this->qb->table(self::TABLE)
            ->where('attempted_at', '<', $this->windowStart())
            ->delete();
    }
}
