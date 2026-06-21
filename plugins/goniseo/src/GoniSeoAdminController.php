<?php
declare(strict_types=1);

namespace GoniSeo;

use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Modules\Login\LoginService;

final class GoniSeoAdminController
{
    private const DEFAULTS = [
        'enabled'             => '1',
        'title_format'        => '{title} | {site_name}',
        'site_name'           => '',
        'default_description' => '',
        'default_keywords'    => '',
        'default_og_image'    => '',
        'default_robots'      => 'index,follow',
        'google_verify'       => '',
        'bing_verify'         => '',
        'robots_txt'          => "User-agent: *\nAllow: /",
        'manage_robots'       => '1',
    ];

    public function __construct(
        private readonly GoniSeoService $svc,
        private readonly QueryBuilder   $qb,
        private readonly LoginService   $auth,
        private readonly HookManager    $hooks,
        private readonly string         $siteName = 'GoniCore',
    ) {}

    // ── Dashboard ──────────────────────────────────────────────────────────────

    public function dashboard(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('dashboard', [
            'base'       => $r->basePath(),
            'stats'      => $this->svc->stats(),
            'settings'   => array_merge(self::DEFAULTS, $this->svc->getSettings()),
            'recentMeta' => $this->svc->allMeta('', 1, 6),
        ]);
    }

    // ── Settings ───────────────────────────────────────────────────────────────

    public function settings(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        return $this->renderPage('settings', [
            'base'     => $r->basePath(),
            'settings' => array_merge(self::DEFAULTS, $this->svc->getSettings()),
            'saved'    => $r->query('saved') === '1',
        ]);
    }

    public function settingsSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        foreach (array_keys(self::DEFAULTS) as $key) {
            if (in_array($key, ['enabled', 'manage_robots'], true)) {
                $value = (string) $r->post($key, '0') === '1' ? '1' : '0';
            } elseif ($key === 'robots_txt') {
                // Keep newlines; allow empty
                $value = (string) $r->post($key, self::DEFAULTS[$key]);
            } else {
                $value = trim((string) $r->post($key, ''));
                if ($value === '' && isset(self::DEFAULTS[$key])) $value = self::DEFAULTS[$key];
            }
            $this->svc->saveSetting($key, $value);
        }
        return Response::redirect($r->basePath() . '/manage/goniseo/settings?saved=1');
    }

    // ── Meta list ──────────────────────────────────────────────────────────────

    public function metaList(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $search = trim((string)($r->query('q') ?? ''));
        $page   = max(1, (int)($r->query('page') ?? 1));
        $per    = 25;
        return $this->renderPage('meta_list', [
            'base'    => $r->basePath(),
            'rows'    => $this->svc->allMeta($search, $page, $per),
            'total'   => $this->svc->countMeta($search),
            'page'    => $page,
            'per'     => $per,
            'search'  => $search,
            'saved'   => $r->query('saved') === '1',
            'deleted' => $r->query('deleted') === '1',
        ]);
    }

    // ── Meta form ──────────────────────────────────────────────────────────────

    public function metaForm(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id     = $r->query('id') ? (int) $r->query('id') : null;
        $row    = $id ? $this->svc->meta($id) : null;
        return $this->renderPage('meta_form', [
            'base'         => $r->basePath(),
            'row'          => $row,
            'isEdit'       => $id !== null && $row !== null,
            'prefillPath'  => trim((string)($r->query('path') ?? '')),
            'robotsOptions'=> ['index,follow', 'noindex,follow', 'noindex,nofollow', 'index,nofollow'],
        ]);
    }

    public function metaSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id   = $r->post('id') ? (int) $r->post('id') : null;
        $path = $this->svc->normalizePath(trim((string) $r->post('url_path', '/')));
        $data = [
            'url_path'       => $path,
            'title'          => trim((string) $r->post('title',          '')),
            'description'    => trim((string) $r->post('description',    '')),
            'keywords'       => trim((string) $r->post('keywords',       '')),
            'og_title'       => trim((string) $r->post('og_title',       '')),
            'og_description' => trim((string) $r->post('og_description', '')),
            'og_image'       => trim((string) $r->post('og_image',       '')),
            'canonical'      => trim((string) $r->post('canonical',      '')),
            'robots'         => trim((string) $r->post('robots',         '')),
            'json_ld'        => trim((string) $r->post('json_ld',        '')),
        ];
        $this->svc->saveMeta($data, $id);
        return Response::redirect($r->basePath() . '/manage/goniseo/meta?saved=1');
    }

    public function metaDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int) $r->post('id', '0');
        if ($id > 0) $this->svc->deleteMeta($id);
        return Response::redirect($r->basePath() . '/manage/goniseo/meta?deleted=1');
    }

    // ── Sitemap admin ──────────────────────────────────────────────────────────

    public function sitemapAdmin(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $proto      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host       = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $sitemapUrl = $proto . '://' . $host . $r->basePath() . '/sitemap.xml';
        return $this->renderPage('sitemap', [
            'base'       => $r->basePath(),
            'urls'       => $this->svc->getSitemapUrls(),
            'sitemapUrl' => $sitemapUrl,
            'saved'      => $r->query('saved') === '1',
            'deleted'    => $r->query('deleted') === '1',
            'pinged'     => $r->query('pinged'),
        ]);
    }

    public function sitemapForm(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id  = $r->query('id') ? (int) $r->query('id') : null;
        $row = $id ? $this->svc->getSitemapUrl($id) : null;
        return $this->renderPage('sitemap_form', [
            'base'   => $r->basePath(),
            'row'    => $row,
            'isEdit' => $id !== null && $row !== null,
        ]);
    }

    public function sitemapSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id  = $r->post('id') ? (int) $r->post('id') : null;
        $pri = trim((string) $r->post('priority', '0.5'));
        if (!is_numeric($pri)) $pri = '0.5';
        $pri     = max(0.0, min(1.0, (float) $pri));
        $lastmod = trim((string) $r->post('lastmod', ''));
        $data = [
            'url'        => trim((string) $r->post('url', '')),
            'priority'   => number_format($pri, 1),
            'changefreq' => in_array(
                $cf = trim((string) $r->post('changefreq', 'weekly')),
                ['always','hourly','daily','weekly','monthly','yearly','never'],
                true
            ) ? $cf : 'weekly',
            'lastmod'    => $lastmod !== '' ? $lastmod : null,
        ];
        $this->svc->saveSitemapUrl($data, $id);
        return Response::redirect($r->basePath() . '/manage/goniseo/sitemap?saved=1');
    }

    public function sitemapDelete(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $id = (int) $r->post('id', '0');
        if ($id > 0) $this->svc->deleteSitemapUrl($id);
        return Response::redirect($r->basePath() . '/manage/goniseo/sitemap?deleted=1');
    }

    public function sitemapPing(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $sitemapUrl = trim((string) $r->post('sitemap_url', ''));
        $result     = 'error';

        if (filter_var($sitemapUrl, FILTER_VALIDATE_URL)) {
            // Bing sitemap ping (Google deprecated their ping endpoint)
            $pingUrl = 'https://www.bing.com/ping?sitemap=' . urlencode($sitemapUrl);
            try {
                $ctx  = stream_context_create(['http' => ['timeout' => 6, 'ignore_errors' => true]]);
                $resp = @file_get_contents($pingUrl, false, $ctx);
                $result = ($resp !== false) ? 'ok' : 'error';
            } catch (\Throwable) {
                $result = 'error';
            }
        }

        return Response::redirect($r->basePath() . '/manage/goniseo/sitemap?pinged=' . $result);
    }

    // ── Robots.txt ─────────────────────────────────────────────────────────────

    public function robotsAdmin(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $proto      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host       = (string)($_SERVER['HTTP_HOST'] ?? 'localhost');
        $robotsUrl  = $proto . '://' . $host . $r->basePath() . '/robots.txt';
        return $this->renderPage('robots', [
            'base'      => $r->basePath(),
            'settings'  => array_merge(self::DEFAULTS, $this->svc->getSettings()),
            'robotsUrl' => $robotsUrl,
            'saved'     => $r->query('saved') === '1',
        ]);
    }

    public function robotsSave(Request $r): Response
    {
        if ($rr = $this->guard($r)) return $rr;
        $this->svc->saveSetting(
            'robots_txt',
            (string) $r->post('robots_txt', self::DEFAULTS['robots_txt'])
        );
        $this->svc->saveSetting(
            'manage_robots',
            (string) $r->post('manage_robots', '0') === '1' ? '1' : '0'
        );
        return Response::redirect($r->basePath() . '/manage/goniseo/robots?saved=1');
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    private function guard(Request $r): ?Response
    {
        if (!$this->auth->isLoggedIn()) {
            return Response::redirect($r->basePath() . '/login');
        }
        return null;
    }

    /** @param array<string,mixed> $data */
    private function renderPage(string $view, array $data): Response
    {
        $themeDir = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeDir . '/helpers.php';

        $base     = $data['base'] ?? '';
        $siteName = $this->siteName;
        $hooks    = $this->hooks;

        $userId = $this->auth->currentUserId();
        $user   = $userId
            ? $this->qb->table('users')->where('id', '=', $userId)->first()
            : null;

        $notifList       = [];
        $notifUnread     = 0;
        $panelLangs      = [];
        $currentLangCode = 'en';

        extract($data, EXTR_SKIP);

        ob_start();
        try {
            include __DIR__ . '/../views/admin/' . $view . '.php';
            $content = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        ob_start();
        try {
            include $themeDir . '/manage/layout.php';
            $html = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        return Response::html($html);
    }
}
