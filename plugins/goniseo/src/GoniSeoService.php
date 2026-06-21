<?php
declare(strict_types=1);

namespace GoniSeo;

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;

final class GoniSeoService
{
    private static ?self   $instance  = null;
    private static string  $basePath  = '';
    private ?array         $settingsCache = null;

    public function __construct(
        private readonly QueryBuilder $qb,
        private readonly Connection   $conn,
    ) {}

    public static function register(self $s): void       { self::$instance = $s; }
    public static function getInstance(): ?self           { return self::$instance; }
    public static function setBasePath(string $b): void   { self::$basePath = $b; }
    public static function getBasePath(): string           { return self::$basePath; }

    // ── Settings ───────────────────────────────────────────────────────────────

    /** @return array<string,string> */
    public function getSettings(): array
    {
        if ($this->settingsCache !== null) return $this->settingsCache;
        try {
            $rows = $this->qb->table('goniseo_settings')->get();
            $this->settingsCache = [];
            foreach ($rows as $row) {
                $this->settingsCache[(string)$row['key']] = (string)$row['value'];
            }
        } catch (\Throwable) {
            $this->settingsCache = [];
        }
        return $this->settingsCache;
    }

    public function getSetting(string $key, string $default = ''): string
    {
        return $this->getSettings()[$key] ?? $default;
    }

    public function saveSetting(string $key, string $value): void
    {
        $this->settingsCache = null;
        try {
            $existing = $this->qb->table('goniseo_settings')->where('key', '=', $key)->first();
            if ($existing) {
                $this->qb->table('goniseo_settings')->where('key', '=', $key)->update(['value' => $value]);
            } else {
                $this->qb->table('goniseo_settings')->insert(['key' => $key, 'value' => $value]);
            }
        } catch (\Throwable) {}
    }

    // ── Meta ───────────────────────────────────────────────────────────────────

    /** Fetch meta row by URL path (e.g. /about). */
    public function getMeta(string $path): ?array
    {
        try {
            return $this->qb->table('goniseo_meta')
                ->where('url_path', '=', $this->normalizePath($path))
                ->first() ?: null;
        } catch (\Throwable) { return null; }
    }

    /** Fetch meta row by database ID. */
    public function meta(int $id): ?array
    {
        try {
            return $this->qb->table('goniseo_meta')->where('id', '=', $id)->first() ?: null;
        } catch (\Throwable) { return null; }
    }

    public function saveMeta(array $data, ?int $id = null): int
    {
        if ($id) {
            $this->qb->table('goniseo_meta')->where('id', '=', $id)->update($data);
            return $id;
        }
        $this->qb->table('goniseo_meta')->insert($data);
        return (int) $this->conn->pdo()->lastInsertId();
    }

    public function deleteMeta(int $id): void
    {
        $this->qb->table('goniseo_meta')->where('id', '=', $id)->delete();
    }

    /** @return list<array<string,mixed>> */
    public function allMeta(string $search = '', int $page = 1, int $per = 25): array
    {
        $qb = $this->qb->table('goniseo_meta');
        if ($search !== '') {
            $qb = $qb->where('url_path', 'LIKE', '%' . $search . '%');
        }
        return $qb->orderBy('updated_at', 'DESC')->limit($per)->offset(($page - 1) * $per)->get();
    }

    public function countMeta(string $search = ''): int
    {
        $qb = $this->qb->table('goniseo_meta');
        if ($search !== '') {
            $qb = $qb->where('url_path', 'LIKE', '%' . $search . '%');
        }
        return (int) $qb->count();
    }

    // ── Sitemap ────────────────────────────────────────────────────────────────

    /** @return list<array<string,mixed>> */
    public function getSitemapUrls(): array
    {
        return $this->qb->table('goniseo_sitemap')->orderBy('created_at', 'ASC')->get();
    }

    public function getSitemapUrl(int $id): ?array
    {
        return $this->qb->table('goniseo_sitemap')->where('id', '=', $id)->first() ?: null;
    }

    public function saveSitemapUrl(array $data, ?int $id = null): int
    {
        if ($id) {
            $this->qb->table('goniseo_sitemap')->where('id', '=', $id)->update($data);
            return $id;
        }
        $this->qb->table('goniseo_sitemap')->insert($data);
        return (int) $this->conn->pdo()->lastInsertId();
    }

    public function deleteSitemapUrl(int $id): void
    {
        $this->qb->table('goniseo_sitemap')->where('id', '=', $id)->delete();
    }

    // ── Stats ──────────────────────────────────────────────────────────────────

    /** @return array<string,mixed> */
    public function stats(): array
    {
        try {
            $row = $this->conn->pdo()->query("
                SELECT
                    (SELECT COUNT(*) FROM `goniseo_meta`)    AS meta_count,
                    (SELECT COUNT(*) FROM `goniseo_sitemap`) AS sitemap_count
            ")->fetch(\PDO::FETCH_ASSOC);
            return $row ?: [];
        } catch (\Throwable) { return []; }
    }

    // ── Head tag rendering ─────────────────────────────────────────────────────

    /**
     * Build all SEO head tags for the given URL path.
     *
     * @param string $path           URL path, e.g. /about
     * @param string $existingTitle  <title> content already in the page (from theme)
     */
    public function renderHeadTags(string $path, string $existingTitle = ''): string
    {
        try {
            $s    = $this->getSettings();
            $meta = $this->getMeta($path);
            $h    = static fn(string $v) => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

            // ── Title ──────────────────────────────────────────────────────────
            $rawTitle = trim((string)($meta['title'] ?? $existingTitle));
            $siteName = trim((string)($s['site_name'] ?? ''));
            $format   = (string)($s['title_format'] ?? '{title} | {site_name}');
            if ($rawTitle !== '') {
                $title = str_replace(['{title}', '{site_name}'], [$rawTitle, $siteName], $format);
            } elseif ($siteName !== '') {
                $title = $siteName;
            } else {
                $title = $existingTitle;
            }

            // ── Current page URL ───────────────────────────────────────────────
            $cleanPath  = $this->normalizePath($path);
            $proto      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host       = (string)($_SERVER['HTTP_HOST'] ?? '');
            $currentUrl = $proto . '://' . $host . $cleanPath;

            // ── Meta values (per-URL overrides fall back to defaults) ──────────
            $desc      = trim((string)($meta['description']    ?? $s['default_description'] ?? ''));
            $keywords  = trim((string)($meta['keywords']       ?? $s['default_keywords'] ?? ''));
            $robots    = trim((string)($meta['robots']         ?? $s['default_robots'] ?? ''));
            $ogTitle   = trim((string)($meta['og_title']       ?? '')) ?: trim($title);
            $ogDesc    = trim((string)($meta['og_description'] ?? '')) ?: $desc;
            $ogImg     = trim((string)($meta['og_image']       ?? $s['default_og_image'] ?? ''));
            $canonical = trim((string)($meta['canonical']      ?? '')) ?: $currentUrl;
            $jsonLd    = trim((string)($meta['json_ld']        ?? ''));

            // ── Build tag list ─────────────────────────────────────────────────
            $tags = [];
            $tags[] = '<!-- GoniSEO -->';

            // Title
            if ($title !== '') $tags[] = '<title>' . $h($title) . '</title>';

            // Basic meta
            if ($desc !== '')     $tags[] = '<meta name="description" content="' . $h($desc) . '">';
            if ($keywords !== '') $tags[] = '<meta name="keywords" content="' . $h($keywords) . '">';
            if ($robots !== '')   $tags[] = '<meta name="robots" content="' . $h($robots) . '">';

            // Canonical
            $tags[] = '<link rel="canonical" href="' . $h($canonical) . '">';

            // Open Graph
            $tags[] = '<meta property="og:type" content="website">';
            $tags[] = '<meta property="og:url" content="' . $h($canonical) . '">';
            if ($ogTitle !== '')  $tags[] = '<meta property="og:title" content="' . $h($ogTitle) . '">';
            if ($ogDesc !== '')   $tags[] = '<meta property="og:description" content="' . $h($ogDesc) . '">';
            if ($ogImg !== '')    $tags[] = '<meta property="og:image" content="' . $h($ogImg) . '">';
            if ($siteName !== '') $tags[] = '<meta property="og:site_name" content="' . $h($siteName) . '">';

            // Twitter Card
            $tags[] = '<meta name="twitter:card" content="summary_large_image">';
            if ($ogTitle !== '') $tags[] = '<meta name="twitter:title" content="' . $h($ogTitle) . '">';
            if ($ogDesc !== '')  $tags[] = '<meta name="twitter:description" content="' . $h($ogDesc) . '">';
            if ($ogImg !== '')   $tags[] = '<meta name="twitter:image" content="' . $h($ogImg) . '">';

            // Verification codes
            $gv = trim((string)($s['google_verify'] ?? ''));
            $bv = trim((string)($s['bing_verify'] ?? ''));
            if ($gv !== '') $tags[] = '<meta name="google-site-verification" content="' . $h($gv) . '">';
            if ($bv !== '') $tags[] = '<meta name="msvalidate.01" content="' . $h($bv) . '">';

            // JSON-LD schema
            if ($jsonLd !== '') {
                $tags[] = '<script type="application/ld+json">' . "\n" . $jsonLd . "\n" . '</script>';
            }

            return implode("\n", $tags);
        } catch (\Throwable) {
            return '';
        }
    }

    // ── Sitemap XML ────────────────────────────────────────────────────────────

    public function generateSitemap(string $appUrl): string
    {
        $appUrl = rtrim($appUrl, '/');
        $h      = static fn(string $v) => htmlspecialchars($v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $entries = [];

        // Custom sitemap entries (highest priority — user-defined)
        foreach ($this->getSitemapUrls() as $row) {
            $url = trim((string)$row['url']);
            if ($url === '') continue;
            if (!str_starts_with($url, 'http')) {
                $url = $appUrl . '/' . ltrim($url, '/');
            }
            $entries[] = [
                'loc'        => $url,
                'priority'   => number_format((float)$row['priority'], 1),
                'changefreq' => (string)$row['changefreq'],
                'lastmod'    => $row['lastmod'] ?? null,
            ];
        }

        // Meta URL entries (all known pages with SEO overrides)
        foreach ($this->allMeta('', 1, 1000) as $row) {
            $fullUrl = $appUrl . $this->normalizePath((string)$row['url_path']);
            $entries[] = [
                'loc'        => $fullUrl,
                'priority'   => '0.5',
                'changefreq' => 'weekly',
                'lastmod'    => date('Y-m-d', strtotime((string)$row['updated_at'])),
            ];
        }

        // Deduplicate by loc
        $seen = [];
        $unique = [];
        foreach ($entries as $e) {
            if (!isset($seen[$e['loc']])) {
                $seen[$e['loc']] = true;
                $unique[] = $e;
            }
        }

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($unique as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . $h((string)$u['loc']) . "</loc>\n";
            $xml .= '    <priority>' . $h((string)$u['priority']) . "</priority>\n";
            $xml .= '    <changefreq>' . $h((string)$u['changefreq']) . "</changefreq>\n";
            if (!empty($u['lastmod'])) {
                $xml .= '    <lastmod>' . $h((string)$u['lastmod']) . "</lastmod>\n";
            }
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        return $xml;
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    public function normalizePath(string $path): string
    {
        $path = (string) strtok($path, '?');
        return '/' . ltrim($path, '/');
    }
}
