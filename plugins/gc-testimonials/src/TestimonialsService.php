<?php

declare(strict_types=1);

namespace GCTestimonials;

use GoniCore\Core\Database\Connection;
use GoniCore\Shared\Support\Str;

/**
 * Data access + presentation helpers for GC Testimonials.
 *
 * All queries are parameterised. Reviews are moderated: public submissions are
 * stored with is_public = 0 and only appear on the site once an admin approves
 * them (is_public = 1).
 */
final class TestimonialsService
{
    public function __construct(private readonly Connection $db) {}

    // ── Campaigns ───────────────────────────────────────────────────────────────

    /** @return list<array<string,mixed>> */
    public function campaigns(): array
    {
        return $this->db->query('SELECT * FROM `gc_testimonial_campaigns` ORDER BY `id` DESC');
    }

    /** @return array<string,mixed>|null */
    public function campaign(int $id): ?array
    {
        return $this->db->queryOne('SELECT * FROM `gc_testimonial_campaigns` WHERE `id` = ? LIMIT 1', [$id]);
    }

    /** @return array<string,mixed>|null */
    public function campaignBySlug(string $slug): ?array
    {
        return $this->db->queryOne('SELECT * FROM `gc_testimonial_campaigns` WHERE `slug` = ? LIMIT 1', [$slug]);
    }

    public function createCampaign(string $name): int
    {
        $slug = $this->uniqueSlug($this->slugify($name));
        $this->db->execute(
            'INSERT INTO `gc_testimonial_campaigns` (`name`, `slug`) VALUES (?, ?)',
            [$name, $slug]
        );
        return (int) $this->db->pdo()->lastInsertId();
    }

    public function deleteCampaign(int $id): void
    {
        $this->db->execute('DELETE FROM `gc_testimonial_campaigns` WHERE `id` = ?', [$id]);
        // Keep the reviews — just detach them to the "general" bucket (0).
        $this->db->execute('UPDATE `gc_testimonials` SET `campaign_id` = 0 WHERE `campaign_id` = ?', [$id]);
    }

    // ── Testimonials (admin) ──────────────────────────────────────────────────────

    /** @return list<array<string,mixed>> */
    public function all(): array
    {
        return $this->db->query(
            'SELECT t.*, c.`name` AS campaign_name
               FROM `gc_testimonials` t
               LEFT JOIN `gc_testimonial_campaigns` c ON c.`id` = t.`campaign_id`
              ORDER BY t.`id` DESC'
        );
    }

    /** @return array<string,mixed>|null */
    public function find(int $id): ?array
    {
        return $this->db->queryOne('SELECT * FROM `gc_testimonials` WHERE `id` = ? LIMIT 1', [$id]);
    }

    /** @param array<string,mixed> $d */
    public function create(array $d): int
    {
        $this->db->execute(
            'INSERT INTO `gc_testimonials`
                (`campaign_id`, `client_name`, `client_role`, `testimonial_text`, `rating`, `is_public`)
             VALUES (?, ?, ?, ?, ?, ?)',
            [
                (int) $d['campaign_id'], (string) $d['client_name'], (string) $d['client_role'],
                (string) $d['testimonial_text'], (int) $d['rating'], (int) $d['is_public'],
            ]
        );
        return (int) $this->db->pdo()->lastInsertId();
    }

    /** @param array<string,mixed> $d */
    public function update(int $id, array $d): void
    {
        $this->db->execute(
            'UPDATE `gc_testimonials`
                SET `campaign_id` = ?, `client_name` = ?, `client_role` = ?,
                    `testimonial_text` = ?, `rating` = ?, `is_public` = ?
              WHERE `id` = ?',
            [
                (int) $d['campaign_id'], (string) $d['client_name'], (string) $d['client_role'],
                (string) $d['testimonial_text'], (int) $d['rating'], (int) $d['is_public'], $id,
            ]
        );
    }

    public function delete(int $id): void
    {
        $this->db->execute('DELETE FROM `gc_testimonials` WHERE `id` = ?', [$id]);
    }

    public function setPublic(int $id, bool $public): void
    {
        $this->db->execute('UPDATE `gc_testimonials` SET `is_public` = ? WHERE `id` = ?', [$public ? 1 : 0, $id]);
    }

    public function pendingCount(): int
    {
        $row = $this->db->queryOne('SELECT COUNT(*) AS c FROM `gc_testimonials` WHERE `is_public` = 0');
        return (int) ($row['c'] ?? 0);
    }

    // ── Public (frontend) ─────────────────────────────────────────────────────────

    /**
     * Approved reviews for a campaign, newest first.
     * @return list<array<string,mixed>>
     */
    public function publicByCampaign(int $campaignId, int $limit = 12): array
    {
        $limit = max(1, min(100, $limit)); // hard cap; value is sanitised, not bound
        return $this->db->query(
            "SELECT * FROM `gc_testimonials`
              WHERE `is_public` = 1 AND `campaign_id` = ?
              ORDER BY `id` DESC
              LIMIT {$limit}",
            [$campaignId]
        );
    }

    /** Store a moderated public submission (always is_public = 0). */
    public function submitPublic(int $campaignId, string $name, string $text, int $rating): int
    {
        return $this->create([
            'campaign_id'      => $campaignId,
            'client_name'      => $name,
            'client_role'      => '',
            'testimonial_text' => $text,
            'rating'           => max(1, min(5, $rating)),
            'is_public'        => 0,
        ]);
    }

    // ── Presentation helpers ───────────────────────────────────────────────────────

    public function slugify(string $text): string
    {
        $slug = Str::slug($text);
        return $slug !== '' ? $slug : 'campaign';
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i    = 2;
        while ($this->db->queryOne('SELECT 1 FROM `gc_testimonial_campaigns` WHERE `slug` = ? LIMIT 1', [$slug]) !== null) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }

    /** Deterministic initials avatar (no external request, no stored file). */
    public static function avatar(string $name, string $size = '44px'): string
    {
        $name = trim($name);
        if ($name === '') {
            $name = '?';
        }
        $words = preg_split('/\s+/u', $name) ?: [$name];
        if (count($words) >= 2) {
            $initials = mb_substr($words[0], 0, 1, 'UTF-8') . mb_substr($words[count($words) - 1], 0, 1, 'UTF-8');
        } else {
            $initials = mb_substr($name, 0, 2, 'UTF-8');
        }

        $colors = ['#4f46e5', '#0ea5e9', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6'];
        $bg     = $colors[abs(crc32($name)) % count($colors)];
        $e      = static fn (string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

        // Fully self-contained inline styles so it renders identically in the
        // admin tables and on the frontend (no external CSS class needed).
        $style = 'background:' . $e($bg) . ';width:' . $e($size) . ';height:' . $e($size)
            . ';min-width:' . $e($size) . ';border-radius:10px;display:inline-flex;align-items:center;'
            . 'justify-content:center;color:#fff;font-weight:700;font-family:system-ui,sans-serif;'
            . 'font-size:calc(' . $e($size) . ' / 2.6)';

        return '<div class="gct-avatar" style="' . $style . '">' . $e(mb_strtoupper($initials, 'UTF-8')) . '</div>';
    }

    public static function formatDate(string $date): string
    {
        $ts = strtotime($date);
        return $ts !== false ? date('d.m.Y', $ts) : '';
    }

    /** Filled + empty stars markup. */
    public static function stars(int $rating): string
    {
        $rating = max(0, min(5, $rating));
        return str_repeat('★', $rating) . str_repeat('☆', 5 - $rating);
    }
}
