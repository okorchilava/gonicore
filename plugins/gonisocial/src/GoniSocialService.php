<?php
declare(strict_types=1);

namespace GoniSocial;

use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;

final class GoniSocialService
{
    private static ?self   $instance = null;
    private static string  $basePath = '';
    private ?array         $settingsCache = null;

    // ── Network metadata ───────────────────────────────────────────────────────

    public const SHARE_NETWORKS = ['facebook','twitter','whatsapp','telegram','linkedin','reddit','viber','pinterest','copy'];

    public const PROFILE_NETWORKS = [
        'facebook'  => ['name' => 'Facebook',   'color' => '#1877F2'],
        'instagram' => ['name' => 'Instagram',  'color' => '#E4405F'],
        'twitter'   => ['name' => 'Twitter / X','color' => '#000000'],
        'linkedin'  => ['name' => 'LinkedIn',   'color' => '#0A66C2'],
        'youtube'   => ['name' => 'YouTube',    'color' => '#FF0000'],
        'tiktok'    => ['name' => 'TikTok',     'color' => '#010101'],
        'telegram'  => ['name' => 'Telegram',   'color' => '#2AABEE'],
        'whatsapp'  => ['name' => 'WhatsApp',   'color' => '#25D366'],
        'pinterest' => ['name' => 'Pinterest',  'color' => '#E60023'],
        'reddit'    => ['name' => 'Reddit',     'color' => '#FF4500'],
        'github'    => ['name' => 'GitHub',     'color' => '#181717'],
        'viber'     => ['name' => 'Viber',      'color' => '#7360F2'],
    ];

    private const NETWORK_COLORS = [
        'facebook'  => '#1877F2',
        'twitter'   => '#000000',
        'whatsapp'  => '#25D366',
        'telegram'  => '#2AABEE',
        'linkedin'  => '#0A66C2',
        'reddit'    => '#FF4500',
        'viber'     => '#7360F2',
        'pinterest' => '#E60023',
        'instagram' => '#E4405F',
        'youtube'   => '#FF0000',
        'tiktok'    => '#010101',
        'github'    => '#181717',
        'copy'      => '#475569',
    ];

    private const SHARE_URLS = [
        'facebook'  => 'https://www.facebook.com/sharer/sharer.php?u={URL}',
        'twitter'   => 'https://twitter.com/intent/tweet?url={URL}&text={TITLE}',
        'whatsapp'  => 'https://api.whatsapp.com/send?text={TITLE}+{URL}',
        'telegram'  => 'https://t.me/share/url?url={URL}&text={TITLE}',
        'linkedin'  => 'https://www.linkedin.com/sharing/share-offsite/?url={URL}',
        'reddit'    => 'https://www.reddit.com/submit?url={URL}&title={TITLE}',
        'viber'     => 'viber://forward?text={TITLE}%20{URL}',
        'pinterest' => 'https://pinterest.com/pin/create/button/?url={URL}&description={TITLE}',
    ];

    /** SVG path data — fill="currentColor", viewBox="0 0 24 24" */
    private const SVG_PATHS = [
        'facebook'  => '<path d="M22 12c0-5.52-4.48-10-10-10S2 6.48 2 12c0 4.84 3.44 8.87 8 9.8V15H8v-3h2V9.5C10 7.57 11.57 6 13.5 6H16v3h-2c-.55 0-1 .45-1 1v2h3v3h-3v6.95c5.05-.5 9-4.76 9-9.95z"/>',
        'twitter'   => '<path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>',
        'instagram' => '<path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>',
        'whatsapp'  => '<path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413z"/>',
        'telegram'  => '<path d="M12 0C5.373 0 0 5.373 0 12s5.373 12 12 12 12-5.373 12-12S18.627 0 12 0zm5.894 8.221-1.97 9.28c-.145.658-.537.818-1.084.508l-3-2.21-1.447 1.394c-.16.16-.295.295-.605.295l.213-3.053 5.56-5.023c.242-.213-.054-.333-.373-.12L7.48 13.801l-2.95-.924c-.641-.201-.654-.641.136-.953l11.57-4.461c.535-.194 1.002.131.658 1.158z"/>',
        'linkedin'  => '<path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 0 1-2.063-2.065 2.064 2.064 0 1 1 2.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>',
        'youtube'   => '<path d="M23.495 6.205a3.007 3.007 0 0 0-2.088-2.088c-1.87-.501-9.396-.501-9.396-.501s-7.507-.01-9.396.501A3.007 3.007 0 0 0 .527 6.205a31.247 31.247 0 0 0-.522 5.805 31.247 31.247 0 0 0 .522 5.783 3.007 3.007 0 0 0 2.088 2.088c1.868.502 9.396.502 9.396.502s7.506 0 9.396-.502a3.007 3.007 0 0 0 2.088-2.088 31.247 31.247 0 0 0 .5-5.783 31.247 31.247 0 0 0-.5-5.805zM9.609 15.601V8.408l6.264 3.602z"/>',
        'tiktok'    => '<path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.67a8.18 8.18 0 0 0 4.77 1.52V6.75a4.85 4.85 0 0 1-1-.06z"/>',
        'pinterest' => '<path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.162-.105-.949-.2-2.405.042-3.441.218-.937 1.407-5.965 1.407-5.965s-.359-.719-.359-1.782c0-1.668.967-2.914 2.171-2.914 1.023 0 1.518.769 1.518 1.69 0 1.029-.655 2.568-.994 3.995-.283 1.194.599 2.169 1.777 2.169 2.133 0 3.772-2.249 3.772-5.495 0-2.873-2.064-4.882-5.012-4.882-3.414 0-5.418 2.561-5.418 5.207 0 1.031.397 2.138.893 2.738a.36.36 0 0 1 .083.345l-.333 1.36c-.053.22-.174.267-.402.161-1.499-.698-2.436-2.889-2.436-4.649 0-3.785 2.75-7.262 7.929-7.262 4.163 0 7.398 2.967 7.398 6.931 0 4.136-2.607 7.464-6.227 7.464-1.216 0-2.359-.632-2.75-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24 12.017 24c6.624 0 11.99-5.367 11.99-11.987C24.007 5.367 18.641.001 12.017.001z"/>',
        'reddit'    => '<path d="M12 0A12 12 0 0 0 0 12a12 12 0 0 0 12 12 12 12 0 0 0 12-12A12 12 0 0 0 12 0zm5.01 4.744c.688 0 1.25.561 1.25 1.249a1.25 1.25 0 0 1-2.498.056l-2.597-.547-.8 3.747c1.824.07 3.48.632 4.674 1.488.308-.309.73-.491 1.207-.491.968 0 1.754.786 1.754 1.754 0 .716-.435 1.333-1.01 1.614a3.111 3.111 0 0 1 .042.52c0 2.694-3.13 4.87-7.004 4.87-3.874 0-7.004-2.176-7.004-4.87 0-.183.015-.366.043-.534A1.748 1.748 0 0 1 4.028 12c0-.968.786-1.754 1.754-1.754.463 0 .898.196 1.207.49 1.207-.883 2.878-1.43 4.744-1.487l.885-4.182a.342.342 0 0 1 .14-.197.35.35 0 0 1 .238-.042l2.906.617a1.214 1.214 0 0 1 1.108-.701zM9.25 12C8.561 12 8 12.562 8 13.25c0 .687.561 1.248 1.25 1.248.687 0 1.248-.561 1.248-1.249 0-.688-.561-1.249-1.249-1.249zm5.5 0c-.687 0-1.248.561-1.248 1.25 0 .687.561 1.248 1.249 1.248.688 0 1.249-.561 1.249-1.249 0-.687-.562-1.249-1.25-1.249zm-5.466 3.99a.327.327 0 0 0-.231.094.33.33 0 0 0 0 .463c.842.842 2.484.913 2.961.913.477 0 2.105-.056 2.961-.913a.361.361 0 0 0 .029-.463.33.33 0 0 0-.464 0c-.547.533-1.684.73-2.512.73-.828 0-1.979-.196-2.512-.73a.326.326 0 0 0-.232-.095z"/>',
        'viber'     => '<path d="M11.4.006C9.156.099 3.977 1.33 1.552 6.396.636 8.29.24 10.388.258 13.063c.016 2.16.27 6.38 4.005 7.413v2.853s-.024.613.378.737c.307.094.487-.098.78-.41l1.538-1.761a10.03 10.03 0 0 0 4.93.583l.43-.055c3.898-.44 7.267-2.973 7.685-6.75.467-4.298-.623-8.958-8.605-9.667zm3.706 13.62c-.334.826-1.704 1.643-2.376 1.75-.606.096-1.368.138-2.205-.138-.51-.17-1.161-.396-1.99-.774C6.233 13.05 4.594 10.7 4.47 10.537c-.125-.136-.997-1.329-.997-2.538 0-1.21.634-1.799.859-2.044.225-.246.491-.307.655-.307s.327.006.47.012c.15.006.353-.06.553.423.205.494.697 1.703.758 1.827.06.124.1.27.018.431-.08.16-.121.26-.243.398-.122.139-.257.308-.367.414-.122.118-.25.246-.107.483.143.237.636 1.053 1.366 1.706.94.838 1.732 1.097 1.977 1.22.245.122.389.102.532-.06.143-.164.61-.713.773-.96.163-.244.326-.204.549-.122.223.082 1.416.668 1.66.79.244.122.407.183.467.284.062.103.062.594-.272 1.167z"/>',
        'github'    => '<path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/>',
        'copy'      => '<path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"/>',
    ];

    // ── Constructor / static registry ──────────────────────────────────────────

    public function __construct(
        private readonly QueryBuilder $qb,
        private readonly Connection   $conn,
    ) {}

    public static function register(self $s): void      { self::$instance = $s; }
    public static function getInstance(): ?self          { return self::$instance; }
    public static function setBasePath(string $b): void  { self::$basePath = $b; }
    public static function getBasePath(): string          { return self::$basePath; }

    // ── Settings ───────────────────────────────────────────────────────────────

    /** @return array<string,string> */
    public function getSettings(): array
    {
        if ($this->settingsCache !== null) return $this->settingsCache;
        try {
            $rows = $this->qb->table('gonisocial_settings')->get();
            $this->settingsCache = [];
            foreach ($rows as $r) {
                $this->settingsCache[(string)$r['key']] = (string)$r['value'];
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
            $existing = $this->qb->table('gonisocial_settings')->where('key', '=', $key)->first();
            if ($existing) {
                $this->qb->table('gonisocial_settings')->where('key', '=', $key)->update(['value' => $value]);
            } else {
                $this->qb->table('gonisocial_settings')->insert(['key' => $key, 'value' => $value]);
            }
        } catch (\Throwable) {}
    }

    // ── Profiles ───────────────────────────────────────────────────────────────

    /** @return list<array<string,mixed>> */
    public function activeProfiles(): array
    {
        return $this->qb->table('gonisocial_profiles')
            ->where('active', '=', 1)
            ->orderBy('sort_order', 'ASC')
            ->get();
    }

    /** @return list<array<string,mixed>> */
    public function allProfiles(): array
    {
        return $this->qb->table('gonisocial_profiles')->orderBy('sort_order', 'ASC')->get();
    }

    public function profile(int $id): ?array
    {
        return $this->qb->table('gonisocial_profiles')->where('id', '=', $id)->first() ?: null;
    }

    public function saveProfile(array $data, ?int $id = null): int
    {
        if ($id) {
            $this->qb->table('gonisocial_profiles')->where('id', '=', $id)->update($data);
            return $id;
        }
        $this->qb->table('gonisocial_profiles')->insert($data);
        return (int) $this->conn->pdo()->lastInsertId();
    }

    public function deleteProfile(int $id): void
    {
        $this->qb->table('gonisocial_profiles')->where('id', '=', $id)->delete();
    }

    public function toggleProfile(int $id): void
    {
        $p = $this->profile($id);
        if ($p) {
            $this->qb->table('gonisocial_profiles')
                ->where('id', '=', $id)
                ->update(['active' => (int)$p['active'] ? 0 : 1]);
        }
    }

    // ── Stats ──────────────────────────────────────────────────────────────────

    public function stats(): array
    {
        try {
            $row = $this->conn->pdo()->query("
                SELECT
                    (SELECT COUNT(*) FROM `gonisocial_profiles`)              AS profile_count,
                    (SELECT COUNT(*) FROM `gonisocial_profiles` WHERE active=1) AS active_profile_count
            ")->fetch(\PDO::FETCH_ASSOC);
            return $row ?: [];
        } catch (\Throwable) { return []; }
    }

    // ── OG / Twitter meta rendering ────────────────────────────────────────────

    /**
     * Build all OG + Twitter Card meta tags for the current page.
     *
     * Called from ob_start callback when GoniSEO is NOT installed.
     *
     * @param string $path         URL path, e.g. /about
     * @param string $existingTitle  <title> content already in the page
     * @param string $existingDesc   <meta name="description"> content already in the page
     * @param string $currentUrl     Full canonical URL of the current page
     */
    public function renderOgTags(
        string $path,
        string $existingTitle,
        string $existingDesc,
        string $currentUrl,
    ): string {
        try {
            $s  = $this->getSettings();
            $h  = static fn(string $v) => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
            $si = $s['og_site_name'] ?? '';
            $di = trim((string)($s['og_default_image'] ?? ''));
            $tc = trim((string)($s['twitter_card'] ?? 'summary_large_image'));
            $th = ltrim(trim((string)($s['twitter_handle'] ?? '')), '@');
            $fi = trim((string)($s['facebook_app_id'] ?? ''));
            $ot = trim((string)($s['og_type'] ?? 'website'));

            $tags   = [];
            $tags[] = '<!-- GoniSocial OG -->';
            $tags[] = '<meta property="og:type" content="' . $h($ot) . '">';
            $tags[] = '<meta property="og:url" content="' . $h($currentUrl) . '">';
            if ($existingTitle !== '') $tags[] = '<meta property="og:title" content="' . $h($existingTitle) . '">';
            if ($existingDesc !== '')  $tags[] = '<meta property="og:description" content="' . $h($existingDesc) . '">';
            if ($di !== '')            $tags[] = '<meta property="og:image" content="' . $h($di) . '">';
            if ($si !== '')            $tags[] = '<meta property="og:site_name" content="' . $h($si) . '">';
            if ($fi !== '')            $tags[] = '<meta property="fb:app_id" content="' . $h($fi) . '">';

            // Twitter Card
            $tags[] = '<meta name="twitter:card" content="' . $h($tc) . '">';
            if ($th !== '') $tags[] = '<meta name="twitter:site" content="@' . $h($th) . '">';
            if ($existingTitle !== '') $tags[] = '<meta name="twitter:title" content="' . $h($existingTitle) . '">';
            if ($existingDesc !== '')  $tags[] = '<meta name="twitter:description" content="' . $h($existingDesc) . '">';
            if ($di !== '')            $tags[] = '<meta name="twitter:image" content="' . $h($di) . '">';

            return implode("\n", $tags);
        } catch (\Throwable) {
            return '';
        }
    }

    // ── Share buttons rendering ────────────────────────────────────────────────

    /**
     * Build the share-buttons widget HTML (CSS + HTML + minimal JS).
     * Injected before </body> by the ob_start callback.
     */
    public function renderShareButtons(string $currentUrl, string $pageTitle): string
    {
        try {
            $s          = $this->getSettings();
            $position   = (string)($s['share_position'] ?? 'floating-left');
            $hideOnMob  = ($s['share_hide_mobile'] ?? '0') === '1';

            $rawNetworks = array_filter(
                array_map('trim', explode(',', $s['share_networks'] ?? 'facebook,twitter,whatsapp,telegram')),
                static fn($n) => $n !== '' && (isset(self::SHARE_URLS[$n]) || $n === 'copy')
            );
            if (empty($rawNetworks)) return '';

            $eu = urlencode($currentUrl);
            $et = urlencode($pageTitle);

            // Position-dependent styles
            [$posStyle, $btnRadius] = match ($position) {
                'floating-left'  => ['left:0;top:50%;transform:translateY(-50%);flex-direction:column', '0 6px 6px 0'],
                'floating-right' => ['right:0;top:50%;transform:translateY(-50%);flex-direction:column', '6px 0 0 6px'],
                'bottom-bar'     => ['bottom:0;left:0;right:0;justify-content:center;flex-direction:row;padding:6px', '8px'],
                default          => ['display:none', '6px'],
            };
            $mobileCss = $hideOnMob ? '@media(max-width:640px){#gsc-sw{display:none!important}}' : '';

            // Build buttons
            $buttons = '';
            foreach ($rawNetworks as $network) {
                $color  = self::NETWORK_COLORS[$network] ?? '#475569';
                $svgEl  = $this->svgIcon($network);
                $br     = 'border-radius:' . $btnRadius;

                if ($network === 'copy') {
                    $encodedUrl = htmlspecialchars($currentUrl, ENT_QUOTES);
                    $buttons .= "<button onclick=\"gscCopy(this)\" data-url=\"{$encodedUrl}\" class=\"gsc-btn\" style=\"background:{$color};{$br}\" title=\"ლინკის კოპირება\">{$svgEl}</button>";
                } else {
                    $shareUrl   = str_replace(['{URL}', '{TITLE}'], [$eu, $et], self::SHARE_URLS[$network]);
                    $networkName = ucfirst($network);
                    $safeUrl    = htmlspecialchars($shareUrl, ENT_QUOTES);
                    $buttons .= "<a href=\"{$safeUrl}\" target=\"_blank\" rel=\"noopener noreferrer\" class=\"gsc-btn\" style=\"background:{$color};{$br}\" title=\"{$networkName}-ზე გაზიარება\">{$svgEl}</a>";
                }
            }

            return <<<HTML

            <style id="gsc-style">
            #gsc-sw{position:fixed;z-index:9998;display:flex;gap:3px;{$posStyle}}
            .gsc-btn{display:flex;align-items:center;justify-content:center;width:44px;height:40px;color:#fff;text-decoration:none;transition:opacity .15s,transform .12s;border:none;cursor:pointer;padding:0;line-height:0}
            .gsc-btn:hover{opacity:.85;transform:scale(1.06)}
            .gsc-btn svg{width:20px;height:20px;flex-shrink:0}
            {$mobileCss}
            </style>
            <div id="gsc-sw" role="complementary" aria-label="სოც. გაზიარება">
            {$buttons}
            </div>
            <script>
            function gscCopy(b){var u=b.dataset.url||location.href,o=b.style.background;
            if(navigator.clipboard){navigator.clipboard.writeText(u).then(function(){b.style.background='#10b981';setTimeout(function(){b.style.background=o;},1500)});}
            else{var t=document.createElement('textarea');t.value=u;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);b.style.background='#10b981';setTimeout(function(){b.style.background=o;},1500);}
            }
            </script>
            HTML;
        } catch (\Throwable) {
            return '';
        }
    }

    // ── Follow buttons (for use in themes) ────────────────────────────────────

    /**
     * Render follow-us social buttons for active profiles.
     *
     * @param string $style  'icon-label' | 'icon-only' | 'label-only'
     */
    public function renderFollowButtons(string $style = 'icon-label'): string
    {
        $profiles = $this->activeProfiles();
        if (empty($profiles)) return '';

        $h = static fn(string $v) => htmlspecialchars($v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $html = '<div class="gsc-follow" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">';

        foreach ($profiles as $p) {
            $network = (string)$p['network'];
            $color   = self::NETWORK_COLORS[$network] ?? self::PROFILE_NETWORKS[$network]['color'] ?? '#475569';
            $name    = $h(trim((string)$p['display_name']) ?: (self::PROFILE_NETWORKS[$network]['name'] ?? ucfirst($network)));
            $url     = $h((string)$p['url']);
            $svg     = $this->svgIcon($network, 18);

            $html .= match ($style) {
                'icon-only'  => "<a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\" title=\"{$name}\" style=\"display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;background:{$color};border-radius:50%;color:#fff;text-decoration:none\">{$svg}</a>",
                'label-only' => "<a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"display:inline-flex;align-items:center;background:{$color};color:#fff;border-radius:8px;padding:7px 14px;font-size:13px;font-weight:600;text-decoration:none\">{$name}</a>",
                default      => "<a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\" style=\"display:inline-flex;align-items:center;gap:7px;background:{$color};color:#fff;border-radius:8px;padding:7px 14px;font-size:13px;font-weight:600;text-decoration:none\">{$svg}<span>{$name}</span></a>",
            };
        }

        return $html . '</div>';
    }

    // ── Private helpers ────────────────────────────────────────────────────────

    private function svgIcon(string $network, int $size = 20): string
    {
        $path = self::SVG_PATHS[$network] ?? null;
        if (!$path) {
            // Fallback: bold first letter
            $letter = htmlspecialchars(strtoupper(substr($network, 0, 1)), ENT_QUOTES);
            return "<span style=\"font-size:{$size}px;font-weight:900;line-height:1\">{$letter}</span>";
        }
        return "<svg viewBox=\"0 0 24 24\" width=\"{$size}\" height=\"{$size}\" fill=\"currentColor\" xmlns=\"http://www.w3.org/2000/svg\" aria-hidden=\"true\">{$path}</svg>";
    }
}
