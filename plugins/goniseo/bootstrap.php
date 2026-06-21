<?php
declare(strict_types=1);

use GoniSeo\GoniSeoAdminController;
use GoniSeo\GoniSeoFrontController;
use GoniSeo\GoniSeoService;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;

// ── Autoloader ────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GoniSeo\\')) return;
    $rel  = substr($class, strlen('GoniSeo\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DB Migration ───────────────────────────────────────────────────────────────

try {
    $conn = $container->get(Connection::class);
    $rows = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'goniseo_settings'"
    );
    if ((int)($rows[0]['cnt'] ?? 0) === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── App URL + base path ───────────────────────────────────────────────────────

try {
    $_gseoAppUrl  = (string) $container->get(\GoniCore\Core\Config\Config::class)->get('app.url', '');
    $_gseoBase    = rtrim(parse_url($_gseoAppUrl, PHP_URL_PATH) ?? '', '/');
} catch (\Throwable) {
    $_gseoAppUrl  = '';
    $_gseoBase    = '';
}
GoniSeoService::setBasePath($_gseoBase);

// ── DI Bindings ───────────────────────────────────────────────────────────────

$container->singleton(GoniSeoService::class,
    static fn($c) => new GoniSeoService(
        $c->get(QueryBuilder::class),
        $c->get(Connection::class),
    )
);

$container->bind(GoniSeoAdminController::class,
    static fn($c) => new GoniSeoAdminController(
        $c->get(GoniSeoService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string) $c->get(\GoniCore\Core\Config\Config::class)->get('app.name', 'GoniCore'),
    )
);

// ── Register static instance ──────────────────────────────────────────────────

GoniSeoService::register($container->get(GoniSeoService::class));

// ── Head injection via ob_start (non-admin pages only) ───────────────────────
//
// Runs as an output-buffer callback, modifying the page HTML just before it
// is sent to the client.  Steps:
//   1. Extract the theme's existing <title> content
//   2. Remove tags we'll replace (title, description, robots, OG, twitter, canonical)
//   3. Inject our SEO block just before </head>

$_gseoReqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

if (!str_contains($_gseoReqPath, '/manage')) {
    try {
        $_gseoSvc = $container->get(GoniSeoService::class);

        if ($_gseoSvc->getSetting('enabled', '1') === '1') {
            $_gseoCapUrl = $_gseoAppUrl; // capture for use inside closure

            ob_start(static function (string $html) use ($_gseoSvc, $_gseoCapUrl): string {
                if (!str_contains($html, '</head>')) return $html;

                // Determine path (strip query string)
                $path = (string) strtok($_SERVER['REQUEST_URI'] ?? '/', '?');

                // Capture existing <title> so service can re-use it for format
                $existingTitle = '';
                if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
                    $existingTitle = html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8');
                }

                $tags = $_gseoSvc->renderHeadTags($path, $existingTitle);
                if (!$tags) return $html;

                // Remove tags we're replacing to avoid duplicates
                $html = preg_replace('/<title[^>]*>.*?<\/title>/si', '', $html) ?? $html;
                $html = preg_replace(
                    '/<meta\s[^>]*name\s*=\s*["\'](?:description|keywords|robots)["\'][^>]*\/?>/si',
                    '', $html
                ) ?? $html;
                $html = preg_replace(
                    '/<meta\s[^>]*property\s*=\s*["\']og:[^"\']*["\'][^>]*\/?>/si',
                    '', $html
                ) ?? $html;
                $html = preg_replace(
                    '/<meta\s[^>]*name\s*=\s*["\']twitter:[^"\']*["\'][^>]*\/?>/si',
                    '', $html
                ) ?? $html;
                $html = preg_replace(
                    '/<link\s[^>]*rel\s*=\s*["\']canonical["\'][^>]*\/?>/si',
                    '', $html
                ) ?? $html;

                return str_ireplace('</head>', $tags . "\n</head>", $html);
            });

            unset($_gseoCapUrl);
        }
        unset($_gseoSvc);
    } catch (\Throwable) {}
}
unset($_gseoReqPath);

// ── Public routes ─────────────────────────────────────────────────────────────

$_gseoFront = new GoniSeoFrontController($container->get(GoniSeoService::class), $_gseoAppUrl);
$router->get('/sitemap.xml', [$_gseoFront, 'sitemap']);
$router->get('/robots.txt',  [$_gseoFront, 'robots']);
unset($_gseoFront);

// ── Admin routes ──────────────────────────────────────────────────────────────

$router->group('/manage/goniseo', static function ($r) use ($container): void {
    // Dashboard
    $r->get('',                     [GoniSeoAdminController::class, 'dashboard']);
    // Settings
    $r->get('/settings',            [GoniSeoAdminController::class, 'settings']);
    $r->post('/settings/save',      [GoniSeoAdminController::class, 'settingsSave']);
    // Per-URL meta
    $r->get('/meta',                [GoniSeoAdminController::class, 'metaList']);
    $r->get('/meta/form',           [GoniSeoAdminController::class, 'metaForm']);
    $r->post('/meta/save',          [GoniSeoAdminController::class, 'metaSave']);
    $r->post('/meta/delete',        [GoniSeoAdminController::class, 'metaDelete']);
    // Sitemap
    $r->get('/sitemap',             [GoniSeoAdminController::class, 'sitemapAdmin']);
    $r->get('/sitemap/form',        [GoniSeoAdminController::class, 'sitemapForm']);
    $r->post('/sitemap/save',       [GoniSeoAdminController::class, 'sitemapSave']);
    $r->post('/sitemap/delete',     [GoniSeoAdminController::class, 'sitemapDelete']);
    $r->post('/sitemap/ping',       [GoniSeoAdminController::class, 'sitemapPing']);
    // Robots.txt
    $r->get('/robots',              [GoniSeoAdminController::class, 'robotsAdmin']);
    $r->post('/robots/save',        [GoniSeoAdminController::class, 'robotsSave']);
});

// ── Sidebar nav ───────────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $h     = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
    $isAct = str_starts_with($activeNav, 'goniseo');
    $open  = $isAct ? ' open' : '';
    $sub   = static function (string $url, string $icon, string $label, string $key) use ($h, $activeNav): string {
        $cls = $activeNav === $key ? ' active' : '';
        return '<li class="nav-sub"><a href="' . $h($url) . '" class="' . $cls . '">'
             . '<span class="nav-icon">' . $icon . '</span> ' . $label . '</a></li>';
    };
    echo '<li>'
       . '<div class="nav-parent-toggle' . $open . '" onclick="navToggle(this)">'
       . '<span class="nav-icon">🔍</span> GoniSEO'
       . '<span class="nav-arrow">▾</span>'
       . '</div>'
       . '<ul class="nav-children' . $open . '">'
       . $sub($base . '/manage/goniseo',          '📊', 'Dashboard',   'goniseo-dashboard')
       . $sub($base . '/manage/goniseo/meta',      '🏷',  'Meta Tags',   'goniseo-meta')
       . $sub($base . '/manage/goniseo/sitemap',   '🗺',  'Sitemap',     'goniseo-sitemap')
       . $sub($base . '/manage/goniseo/robots',    '🤖', 'Robots.txt',  'goniseo-robots')
       . $sub($base . '/manage/goniseo/settings',  '⚙',  'პარამეტრები', 'goniseo-settings')
       . '</ul>'
       . '</li>';
}, 55);

// ── Global helpers (usable in themes) ─────────────────────────────────────────
//
//   goniseo_head()         → renders all <head> SEO tags for current URL
//   goniseo_meta()         → returns meta row array for current URL
//   goniseo_meta('/about') → returns meta row for a specific path
//

if (!function_exists('goniseo_head')) {
    function goniseo_head(): string
    {
        $svc = GoniSeoService::getInstance();
        if (!$svc) return '';
        $path = (string) strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        return $svc->renderHeadTags($path);
    }
}

if (!function_exists('goniseo_meta')) {
    /**
     * @return array<string,mixed>  meta row (may be empty array if no override set)
     */
    function goniseo_meta(string $path = ''): array
    {
        $svc = GoniSeoService::getInstance();
        if (!$svc) return [];
        if ($path === '') $path = (string) strtok($_SERVER['REQUEST_URI'] ?? '/', '?');
        return $svc->getMeta($path) ?? [];
    }
}

unset($_gseoBase, $_gseoAppUrl);
