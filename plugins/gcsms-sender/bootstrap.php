<?php
declare(strict_types=1);

use GcSmsSender\GcSmsSenderAdminController;
use GcSmsSender\GcSmsSenderService;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;

// ── Autoloader ────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GcSmsSender\\')) return;
    $rel  = substr($class, strlen('GcSmsSender\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DB Migration ──────────────────────────────────────────────────────────────

try {
    $conn = $container->get(Connection::class);
    $rows = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gcsmssender_settings'"
    );
    if ((int) ($rows[0]['cnt'] ?? 0) === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── DI Bindings ───────────────────────────────────────────────────────────────

$container->singleton(GcSmsSenderService::class,
    static fn($c) => new GcSmsSenderService($c->get(QueryBuilder::class))
);

$container->bind(GcSmsSenderAdminController::class,
    static fn($c) => new GcSmsSenderAdminController(
        $c->get(GcSmsSenderService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string) $c->get(\GoniCore\Core\Config\Config::class)->get('app.name', 'GoniCore'),
    )
);

// ── Admin Routes ──────────────────────────────────────────────────────────────

$router->group('/manage/gcsmssender', static function ($r) use ($container): void {
    // Settings
    $r->get('',           [GcSmsSenderAdminController::class, 'settings']);
    $r->get('/settings',  [GcSmsSenderAdminController::class, 'settings']);
    $r->post('/settings', [GcSmsSenderAdminController::class, 'settingsSave']);
    // Send
    $r->get('/send',      [GcSmsSenderAdminController::class, 'sendForm']);
    $r->post('/send',     [GcSmsSenderAdminController::class, 'sendPost']);
    // Logs
    $r->get('/logs',           [GcSmsSenderAdminController::class, 'logs']);
    $r->post('/logs/clear',    [GcSmsSenderAdminController::class, 'logsClear']);
    $r->get('/logs/check',     [GcSmsSenderAdminController::class, 'statusCheck']);
});

// ── Sidebar Nav Hook ──────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $h     = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
    $isAct = str_starts_with($activeNav, 'gcsmssender');
    $open  = $isAct ? ' open' : '';
    $sub   = static function (string $url, string $icon, string $label, string $key) use ($h, $activeNav): string {
        $cls = $activeNav === $key ? ' active' : '';
        return '<li class="nav-sub"><a href="' . $h($url) . '" class="' . $cls . '">'
             . '<span class="nav-icon">' . $icon . '</span> ' . $label . '</a></li>';
    };
    echo '<li>'
       . '<div class="nav-parent-toggle' . $open . '" onclick="navToggle(this)">'
       . '<span class="nav-icon">📨</span> GCsmsSender'
       . '<span class="nav-arrow">▾</span>'
       . '</div>'
       . '<ul class="nav-children' . $open . '">'
       . $sub($base . '/manage/gcsmssender/settings', '⚙',  'Settings', 'gcsmssender-settings')
       . $sub($base . '/manage/gcsmssender/send',     '✉',  'Send SMS', 'gcsmssender-send')
       . $sub($base . '/manage/gcsmssender/logs',     '📋', 'Logs',     'gcsmssender-logs')
       . '</ul>'
       . '</li>';
}, 40);
