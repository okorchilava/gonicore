<?php
declare(strict_types=1);

namespace GsAds;

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;

final class GsAdsService
{
    // ── Static registry (for the global gsads() helper) ───────────────────────

    private static ?self $instance   = null;
    private static string $clickBase = '/gsads/click';

    public static function register(self $s): void        { self::$instance   = $s; }
    public static function getInstance(): ?self           { return self::$instance; }
    public static function setClickBase(string $b): void  { self::$clickBase  = $b; }

    // ── Constructor ────────────────────────────────────────────────────────────

    public function __construct(
        private readonly QueryBuilder $qb,
        private readonly Connection   $conn,
    ) {}

    // ── Zone CRUD ──────────────────────────────────────────────────────────────

    /** @return list<array<string,mixed>> */
    public function zones(): array
    {
        return $this->qb->table('gsads_zones')->orderBy('name', 'ASC')->get();
    }

    public function zone(int $id): ?array
    {
        return $this->qb->table('gsads_zones')->where('id', '=', $id)->first() ?: null;
    }

    public function zoneBySlug(string $slug): ?array
    {
        return $this->qb->table('gsads_zones')->where('slug', '=', $slug)->first() ?: null;
    }

    public function saveZone(array $data, ?int $id = null): void
    {
        if ($id) {
            $this->qb->table('gsads_zones')->where('id', '=', $id)->update($data);
        } else {
            $this->qb->table('gsads_zones')->insert($data);
        }
    }

    public function deleteZone(int $id): void
    {
        $this->qb->table('gsads_zones')->where('id', '=', $id)->delete();
    }

    /** @return list<array<string,mixed>> — zones enriched with ad_count, impressions, clicks */
    public function zoneStats(): array
    {
        $rows = $this->conn->pdo()->query("
            SELECT z.*,
                   COUNT(a.id)                       AS ad_count,
                   COALESCE(SUM(a.active), 0)        AS active_ads,
                   COALESCE(SUM(a.impressions), 0)   AS impressions,
                   COALESCE(SUM(a.clicks), 0)        AS clicks
            FROM   `gsads_zones` z
            LEFT   JOIN `gsads_ads` a ON a.zone_id = z.id
            GROUP  BY z.id
            ORDER  BY z.name ASC
        ")->fetchAll(\PDO::FETCH_ASSOC);

        return $rows ?: [];
    }

    // ── Ad CRUD ────────────────────────────────────────────────────────────────

    /**
     * @return array{items:list<array<string,mixed>>, total:int, pages:int}
     */
    public function ads(?int $zoneId = null, int $page = 1, int $perPage = 25): array
    {
        $qb = $this->qb->table('gsads_ads')->orderBy('created_at', 'DESC');
        if ($zoneId !== null) $qb = $qb->where('zone_id', '=', $zoneId);
        $total = (int) $qb->count();
        $items = $qb->limit($perPage)->offset(($page - 1) * $perPage)->get();
        return [
            'items' => $items,
            'total' => $total,
            'pages' => max(1, (int) ceil($total / $perPage)),
        ];
    }

    public function ad(int $id): ?array
    {
        return $this->qb->table('gsads_ads')->where('id', '=', $id)->first() ?: null;
    }

    public function saveAd(array $data, ?int $id = null): void
    {
        if ($id) {
            $this->qb->table('gsads_ads')->where('id', '=', $id)->update($data);
        } else {
            $this->qb->table('gsads_ads')->insert($data);
        }
    }

    public function deleteAd(int $id): void
    {
        $this->qb->table('gsads_ads')->where('id', '=', $id)->delete();
    }

    // ── Counters (atomic SQL) ──────────────────────────────────────────────────

    public function recordImpression(int $adId): void
    {
        try {
            $this->conn->pdo()
                ->prepare("UPDATE `gsads_ads` SET `impressions` = `impressions` + 1 WHERE `id` = ?")
                ->execute([$adId]);
        } catch (\Throwable) {}
    }

    public function recordClick(int $adId): void
    {
        try {
            $this->conn->pdo()
                ->prepare("UPDATE `gsads_ads` SET `clicks` = `clicks` + 1 WHERE `id` = ?")
                ->execute([$adId]);
        } catch (\Throwable) {}
    }

    // ── Dashboard stats ────────────────────────────────────────────────────────

    public function stats(): array
    {
        $pdo = $this->conn->pdo();

        $zoneCount = (int) $pdo->query("SELECT COUNT(*) FROM `gsads_zones`")->fetchColumn();

        $adRow = $pdo->query("
            SELECT COUNT(*)                                   AS total,
                   COALESCE(SUM(active = 1), 0)              AS active_cnt,
                   COALESCE(SUM(impressions), 0)             AS impressions,
                   COALESCE(SUM(clicks),      0)             AS clicks
            FROM `gsads_ads`
        ")->fetch(\PDO::FETCH_ASSOC);

        $imp = (int) ($adRow['impressions'] ?? 0);
        $clk = (int) ($adRow['clicks']      ?? 0);

        return [
            'total_zones'       => $zoneCount,
            'total_ads'         => (int) ($adRow['total']      ?? 0),
            'active_ads'        => (int) ($adRow['active_cnt'] ?? 0),
            'total_impressions'  => $imp,
            'total_clicks'      => $clk,
            'ctr'               => $imp > 0 ? round($clk / $imp * 100, 2) : 0.0,
        ];
    }

    // ── Frontend rendering ─────────────────────────────────────────────────────

    /**
     * Returns HTML for the zone. Call from theme templates:
     *
     *   <?= gsads('header-banner') ?>
     *   <?= gsads('sidebar-block', 3) ?>   ← up to 3 ads
     *   <?= gsads('footer-strip', 0)  ?>   ← all active ads
     */
    public function renderZone(string $slug, int $limit = 1): string
    {
        $zone = $this->zoneBySlug($slug);
        if (!$zone || !(int)$zone['active']) return '';

        $ads = $this->eligibleAds((int) $zone['id'], $limit);
        if (empty($ads)) return '';

        $html = '<div class="gsads-zone gsads-zone--' . htmlspecialchars($slug, ENT_QUOTES) . '" style="text-align:center">';
        foreach ($ads as $ad) {
            $this->recordImpression((int) $ad['id']);
            $html .= $this->adHtml($ad);
        }
        $html .= '</div>';
        return $html;
    }

    /**
     * Returns active, scheduled-eligible ads ordered randomly (weight via ORDER BY RAND()).
     * @return list<array<string,mixed>>
     */
    private function eligibleAds(int $zoneId, int $limit): array
    {
        $today   = date('Y-m-d');
        $limitSql = $limit > 0 ? ' LIMIT ' . $limit : '';

        $stmt = $this->conn->pdo()->prepare("
            SELECT * FROM `gsads_ads`
            WHERE  `zone_id`  = ?
              AND  `active`   = 1
              AND  (`starts_at` IS NULL OR `starts_at` <= ?)
              AND  (`ends_at`   IS NULL OR `ends_at`   >= ?)
            ORDER  BY RAND()
            $limitSql
        ");
        $stmt->execute([$zoneId, $today, $today]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    private function adHtml(array $ad): string
    {
        $id       = (int) $ad['id'];
        $blank    = $ad['opens_blank'] ? ' target="_blank" rel="noopener nofollow"' : '';
        $clickUrl = self::$clickBase . '?id=' . $id;
        $ch       = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return match ((string) $ad['type']) {
            'image' => sprintf(
                '<a href="%s"%s class="gsads-ad gsads-ad--image" style="display:inline-block">'
                . '<img src="%s" alt="%s" loading="lazy" style="max-width:100%%;height:auto;display:block;margin:0 auto">'
                . '</a>',
                $ch($clickUrl), $blank,
                $ch((string) $ad['image_url']),
                $ch((string) $ad['name']),
            ),
            'text' => sprintf(
                '<a href="%s"%s class="gsads-ad gsads-ad--text" style="display:inline-block">'
                . '<strong class="gsads-text-title">%s</strong>'
                . '%s'
                . '</a>',
                $ch($clickUrl), $blank,
                $ch((string) $ad['ad_title']),
                $ad['ad_body']
                    ? '<span class="gsads-text-body">' . $ch((string) $ad['ad_body']) . '</span>'
                    : '',
            ),
            'html'  => '<div class="gsads-ad gsads-ad--html" style="display:flex;justify-content:center;align-items:center">' . (string) $ad['html_code'] . '</div>',
            default => '',
        };
    }
}
