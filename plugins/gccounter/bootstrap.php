<?php
declare(strict_types=1);

use GCCounter\GCCounterAdminController;
use GCCounter\GCCounterService;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Shortcodes\ShortcodeManager as CoreShortcodeManager;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Shared\Contracts\ShortcodeInterface;

// ── Autoloader ────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GCCounter\\')) return;
    $rel  = substr($class, strlen('GCCounter\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DB Migration ───────────────────────────────────────────────────────────────

try {
    $conn = $container->get(Connection::class);
    $rows = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gccounter_groups'"
    );
    if ((int)($rows[0]['cnt'] ?? 0) === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── DI Bindings ───────────────────────────────────────────────────────────────

$container->singleton(GCCounterService::class,
    static fn($c) => new GCCounterService(
        $c->get(QueryBuilder::class),
        $c->get(Connection::class),
    )
);

$container->bind(GCCounterAdminController::class,
    static fn($c) => new GCCounterAdminController(
        $c->get(GCCounterService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string) $c->get(\GoniCore\Core\Config\Config::class)->get('app.name', 'GoniCore'),
    )
);

// ── Register static instance ──────────────────────────────────────────────────

GCCounterService::register($container->get(GCCounterService::class));

// ── Shortcode: [gccounter id="1"] / [gccounter slug="my-slug"] ───────────────

try {
    $shortcodeMgr = $container->get(CoreShortcodeManager::class);
    $shortcodeMgr->register(new class($container) implements ShortcodeInterface {
        public function __construct(
            private readonly \GoniCore\Core\Container\Container $container
        ) {}

        public function getTag(): string { return 'gccounter'; }

        public function render(array $attrs, string $content): string
        {
            /** @var GCCounterService $svc */
            $svc = $this->container->get(GCCounterService::class);
            GCCounterService::resetAssets(); // allow fresh CSS/JS on each shortcode render

            if (isset($attrs['id']) && (int)$attrs['id'] > 0) {
                return $svc->renderById((int) $attrs['id']);
            }
            if (!empty($attrs['slug'])) {
                return $svc->renderBySlug(trim((string) $attrs['slug']));
            }
            return '';
        }
    });
} catch (\Throwable) {}

// ── Admin Routes ──────────────────────────────────────────────────────────────

$router->group('/manage/gccounter', static function ($r) use ($container): void {
    $r->get('',                     [GCCounterAdminController::class, 'counters']);
    $r->get('/form',                [GCCounterAdminController::class, 'counterForm']);
    $r->post('/save',               [GCCounterAdminController::class, 'counterSave']);
    $r->post('/delete',             [GCCounterAdminController::class, 'counterDelete']);
});

// ── Sidebar Nav ───────────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $h     = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
    $isAct = str_starts_with($activeNav, 'gccounter');
    $cls   = $isAct ? ' active' : '';
    echo '<li>'
       . '<a href="' . $h($base . '/manage/gccounter') . '" class="' . $cls . '">'
       . '<span class="nav-icon">🔢</span> GCCounter'
       . '</a>'
       . '</li>';
}, 47);

// ── Global helper ─────────────────────────────────────────────────────────────
//
//   gccounter(1)           → render group by ID
//   gccounter('my-slug')   → render group by slug

if (!function_exists('gccounter')) {
    function gccounter(int|string $idOrSlug): string
    {
        $svc = GCCounterService::getInstance();
        if (!$svc) return '';
        GCCounterService::resetAssets();
        if (is_int($idOrSlug)) return $svc->renderById($idOrSlug);
        return $svc->renderBySlug((string) $idOrSlug);
    }
}
