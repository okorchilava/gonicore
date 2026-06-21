<?php
declare(strict_types=1);

use GsAds\GsAdsAdminController;
use GsAds\GsAdsService;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;

// ── Autoloader ─────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GsAds\\')) return;
    $rel  = substr($class, strlen('GsAds\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DB Migration ───────────────────────────────────────────────────────────────

try {
    $conn = $container->get(Connection::class);
    $rows = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gsads_zones'"
    );
    if ((int)($rows[0]['cnt'] ?? 0) === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── Base path for click-tracking URLs ─────────────────────────────────────────
// Derived from app.url config — path component only (e.g. '' or '/goni')

try {
    $appUrl        = (string) $container->get(\GoniCore\Core\Config\Config::class)->get('app.url', '');
    $_gsClickBase  = rtrim(parse_url($appUrl, PHP_URL_PATH) ?? '', '/') . '/gsads/click';
} catch (\Throwable) {
    $_gsClickBase  = '/gsads/click';
}
GsAdsService::setClickBase($_gsClickBase);
unset($_gsClickBase, $appUrl);

// ── DI Bindings ───────────────────────────────────────────────────────────────

$container->singleton(GsAdsService::class,
    static fn($c) => new GsAdsService(
        $c->get(QueryBuilder::class),
        $c->get(Connection::class),
    )
);

$container->bind(GsAdsAdminController::class,
    static fn($c) => new GsAdsAdminController(
        $c->get(GsAdsService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string) $c->get(\GoniCore\Core\Config\Config::class)->get('app.name', 'GoniCore'),
    )
);

// ── Register static service instance (needed by gsads() helper) ────────────────

GsAdsService::register($container->get(GsAdsService::class));

// ── Public Route: click tracker ────────────────────────────────────────────────

$router->get('/gsads/click', [GsAdsAdminController::class, 'click']);

// ── Admin Routes ───────────────────────────────────────────────────────────────

$router->group('/manage/gsads', static function ($r) use ($container): void {
    // Dashboard
    $r->get('', [GsAdsAdminController::class, 'dashboard']);
    // Zones
    $r->get('/zones',          [GsAdsAdminController::class, 'zones']);
    $r->get('/zones/form',     [GsAdsAdminController::class, 'zoneForm']);
    $r->post('/zones/save',    [GsAdsAdminController::class, 'zoneSave']);
    $r->post('/zones/delete',  [GsAdsAdminController::class, 'zoneDelete']);
    $r->post('/zones/toggle',  [GsAdsAdminController::class, 'zoneToggle']);
    // Ads
    $r->get('/ads',            [GsAdsAdminController::class, 'ads']);
    $r->get('/ads/form',       [GsAdsAdminController::class, 'adForm']);
    $r->post('/ads/save',      [GsAdsAdminController::class, 'adSave']);
    $r->post('/ads/delete',    [GsAdsAdminController::class, 'adDelete']);
    $r->post('/ads/toggle',    [GsAdsAdminController::class, 'adToggle']);
});

// ── Sidebar Nav Hook ───────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $h     = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
    $isAct = str_starts_with($activeNav, 'gsads');
    $open  = $isAct ? ' open' : '';
    $sub   = static function (string $url, string $icon, string $label, string $key) use ($h, $activeNav): string {
        $cls = $activeNav === $key ? ' active' : '';
        return '<li class="nav-sub"><a href="' . $h($url) . '" class="' . $cls . '">'
             . '<span class="nav-icon">' . $icon . '</span> ' . $label . '</a></li>';
    };
    echo '<li>'
       . '<div class="nav-parent-toggle' . $open . '" onclick="navToggle(this)">'
       . '<span class="nav-icon">📢</span> GsAds'
       . '<span class="nav-arrow">▾</span>'
       . '</div>'
       . '<ul class="nav-children' . $open . '">'
       . $sub($base . '/manage/gsads',       '📊', 'Dashboard', 'gsads-dashboard')
       . $sub($base . '/manage/gsads/zones', '🗂',  'Zones',     'gsads-zones')
       . $sub($base . '/manage/gsads/ads',   '🖼',  'Ads',       'gsads-ads')
       . '</ul>'
       . '</li>';
}, 45);

// ── Global helper (use in theme templates) ────────────────────────────────────
//
//   gsads('header-banner')       → HTML for 1 randomly-selected eligible ad
//   gsads('sidebar-block', 3)    → up to 3 ads
//   gsads('footer-strip', 0)     → all active ads in zone
//
if (!function_exists('gsads')) {
    function gsads(string $slug, int $limit = 1): string
    {
        return GsAdsService::getInstance()?->renderZone($slug, $limit) ?? '';
    }
}
