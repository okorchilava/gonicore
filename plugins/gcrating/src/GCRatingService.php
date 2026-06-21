<?php
declare(strict_types=1);

namespace GCRating;

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;

/**
 * GCRating — visitor analytics service.
 *
 * Tracks sessions, pageviews, time on page, traffic sources,
 * device types and browsers. No personal data stored — IPs are
 * never persisted; visitor identification uses random localStorage tokens.
 */
final class GCRatingService
{
    private static ?self $instance = null;
    private ?array $settingsCache  = null;

    /** Referrer hostnames treated as search engines. */
    private const SEARCH_DOMAINS = [
        'google.', 'bing.', 'yahoo.', 'yandex.', 'baidu.', 'duckduckgo.',
        'ask.com', 'ecosia.', 'startpage.', 'qwant.', 'brave.com',
    ];

    /** Referrer hostnames treated as social networks. */
    private const SOCIAL_DOMAINS = [
        'facebook.', 'fb.com', 'twitter.', 't.co', 'x.com', 'instagram.',
        'linkedin.', 'pinterest.', 'youtube.', 'tiktok.', 'reddit.',
        'snapchat.', 'telegram.', 'whatsapp.', 'vk.com', 'ok.ru',
    ];

    public function __construct(
        private readonly QueryBuilder $qb,
        private readonly Connection   $conn,
    ) {}

    // ── Singleton ──────────────────────────────────────────────────────────────

    public static function register(self $s): void { self::$instance = $s; }
    public static function getInstance(): ?self     { return self::$instance; }

    // ── Settings ───────────────────────────────────────────────────────────────

    /** @return array<string,string> */
    public function getSettings(): array
    {
        if ($this->settingsCache !== null) return $this->settingsCache;
        $rows = $this->conn->query("SELECT `key`, `value` FROM `gcrating_settings`");
        $this->settingsCache = [];
        foreach ($rows as $r) {
            $this->settingsCache[(string)$r['key']] = (string)$r['value'];
        }
        return $this->settingsCache;
    }

    public function getSetting(string $key, string $default = ''): string
    {
        return $this->getSettings()[$key] ?? $default;
    }

    public function saveSetting(string $key, string $value): void
    {
        $this->conn->execute(
            "INSERT INTO `gcrating_settings` (`key`, `value`) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)",
            [$key, $value]
        );
        $this->settingsCache = null;
    }

    // ── Tracking ───────────────────────────────────────────────────────────────

    /**
     * Record an incoming pageview from the JS beacon.
     *
     * @param  array<string,mixed> $data  Decoded JSON from the request body.
     * @return array{session_id:int,pv_id:int}|array{}  Empty array on failure.
     */
    public function trackPageview(array $data): array
    {
        if ($this->getSetting('enabled', '1') !== '1') return [];

        $sessionHash = $this->sanitizeHash((string)($data['sid']   ?? ''));
        $visitorHash = $this->sanitizeHash((string)($data['vid']   ?? ''));
        if (!$sessionHash || !$visitorHash) return [];

        $url      = mb_substr(
            filter_var((string)($data['url'] ?? '/'), FILTER_SANITIZE_URL) ?: '/', 0, 500
        );
        $title    = mb_substr(strip_tags((string)($data['title']  ?? '')), 0, 300);
        $referrer = mb_substr((string)($data['ref']               ?? ''), 0, 500);
        $ua       = mb_substr((string)($data['ua']                ?? ''), 0, 300);
        $width    = (int)($data['w']                              ?? 0);
        $isTouch  = (bool)($data['touch']                         ?? false);
        $utmSrc   = mb_substr((string)($data['utm_source']        ?? ''), 0, 100);
        $utmMed   = mb_substr((string)($data['utm_medium']        ?? ''), 0, 100);
        $utmCamp  = mb_substr((string)($data['utm_campaign']      ?? ''), 0, 100);

        $now = date('Y-m-d H:i:s');

        // ── Session: continue or create ────────────────────────────────────────
        $existing = $this->conn->queryOne(
            "SELECT `id` FROM `gcrating_sessions` WHERE `session_hash` = ? LIMIT 1",
            [$sessionHash]
        );

        if ($existing) {
            $sessionId = (int)$existing['id'];
            $this->conn->execute(
                "UPDATE `gcrating_sessions`
                 SET `pages_viewed` = `pages_viewed` + 1, `updated_at` = ?
                 WHERE `id` = ?",
                [$now, $sessionId]
            );
        } else {
            $refHost    = $this->extractHost($referrer);
            $sourceType = $this->detectSource($referrer, $refHost, $utmMed);
            $device     = $this->parseDevice($ua, $width, $isTouch);
            $browser    = $this->parseBrowser($ua);
            $os         = $this->parseOs($ua);

            $this->conn->execute(
                "INSERT INTO `gcrating_sessions`
                 (`session_hash`, `visitor_hash`, `device`, `browser`, `os`,
                  `source_type`, `referrer`, `referrer_host`,
                  `utm_source`, `utm_medium`, `utm_campaign`,
                  `pages_viewed`, `total_time`, `created_at`, `updated_at`)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 0, ?, ?)",
                [
                    $sessionHash, $visitorHash, $device, $browser, $os,
                    $sourceType, $referrer, mb_substr($refHost, 0, 120),
                    $utmSrc, $utmMed, $utmCamp, $now, $now,
                ]
            );
            $sessionId = (int)$this->conn->lastInsertId();
        }

        // ── Pageview record ────────────────────────────────────────────────────
        $this->conn->execute(
            "INSERT INTO `gcrating_pageviews` (`session_id`, `url`, `title`, `time_spent`, `created_at`)
             VALUES (?, ?, ?, 0, ?)",
            [$sessionId, $url, $title, $now]
        );
        $pvId = (int)$this->conn->lastInsertId();

        return ['session_id' => $sessionId, 'pv_id' => $pvId];
    }

    /**
     * Update time spent on a page (called via sendBeacon on page unload).
     * Only updates if the new value is larger than the current one.
     */
    public function updateDuration(int $pvId, int $sessionId, int $seconds): void
    {
        if ($pvId <= 0 || $sessionId <= 0 || $seconds <= 0) return;
        $seconds = min($seconds, 86_400); // cap at 24 h

        $this->conn->execute(
            "UPDATE `gcrating_pageviews`
             SET `time_spent` = ?
             WHERE `id` = ? AND `session_id` = ? AND `time_spent` < ?",
            [$seconds, $pvId, $sessionId, $seconds]
        );

        // Recalculate session total time
        $this->conn->execute(
            "UPDATE `gcrating_sessions`
             SET `total_time` = (
                 SELECT COALESCE(SUM(`time_spent`), 0)
                 FROM `gcrating_pageviews`
                 WHERE `session_id` = ?
             )
             WHERE `id` = ?",
            [$sessionId, $sessionId]
        );
    }

    // ── Stats ──────────────────────────────────────────────────────────────────

    /**
     * Aggregate KPIs for the given period.
     * @return array{sessions:int,unique_visitors:int,pageviews:int,avg_session_time:int,avg_pages:float,bounce_rate:float}
     */
    public function overview(string $period = '30d'): array
    {
        $since = $this->periodToDate($period);
        $where = $since ? 'WHERE s.`created_at` >= ?' : 'WHERE 1=1';
        $args  = $since ? [$since] : [];

        $row = $this->conn->queryOne(
            "SELECT
                COUNT(DISTINCT s.`id`)                           AS sessions,
                COUNT(DISTINCT s.`visitor_hash`)                 AS unique_visitors,
                COUNT(p.`id`)                                    AS pageviews,
                COALESCE(AVG(NULLIF(s.`total_time`, 0)),  0)     AS avg_session_time,
                COALESCE(AVG(s.`pages_viewed`),           0)     AS avg_pages
             FROM `gcrating_sessions` s
             LEFT JOIN `gcrating_pageviews` p ON p.`session_id` = s.`id`
             $where",
            $args
        ) ?? [];

        // Bounce: sessions with only 1 page viewed
        $bounceWhere = $since ? 'WHERE `created_at` >= ? AND' : 'WHERE';
        $bounce = (int)$this->conn->scalar(
            "SELECT COUNT(*) FROM `gcrating_sessions` $bounceWhere `pages_viewed` = 1",
            $since ? [$since] : []
        );

        $total = max(1, (int)($row['sessions'] ?? 0));

        return [
            'sessions'         => (int)($row['sessions']         ?? 0),
            'unique_visitors'  => (int)($row['unique_visitors']   ?? 0),
            'pageviews'        => (int)($row['pageviews']         ?? 0),
            'avg_session_time' => (int)round((float)($row['avg_session_time'] ?? 0)),
            'avg_pages'        => round((float)($row['avg_pages'] ?? 0), 1),
            'bounce_rate'      => round($bounce / $total * 100, 1),
        ];
    }

    /**
     * Sessions and unique visitors for today only (for topbar counter).
     * @return array{sessions:int,visitors:int}
     */
    public function todayStats(): array
    {
        return $this->conn->queryOne(
            "SELECT
                COUNT(DISTINCT `session_hash`) AS sessions,
                COUNT(DISTINCT `visitor_hash`) AS visitors
             FROM `gcrating_sessions`
             WHERE DATE(`created_at`) = CURDATE()"
        ) ?? ['sessions' => 0, 'visitors' => 0];
    }

    /**
     * Per-day counts for the last $days days (for the dashboard chart).
     * @return list<array{date:string,sessions:int,visitors:int,pageviews:int}>
     */
    public function dailyStats(int $days = 30): array
    {
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        return $this->conn->query(
            "SELECT
                DATE(s.`created_at`)             AS `date`,
                COUNT(DISTINCT s.`id`)           AS sessions,
                COUNT(DISTINCT s.`visitor_hash`) AS visitors,
                COUNT(p.`id`)                    AS pageviews
             FROM `gcrating_sessions` s
             LEFT JOIN `gcrating_pageviews` p ON p.`session_id` = s.`id`
             WHERE s.`created_at` >= ?
             GROUP BY DATE(s.`created_at`)
             ORDER BY `date` ASC",
            [$since]
        );
    }

    /**
     * Top pages ordered by view count.
     * @return list<array{url:string,title:string,views:int,unique_views:int,avg_time:float}>
     */
    public function topPages(int $limit = 20, string $period = '30d'): array
    {
        $since = $this->periodToDate($period);
        $where = $since ? 'WHERE s.`created_at` >= ?' : 'WHERE 1=1';
        $args  = $since ? [$since] : [];
        $limit = max(1, min(500, $limit));

        return $this->conn->query(
            "SELECT
                p.`url`,
                MAX(p.`title`)                              AS title,
                COUNT(p.`id`)                               AS views,
                COUNT(DISTINCT s.`visitor_hash`)            AS unique_views,
                COALESCE(AVG(NULLIF(p.`time_spent`, 0)), 0) AS avg_time
             FROM `gcrating_pageviews` p
             JOIN `gcrating_sessions` s ON s.`id` = p.`session_id`
             $where
             GROUP BY p.`url`
             ORDER BY views DESC
             LIMIT $limit",
            $args
        );
    }

    /**
     * Top external referrer hosts.
     * @return list<array{referrer_host:string,source_type:string,sessions:int,visitors:int}>
     */
    public function topReferrers(int $limit = 20, string $period = '30d'): array
    {
        $since = $this->periodToDate($period);
        $where = $since
            ? "WHERE `created_at` >= ? AND `source_type` != 'internal'"
            : "WHERE `source_type` != 'internal'";
        $args  = $since ? [$since] : [];
        $limit = max(1, min(500, $limit));

        return $this->conn->query(
            "SELECT
                `referrer_host`,
                `source_type`,
                COUNT(*)                       AS sessions,
                COUNT(DISTINCT `visitor_hash`) AS visitors
             FROM `gcrating_sessions`
             $where AND `referrer_host` != ''
             GROUP BY `referrer_host`, `source_type`
             ORDER BY sessions DESC
             LIMIT $limit",
            $args
        );
    }

    /**
     * Breakdown of sessions by traffic source type.
     * @return list<array{source_type:string,cnt:int}>
     */
    public function sourceStats(string $period = '30d'): array
    {
        $since = $this->periodToDate($period);
        $where = $since ? 'WHERE `created_at` >= ?' : '';
        $args  = $since ? [$since] : [];
        return $this->conn->query(
            "SELECT `source_type`, COUNT(*) AS cnt
             FROM `gcrating_sessions` $where
             GROUP BY `source_type`
             ORDER BY cnt DESC",
            $args
        );
    }

    /**
     * Breakdown of sessions by device type.
     * @return list<array{device:string,cnt:int}>
     */
    public function deviceStats(string $period = '30d'): array
    {
        $since = $this->periodToDate($period);
        $where = $since ? 'WHERE `created_at` >= ?' : '';
        $args  = $since ? [$since] : [];
        return $this->conn->query(
            "SELECT `device`, COUNT(*) AS cnt
             FROM `gcrating_sessions` $where
             GROUP BY `device` ORDER BY cnt DESC",
            $args
        );
    }

    /**
     * Breakdown of sessions by browser (top 8).
     * @return list<array{browser:string,cnt:int}>
     */
    public function browserStats(string $period = '30d'): array
    {
        $since = $this->periodToDate($period);
        $where = $since ? 'WHERE `created_at` >= ?' : '';
        $args  = $since ? [$since] : [];
        return $this->conn->query(
            "SELECT `browser`, COUNT(*) AS cnt
             FROM `gcrating_sessions` $where
             GROUP BY `browser` ORDER BY cnt DESC LIMIT 8",
            $args
        );
    }

    /**
     * Total database row counts (for settings page / storage overview).
     * @return array{sessions:int,pageviews:int}
     */
    public function totalRows(): array
    {
        return $this->conn->queryOne(
            "SELECT
                (SELECT COUNT(*) FROM `gcrating_sessions`)  AS sessions,
                (SELECT COUNT(*) FROM `gcrating_pageviews`) AS pageviews"
        ) ?? ['sessions' => 0, 'pageviews' => 0];
    }

    /**
     * Delete sessions older than $retentionDays. Returns deleted count.
     */
    public function cleanup(int $retentionDays = 365): int
    {
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$retentionDays} days"));
        $stmt   = $this->conn->execute(
            "DELETE FROM `gcrating_sessions` WHERE `created_at` < ? LIMIT 5000",
            [$cutoff]
        );
        return $stmt->rowCount();
    }

    /**
     * Hard-delete ALL analytics data (used from Settings danger zone).
     */
    public function truncate(): void
    {
        $this->conn->execute("DELETE FROM `gcrating_pageviews`");
        $this->conn->execute("DELETE FROM `gcrating_sessions`");
    }

    // ── UA / referrer parsing ──────────────────────────────────────────────────

    private function parseDevice(string $ua, int $w = 0, bool $touch = false): string
    {
        $l = strtolower($ua);
        // iPad and Android tablets
        if (str_contains($l, 'ipad') || (str_contains($l, 'android') && !str_contains($l, 'mobile'))) {
            return 'tablet';
        }
        // Heuristic: touch device with mid-range screen width
        if ($touch && $w >= 600 && $w <= 1366 && !str_contains($l, 'mobile') && !str_contains($l, 'iphone')) {
            return 'tablet';
        }
        if (str_contains($l, 'mobile') || str_contains($l, 'iphone') || str_contains($l, 'ipod') ||
            ($touch && $w < 600)) {
            return 'mobile';
        }
        return 'desktop';
    }

    private function parseBrowser(string $ua): string
    {
        // Order matters — Edge/Chrome both contain "Chrome", check Edge first
        if (str_contains($ua, 'Edg/') || str_contains($ua, 'Edge/')) return 'Edge';
        if (str_contains($ua, 'OPR/')  || str_contains($ua, 'Opera'))  return 'Opera';
        if (str_contains($ua, 'YaBrowser'))                             return 'Yandex';
        if (str_contains($ua, 'Chrome/'))                               return 'Chrome';
        if (str_contains($ua, 'Firefox/'))                              return 'Firefox';
        if (str_contains($ua, 'Safari/'))                               return 'Safari';
        if (str_contains($ua, 'MSIE') || str_contains($ua, 'Trident')) return 'IE';
        return 'Other';
    }

    private function parseOs(string $ua): string
    {
        if (str_contains($ua, 'Windows NT 10'))  return 'Windows 10/11';
        if (str_contains($ua, 'Windows NT 6.3')) return 'Windows 8.1';
        if (str_contains($ua, 'Windows NT'))     return 'Windows';
        if (str_contains($ua, 'Macintosh'))      return 'macOS';
        if (str_contains($ua, 'Android'))        return 'Android';
        if (str_contains($ua, 'iPhone') || str_contains($ua, 'iPad')) return 'iOS';
        if (str_contains($ua, 'Linux'))          return 'Linux';
        return 'Other';
    }

    private function detectSource(string $referrer, string $refHost, string $utmMedium = ''): string
    {
        // UTM medium overrides referrer-based detection
        if ($utmMedium !== '') {
            $med = strtolower($utmMedium);
            if (in_array($med, ['cpc', 'ppc', 'paid'], true)) return 'referral';
            if (str_contains($med, 'social'))                  return 'social';
            if (str_contains($med, 'email'))                   return 'referral';
        }

        if (!$referrer || !$refHost) return 'direct';

        // Same domain → internal
        $own = strtolower($_SERVER['HTTP_HOST'] ?? '');
        if ($own && (str_contains(strtolower($refHost), $own) || str_contains($own, $refHost))) {
            return 'internal';
        }

        $host = strtolower($refHost);
        foreach (self::SEARCH_DOMAINS as $d) {
            if (str_contains($host, $d)) return 'search';
        }
        foreach (self::SOCIAL_DOMAINS as $d) {
            if (str_contains($host, $d)) return 'social';
        }
        return 'referral';
    }

    private function extractHost(string $url): string
    {
        if (!$url) return '';
        $host = parse_url($url, PHP_URL_HOST) ?: '';
        return strtolower((string)preg_replace('/^www\./', '', $host));
    }

    private function periodToDate(string $period): string
    {
        return match ($period) {
            'today' => date('Y-m-d'),
            '7d'    => date('Y-m-d', strtotime('-7 days')),
            '30d'   => date('Y-m-d', strtotime('-30 days')),
            '90d'   => date('Y-m-d', strtotime('-90 days')),
            'all'   => '',
            default => date('Y-m-d', strtotime('-30 days')),
        };
    }

    private function sanitizeHash(string $hash): string
    {
        return substr((string)preg_replace('/[^a-zA-Z0-9]/', '', $hash), 0, 32);
    }

    // ── Static helpers (usable in views) ──────────────────────────────────────

    /**
     * Detect common crawlers and headless browsers.
     */
    public static function isBot(string $ua): bool
    {
        if (!$ua || strlen($ua) < 10) return true;
        $bots = [
            'bot', 'crawler', 'spider', 'slurp', 'mediapartners', 'adsbot',
            'googlebot', 'bingbot', 'yandex', 'baidu', 'duckduck',
            'facebookexternalhit', 'linkedinbot', 'twitterbot',
            'semrush', 'ahrefs', 'mj12bot', 'dotbot', 'petalbot', 'bytespider',
            'headlesschrome', 'phantomjs', 'selenium', 'wget', 'curl/',
        ];
        $l = strtolower($ua);
        foreach ($bots as $b) {
            if (str_contains($l, $b)) return true;
        }
        return false;
    }

    /**
     * Human-readable time duration in Georgian units.
     */
    public static function formatTime(int $seconds): string
    {
        if ($seconds <= 0) return '—';
        if ($seconds < 60) return $seconds . 'წმ';
        $m = intdiv($seconds, 60);
        $s = $seconds % 60;
        if ($m < 60) return $m . 'წთ' . ($s > 0 ? ' ' . $s . 'წმ' : '');
        $h = intdiv($m, 60);
        $m = $m % 60;
        return $h . 'სთ' . ($m > 0 ? ' ' . $m . 'წთ' : '');
    }

    /** Human-readable label for a source type. */
    public static function sourceLabel(string $type): string
    {
        return match ($type) {
            'direct'   => '🔗 Direct',
            'search'   => '🔍 Search',
            'social'   => '💬 Social',
            'referral' => '↗ Referral',
            'internal' => '🔄 Internal',
            default    => ucfirst($type),
        };
    }

    /** Brand color per source type. */
    public static function sourceColor(string $type): string
    {
        return match ($type) {
            'direct'   => '#3b82f6',
            'search'   => '#10b981',
            'social'   => '#8b5cf6',
            'referral' => '#f59e0b',
            'internal' => '#6b7280',
            default    => '#9ca3af',
        };
    }
}
