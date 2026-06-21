<?php

declare(strict_types=1);

/**
 * Plugin Name: GC Maintenance Mode
 * Description: Put the site into maintenance mode — visitors see a 503 page while
 *              admins keep full access. Toggle + custom message in Manage → Maintenance.
 * Version:     1.0.0
 * Author:      GoniCore
 *
 * Settings (core `settings` table, no plugin table):
 *   maintenance_enabled '0'|'1' · maintenance_title · maintenance_message
 *
 * Scope from PluginLoader: $router, $container, $hooks, $pluginDir
 */

use GCMaintenance\MaintenanceAdmin;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Language\LanguageRepository;
use GoniCore\Modules\Language\LanguageService;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;
use GoniCore\Modules\Notifications\NotificationService;

// ── Autoloader ─────────────────────────────────────────────────────────────────
spl_autoload_register(static function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GCMaintenance\\')) return;
    $rel  = substr($class, strlen('GCMaintenance\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DI binding ──────────────────────────────────────────────────────────────────
$container->bind(MaintenanceAdmin::class, static fn ($c) => new MaintenanceAdmin(
    $c->get(LoginService::class),
    $c->get(SessionManager::class),
    $c->get(QueryBuilder::class),
    $c->get(HookManager::class),
    $c->get(LanguageService::class),
    $c->get(LanguageRepository::class),
    $c->get(NotificationService::class),
    (string) gc_setting('site_name', 'GoniCore'),
));

// ── Admin routes ─────────────────────────────────────────────────────────────────
$router->group('/manage', static function ($r): void {
    $r->get('/maintenance',  [MaintenanceAdmin::class, 'settings']);
    $r->post('/maintenance', [MaintenanceAdmin::class, 'save']);
});

// ── Sidebar nav (red dot when active) ────────────────────────────────────────────
gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $on  = gc_setting('maintenance_enabled', '0') === '1';
    $cls = $activeNav === 'maintenance' ? 'active' : '';
    $dot = $on
        ? '<span style="margin-left:auto;width:9px;height:9px;border-radius:50%;background:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,.2)"></span>'
        : '';
    echo '<li><a href="' . htmlspecialchars($base . '/manage/maintenance', ENT_QUOTES) . '" class="' . $cls . '" style="display:flex;align-items:center;gap:6px">'
       . '<span class="nav-icon material-symbols-outlined">construction</span> Maintenance' . $dot . '</a></li>';
}, 55);

// ── Banners: admin (below topbar) + front-end (top of site, admins only) ─────────

gc_on('manage.content.top', static function (string $base = ''): void {
    if (gc_setting('maintenance_enabled', '0') !== '1') return;
    $url = htmlspecialchars(rtrim($base, '/') . '/manage/maintenance', ENT_QUOTES);
    echo '<div style="display:flex;align-items:center;gap:10px;background:#fef3c7;border:1px solid #fcd34d;'
       . 'color:#92400e;border-radius:10px;padding:11px 16px;margin-bottom:18px;font-size:13.5px;font-weight:600">'
       . '<span class="material-symbols-outlined" style="font-size:20px">construction</span>'
       . '<span>Maintenance mode is ON — visitors see the maintenance page; you still have full access.</span>'
       . '<a href="' . $url . '" style="margin-left:auto;color:#92400e;font-weight:700;white-space:nowrap">Settings &rarr;</a>'
       . '</div>';
}, 10);

gc_on('theme.body.top', static function (string $base = ''): void {
    if (gc_setting('maintenance_enabled', '0') !== '1') return;
    // Anonymous visitors are 503'd in the gate below; this banner is the heads-up
    // for an admin browsing the still-hidden live site.
    try { if (!gc_is_logged_in()) return; } catch (\Throwable) { return; }
    $url = htmlspecialchars(rtrim($base, '/') . '/manage/maintenance', ENT_QUOTES);
    echo '<div id="gcm-fbar"><span>🛠️ Maintenance mode — the site is hidden from visitors.</span>'
       . '<a href="' . $url . '">Manage</a></div>'
       . '<style>#gcm-fbar{position:fixed;top:0;left:0;right:0;height:40px;z-index:2000;background:#92400e;'
       . 'color:#fff;display:flex;align-items:center;justify-content:center;gap:10px;font-size:13px;font-weight:600;'
       . 'font-family:system-ui,-apple-system,sans-serif;padding:0 16px}'
       . '#gcm-fbar a{color:#fde68a;text-decoration:underline}'
       . '.gc-header{top:40px}.gc-main{padding-top:112px}</style>';
}, 5);

// ── Front-end gate ───────────────────────────────────────────────────────────────
// Runs on every request (plugins boot before dispatch). When maintenance is on,
// anonymous visitors get the 503 page; admin areas, login and logged-in users pass.
if (gc_setting('maintenance_enabled', '0') === '1') {
    $uri        = (string) (parse_url((string) ($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?: '/');
    $publicBase = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');

    $rel = $uri;
    if ($publicBase !== '' && $publicBase !== '.' && str_starts_with($uri, $publicBase)) {
        $rel = substr($uri, strlen($publicBase));
    } else {
        $projectBase = rtrim(dirname($publicBase), '/');
        if ($projectBase !== '' && $projectBase !== '.' && str_starts_with($uri, $projectBase)) {
            $rel = substr($uri, strlen($projectBase));
        }
    }
    $rel = '/' . ltrim($rel, '/');

    $allow = false;
    foreach (['/manage', '/login', '/logout', '/lang'] as $p) {
        if ($rel === $p || str_starts_with($rel, $p . '/')) { $allow = true; break; }
    }

    $loggedIn = false;
    try { $loggedIn = gc_is_logged_in(); } catch (\Throwable) {}

    if (!$allow && !$loggedIn) {
        MaintenanceAdmin::render503();
        exit;
    }
}
