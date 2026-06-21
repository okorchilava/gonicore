<?php
declare(strict_types=1);

use GCPopup\GCPopupAdminController;
use GCPopup\GCPopupService;
use GoniCore\Core\Config\Config;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Shortcodes\ShortcodeManager as CoreShortcodeManager;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Shared\Contracts\ShortcodeInterface;

// ── Autoloader ─────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GCPopup\\')) return;
    $rel  = substr($class, strlen('GCPopup\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DB Migration ───────────────────────────────────────────────────────────────

try {
    /** @var Connection $conn */
    $conn = $container->get(Connection::class);
    $cnt  = $conn->scalar(
        "SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gcpopup_popups'"
    );
    if ((int)$cnt === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── DI Bindings ────────────────────────────────────────────────────────────────

$container->singleton(GCPopupService::class,
    static fn($c) => new GCPopupService(
        $c->get(QueryBuilder::class),
        $c->get(Connection::class),
    )
);

$container->bind(GCPopupAdminController::class,
    static fn($c) => new GCPopupAdminController(
        $c->get(GCPopupService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string)$c->get(Config::class)->get('app.name', 'GoniCore'),
    )
);

// ── Register singleton instance ────────────────────────────────────────────────

GCPopupService::register($container->get(GCPopupService::class));

// ── Shortcode: [gcpopup id="1"] / [gcpopup id="1" btn="გახსნა"] ───────────────

try {
    $shortcodeMgr = $container->get(CoreShortcodeManager::class);
    $shortcodeMgr->register(new class($container) implements ShortcodeInterface {
        public function __construct(
            private readonly \GoniCore\Core\Container\Container $container
        ) {}

        public function getTag(): string { return 'gcpopup'; }

        public function render(array $attrs, string $content): string
        {
            /** @var GCPopupService $svc */
            $svc = $this->container->get(GCPopupService::class);
            $id  = isset($attrs['id']) ? (int)$attrs['id'] : 0;
            if ($id <= 0) return '';
            return $svc->renderShortcode($id, $attrs);
        }
    });
} catch (\Throwable) {}

// ── Frontend injection: render all active popups before </body> ────────────────

ob_start(static function (string $buffer) use ($container): string {
    if (!str_contains($buffer, '</body>')) return $buffer;

    // Skip admin pages and the popup API endpoint
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    if (str_contains($uri, '/manage/') || str_contains($uri, '/login')) {
        return $buffer;
    }

    // Skip if logged-in admin (optional — controlled per-popup via target_pages)
    try {
        /** @var LoginService $auth */
        $auth = $container->get(LoginService::class);
        if ($auth->isLoggedIn()) {
            // Still inject, but only non-admin pages can trigger
            // (Admin may want to preview popups on their own site pages)
        }
    } catch (\Throwable) {}

    try {
        /** @var GCPopupService $svc */
        $svc  = $container->get(GCPopupService::class);
        $html = $svc->renderAll();
        if (!$html) return $buffer;
        return str_replace('</body>', $html . '</body>', $buffer);
    } catch (\Throwable) {
        return $buffer;
    }
});

// ── Admin Routes ───────────────────────────────────────────────────────────────

$router->group('/manage/gcpopup', static function ($r) use ($container): void {
    $r->get('',           [GCPopupAdminController::class, 'popups']);
    $r->get('/form',      [GCPopupAdminController::class, 'form']);
    $r->post('/save',     [GCPopupAdminController::class, 'save']);
    $r->post('/delete',   [GCPopupAdminController::class, 'delete']);
    $r->post('/toggle',   [GCPopupAdminController::class, 'toggle']);
});

// ── Sidebar Nav ─────────────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $h     = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
    $isAct = str_starts_with($activeNav, 'gcpopup');
    $cls   = $isAct ? ' active' : '';
    echo '<li>'
       . '<a href="' . $h($base . '/manage/gcpopup') . '" class="' . $cls . '">'
       . '<span class="nav-icon">🪟</span> GCPopup'
       . '</a>'
       . '</li>';
}, 44);

// ── Global helper ──────────────────────────────────────────────────────────────
//
//   gcpopup(1)                    → trigger button (uses popup's btn_text)
//   gcpopup(1, 'გახსნა')          → trigger button with custom label

if (!function_exists('gcpopup')) {
    function gcpopup(int $id, string $btn = ''): string
    {
        $svc = GCPopupService::getInstance();
        if (!$svc) return '';
        return $svc->renderShortcode($id, $btn !== '' ? ['btn' => $btn] : []);
    }
}
