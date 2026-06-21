<?php

declare(strict_types=1);

use GoniTotp\TotpService;
use GoniTotp\TwofaController;
use GoniCore\Core\Config\Config;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;

// ── Autoloader ────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GoniTotp\\')) return;
    $rel  = substr($class, strlen('GoniTotp\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DB migration ──────────────────────────────────────────────────────────────

try {
    $conn = $container->get(Connection::class);
    $cols = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'totp_secret'");
    if ((int)($cols[0]['cnt'] ?? 0) === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── DI bindings ───────────────────────────────────────────────────────────────

$container->singleton(TotpService::class, static fn(): TotpService => new TotpService());

$container->bind(
    TwofaController::class,
    static fn($c) => new TwofaController(
        $c->get(TotpService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(SessionManager::class),
        $c->get(HookManager::class),
        (string) $c->get(Config::class)->get('app.name', 'GoniCore'),
    )
);

// ── Routes ────────────────────────────────────────────────────────────────────

$router->get('/manage/2fa',          [TwofaController::class, 'manageForm']);
$router->get('/manage/2fa/qr',       [TwofaController::class, 'qrImage']);
$router->get('/manage/2fa/setup',    [TwofaController::class, 'setupForm']);
$router->post('/manage/2fa/enable',  [TwofaController::class, 'enable']);
$router->post('/manage/2fa/disable', [TwofaController::class, 'disable']);
$router->get('/2fa/verify',          [TwofaController::class, 'verifyForm']);
$router->post('/2fa/verify',         [TwofaController::class, 'verifySubmit']);

// ── Login intercept: pause session if user has 2FA ───────────────────────────

gc_on('login.success', static function (int $userId) use ($container): void {
    $qb   = $container->get(QueryBuilder::class);
    $user = $qb->table('users')->where('id', '=', $userId)->first();

    if (empty($user['totp_enabled'])) {
        return;
    }

    $session = $container->get(SessionManager::class);
    $session->clearUserId();
    $session->start();
    $_SESSION['gc_2fa_pending_uid'] = $userId;
}, 10);

gc_filter('login.redirect', static function (string $redirect, int $userId, string $basePath) use ($container): string {
    $session = $container->get(SessionManager::class);
    $session->start();

    if (empty($_SESSION['gc_2fa_pending_uid'])) {
        return $redirect;
    }

    $_SESSION['gc_2fa_redirect'] = $redirect;
    return $basePath . '/2fa/verify';
}, 10);

// ── Profile page card ─────────────────────────────────────────────────────────

gc_on('manage.profile.cards', static function (array $user, string $base) use ($container): void {
    $qb      = $container->get(QueryBuilder::class);
    $row     = $qb->table('users')->where('id', '=', (int)($user['id'] ?? 0))->first();
    $enabled = !empty($row['totp_enabled']);
    $href    = htmlspecialchars($base . '/manage/2fa', ENT_QUOTES);
    $setupHref = htmlspecialchars($base . '/manage/2fa/setup', ENT_QUOTES);
    ?>
    <div class="card">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between">
            <h3>🔐 Two-Factor Authentication</h3>
            <span class="badge <?= $enabled ? 'published' : 'draft' ?>">
                <?= $enabled ? 'Enabled' : 'Disabled' ?>
            </span>
        </div>
        <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
            <p style="font-size:13.5px;color:var(--muted);margin:0">
                <?= $enabled
                    ? 'Your account is protected. A code from your authenticator app is required on every login.'
                    : 'Add an extra layer of security. Requires an authenticator app (Google Authenticator, Authy, etc.).' ?>
            </p>
            <?php if ($enabled): ?>
            <a href="<?= $href ?>" class="btn btn-ghost" style="flex-shrink:0;white-space:nowrap">Manage 2FA</a>
            <?php else: ?>
            <a href="<?= $setupHref ?>" class="btn btn-primary" style="flex-shrink:0;white-space:nowrap">Enable 2FA</a>
            <?php endif ?>
        </div>
    </div>
    <?php
}, 10);

// ── Sidebar nav ───────────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $active = str_starts_with($activeNav, '2fa') ? 'active' : '';
    echo '<li><a href="' . htmlspecialchars($base . '/manage/2fa', ENT_QUOTES) . '" class="' . $active . '">'
       . '<span class="nav-icon">🔐</span> 2FA Security'
       . '</a></li>';
}, 15);
