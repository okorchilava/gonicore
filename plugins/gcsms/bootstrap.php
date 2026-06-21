<?php
declare(strict_types=1);

use GcSms\GcSmsAdminController;
use GcSms\GcSmsService;
use GcSms\GcSmsWebhookController;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;

// ── Autoloader ────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GcSms\\')) return;
    $rel  = substr($class, strlen('GcSms\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DB Migration ──────────────────────────────────────────────────────────────

try {
    $conn = $container->get(Connection::class);
    $rows = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gcsms_inbound'"
    );
    if ((int) ($rows[0]['cnt'] ?? 0) === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── DI Bindings ───────────────────────────────────────────────────────────────

$container->singleton(GcSmsService::class,
    static fn($c) => new GcSmsService($c->get(QueryBuilder::class))
);

$container->bind(GcSmsAdminController::class,
    static fn($c) => new GcSmsAdminController(
        $c->get(GcSmsService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string) $c->get(\GoniCore\Core\Config\Config::class)->get('app.name', 'GoniCore'),
    )
);

$container->bind(GcSmsWebhookController::class,
    static fn($c) => new GcSmsWebhookController($c->get(GcSmsService::class))
);

// ── Public Webhook Routes (no login — authenticated by X-Webhook-Token) ─────────

$router->post('/gcsms/webhook/inbound', [GcSmsWebhookController::class, 'inbound']);
$router->post('/gcsms/webhook/status',  [GcSmsWebhookController::class, 'status']);

// ── Admin Routes ──────────────────────────────────────────────────────────────

$router->group('/manage/gcsms', static function ($r) use ($container): void {
    // Settings
    $r->get('',              [GcSmsAdminController::class, 'settings']);
    $r->get('/settings',     [GcSmsAdminController::class, 'settings']);
    $r->post('/settings',    [GcSmsAdminController::class, 'settingsSave']);
    $r->post('/sender',      [GcSmsAdminController::class, 'senderCreate']);
    // Send SMS
    $r->get('/send',         [GcSmsAdminController::class, 'sendForm']);
    $r->post('/send',        [GcSmsAdminController::class, 'sendPost']);
    // OTP test
    $r->get('/otp',          [GcSmsAdminController::class, 'otpForm']);
    $r->post('/otp/send',    [GcSmsAdminController::class, 'otpSend']);
    $r->post('/otp/verify',  [GcSmsAdminController::class, 'otpVerify']);
    // Logs
    $r->get('/logs',         [GcSmsAdminController::class, 'logs']);
    $r->post('/logs/clear',  [GcSmsAdminController::class, 'logsClear']);
    // Inbound replies
    $r->get('/inbound',             [GcSmsAdminController::class, 'inbound']);
    $r->post('/inbound/clear',      [GcSmsAdminController::class, 'inboundClear']);
});

// ── Sidebar Nav Hook ──────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav) use ($container): void {
    $h     = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
    $isSms = str_starts_with($activeNav, 'gcsms');
    $open  = $isSms ? ' open' : '';
    $sub   = static function (string $url, string $icon, string $label, string $key) use ($h, $activeNav): string {
        $cls = $activeNav === $key ? ' active' : '';
        return '<li class="nav-sub"><a href="' . $h($url) . '" class="' . $cls . '">'
             . '<span class="nav-icon">' . $icon . '</span> ' . $label . '</a></li>';
    };

    // Inbound item carries an unread-replies badge.
    $unread = 0;
    try { $unread = $container->get(GcSmsService::class)->unreadInboundCount(); } catch (\Throwable) {}
    $badge = $unread > 0
        ? ' <span style="background:#ef4444;color:#fff;font-size:10px;font-weight:700;border-radius:9px;padding:1px 6px">' . (int) $unread . '</span>'
        : '';
    $inboundCls = $activeNav === 'gcsms-inbound' ? ' active' : '';
    $inbound = '<li class="nav-sub"><a href="' . $h($base . '/manage/gcsms/inbound') . '" class="' . trim($inboundCls) . '" style="display:flex;align-items:center;gap:6px">'
             . '<span class="nav-icon">📥</span> Inbound' . $badge . '</a></li>';

    echo '<li>'
       . '<div class="nav-parent-toggle' . $open . '" onclick="navToggle(this)">'
       . '<span class="nav-icon">📱</span> GCSMS'
       . '<span class="nav-arrow">▾</span>'
       . '</div>'
       . '<ul class="nav-children' . $open . '">'
       . $sub($base . '/manage/gcsms/settings', '⚙',  'Settings', 'gcsms-settings')
       . $sub($base . '/manage/gcsms/send',     '✉',  'Send SMS', 'gcsms-send')
       . $inbound
       . $sub($base . '/manage/gcsms/otp',      '🔑', 'OTP Test', 'gcsms-otp')
       . $sub($base . '/manage/gcsms/logs',     '📋', 'Logs',     'gcsms-logs')
       . '</ul>'
       . '</li>';
}, 30);
