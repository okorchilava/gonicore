<?php
declare(strict_types=1);

use GoniSocial\GoniSocialAdminController;
use GoniSocial\GoniSocialService;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;

// ── Autoloader ────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GoniSocial\\')) return;
    $rel  = substr($class, strlen('GoniSocial\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DB Migration ───────────────────────────────────────────────────────────────

try {
    $conn = $container->get(Connection::class);
    $rows = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gonisocial_settings'"
    );
    if ((int)($rows[0]['cnt'] ?? 0) === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── App URL + base path ───────────────────────────────────────────────────────

try {
    $_gscAppUrl = (string) $container->get(\GoniCore\Core\Config\Config::class)->get('app.url', '');
    $_gscBase   = rtrim(parse_url($_gscAppUrl, PHP_URL_PATH) ?? '', '/');
} catch (\Throwable) {
    $_gscAppUrl = '';
    $_gscBase   = '';
}
GoniSocialService::setBasePath($_gscBase);

// ── DI Bindings ───────────────────────────────────────────────────────────────

$container->singleton(GoniSocialService::class,
    static fn($c) => new GoniSocialService(
        $c->get(QueryBuilder::class),
        $c->get(Connection::class),
    )
);

$container->bind(GoniSocialAdminController::class,
    static fn($c) => new GoniSocialAdminController(
        $c->get(GoniSocialService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string) $c->get(\GoniCore\Core\Config\Config::class)->get('app.name', 'GoniCore'),
    )
);

// ── Register static instance ──────────────────────────────────────────────────

GoniSocialService::register($container->get(GoniSocialService::class));

// ── Output-buffer injection (non-admin pages only) ───────────────────────────
//
// Load order is alphabetical → goniseo bootstrap runs BEFORE gonisocial.
// ob_start is LIFO: gonisocial callback fires FIRST, goniseo callback SECOND.
//
// Therefore:
//   • GoniSocial injects share buttons before </body>  (GoniSEO never touches </body>)
//   • GoniSocial injects OG/twitter tags ONLY when GoniSEO is absent; if GoniSEO is
//     active it will strip & rewrite them anyway — so we skip the extra work.

$_gscReqPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

if (!str_contains($_gscReqPath, '/manage')) {
    try {
        $_gscSvc = $container->get(GoniSocialService::class);

        if ($_gscSvc->getSetting('enabled', '1') === '1') {
            $_gscOgEnabled    = $_gscSvc->getSetting('og_enabled',    '1') === '1';
            $_gscShareEnabled = $_gscSvc->getSetting('share_enabled', '1') === '1';
            $_gscCapUrl       = $_gscAppUrl;

            ob_start(static function (string $html) use ($_gscSvc, $_gscOgEnabled, $_gscShareEnabled, $_gscCapUrl): string {
                if (!str_contains($html, '</html>') && !str_contains($html, '</body>') && !str_contains($html, '</head>')) {
                    return $html;
                }

                // ── Current page URL ─────────────────────────────────────────
                $proto   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host    = $_SERVER['HTTP_HOST'] ?? '';
                $reqUri  = $_SERVER['REQUEST_URI'] ?? '/';
                $currentUrl = $_gscCapUrl ?: ($proto . '://' . $host . $reqUri);

                // ── OG / Twitter Card injection ──────────────────────────────
                // Skip if GoniSEO is active — it will inject superior per-URL OG tags
                if ($_gscOgEnabled && str_contains($html, '</head>') && !function_exists('goniseo_head')) {
                    // Extract existing <title> and <meta name="description"> for OG
                    $existingTitle = '';
                    if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
                        $existingTitle = html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8');
                    }
                    $existingDesc = '';
                    if (preg_match('/<meta\s[^>]*name\s*=\s*["\']description["\'][^>]*content\s*=\s*["\']([^"\']*)["\'][^>]*>/si', $html, $m2) ||
                        preg_match('/<meta\s[^>]*content\s*=\s*["\']([^"\']*)["\'][^>]*name\s*=\s*["\']description["\'][^>]*>/si', $html, $m2)) {
                        $existingDesc = html_entity_decode($m2[1], ENT_QUOTES, 'UTF-8');
                    }

                    $path   = (string) strtok($reqUri, '?');
                    $ogTags = $_gscSvc->renderOgTags($path, $existingTitle, $existingDesc, $currentUrl);
                    if ($ogTags) {
                        $html = str_ireplace('</head>', $ogTags . "\n</head>", $html);
                    }
                }

                // ── Share buttons injection ──────────────────────────────────
                if ($_gscShareEnabled && str_contains($html, '</body>')) {
                    $path  = (string) strtok($reqUri, '?');
                    $pageTitle = '';
                    if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
                        $pageTitle = html_entity_decode(strip_tags($m[1]), ENT_QUOTES, 'UTF-8');
                    }
                    $shareHtml = $_gscSvc->renderShareButtons($currentUrl, $pageTitle);
                    if ($shareHtml) {
                        $html = str_ireplace('</body>', $shareHtml . "\n</body>", $html);
                    }
                }

                return $html;
            });

            unset($_gscOgEnabled, $_gscShareEnabled, $_gscCapUrl);
        }
        unset($_gscSvc);
    } catch (\Throwable) {}
}
unset($_gscReqPath);

// ── Admin routes ──────────────────────────────────────────────────────────────

$router->group('/manage/gonisocial', static function ($r) use ($container): void {
    $r->get('',                       [GoniSocialAdminController::class, 'dashboard']);
    $r->get('/settings',              [GoniSocialAdminController::class, 'settings']);
    $r->post('/settings/save',        [GoniSocialAdminController::class, 'settingsSave']);
    $r->get('/share',                 [GoniSocialAdminController::class, 'share']);
    $r->post('/share/save',           [GoniSocialAdminController::class, 'shareSave']);
    $r->get('/profiles',              [GoniSocialAdminController::class, 'profiles']);
    $r->get('/profiles/form',         [GoniSocialAdminController::class, 'profileForm']);
    $r->post('/profiles/save',        [GoniSocialAdminController::class, 'profileSave']);
    $r->post('/profiles/delete',      [GoniSocialAdminController::class, 'profileDelete']);
    $r->post('/profiles/toggle',      [GoniSocialAdminController::class, 'profileToggle']);
});

// ── Sidebar nav ───────────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $h     = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
    $isAct = str_starts_with($activeNav, 'gonisocial');
    $open  = $isAct ? ' open' : '';
    $sub   = static function (string $url, string $icon, string $label, string $key) use ($h, $activeNav): string {
        $cls = $activeNav === $key ? ' active' : '';
        return '<li class="nav-sub"><a href="' . $h($url) . '" class="' . $cls . '">'
             . '<span class="nav-icon">' . $icon . '</span> ' . $label . '</a></li>';
    };
    echo '<li>'
       . '<div class="nav-parent-toggle' . $open . '" onclick="navToggle(this)">'
       . '<span class="nav-icon">📱</span> GoniSocial'
       . '<span class="nav-arrow">▾</span>'
       . '</div>'
       . '<ul class="nav-children' . $open . '">'
       . $sub($base . '/manage/gonisocial',           '📊', 'Dashboard',   'gonisocial-dashboard')
       . $sub($base . '/manage/gonisocial/share',      '🔗', 'გაზიარება',   'gonisocial-share')
       . $sub($base . '/manage/gonisocial/profiles',   '👤', 'პროფილები',   'gonisocial-profiles')
       . $sub($base . '/manage/gonisocial/settings',   '⚙',  'პარამეტრები', 'gonisocial-settings')
       . '</ul>'
       . '</li>';
}, 65);

// ── Global helpers (usable in themes) ─────────────────────────────────────────
//
//   gonisocial_share(string $url = '', string $title = '')  → share buttons HTML
//   gonisocial_follow(string $style = 'icon-label')         → follow buttons HTML

if (!function_exists('gonisocial_share')) {
    function gonisocial_share(string $url = '', string $title = ''): string
    {
        $svc = GoniSocialService::getInstance();
        if (!$svc) return '';
        if ($url === '') {
            $proto = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $url   = $proto . '://' . ($_SERVER['HTTP_HOST'] ?? '') . ($_SERVER['REQUEST_URI'] ?? '/');
        }
        return $svc->renderShareButtons($url, $title);
    }
}

if (!function_exists('gonisocial_follow')) {
    function gonisocial_follow(string $style = 'icon-label'): string
    {
        $svc = GoniSocialService::getInstance();
        if (!$svc) return '';
        return $svc->renderFollowButtons($style);
    }
}

unset($_gscBase, $_gscAppUrl);
