<?php
declare(strict_types=1);

use GCWeather\GCWeatherAdminController;
use GCWeather\GCWeatherService;
use GoniCore\Core\Config\Config;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Shortcodes\ShortcodeManager as CoreShortcodeManager;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Shared\Contracts\ShortcodeInterface;

// ── Autoloader ─────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GCWeather\\')) return;
    $rel  = substr($class, strlen('GCWeather\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DB Migration ───────────────────────────────────────────────────────────────

try {
    /** @var Connection $conn */
    $conn = $container->get(Connection::class);
    $cnt  = $conn->scalar(
        "SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gcweather_locations'"
    );
    if ((int)$cnt === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── DI Bindings ────────────────────────────────────────────────────────────────

$container->singleton(GCWeatherService::class,
    static fn($c) => new GCWeatherService(
        $c->get(QueryBuilder::class),
        $c->get(Connection::class),
    )
);

$container->bind(GCWeatherAdminController::class,
    static fn($c) => new GCWeatherAdminController(
        $c->get(GCWeatherService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string)$c->get(Config::class)->get('app.name', 'GoniCore'),
    )
);

// ── Register singleton instance ────────────────────────────────────────────────

GCWeatherService::register($container->get(GCWeatherService::class));

// ── Shortcode: [gcweather id="1"] / [gcweather id="1" style="full"] ────────────

try {
    $shortcodeMgr = $container->get(CoreShortcodeManager::class);
    $shortcodeMgr->register(new class($container) implements ShortcodeInterface {
        public function __construct(
            private readonly \GoniCore\Core\Container\Container $container
        ) {}

        public function getTag(): string { return 'gcweather'; }

        public function render(array $attrs, string $content): string
        {
            /** @var GCWeatherService $svc */
            $svc   = $this->container->get(GCWeatherService::class);
            $id    = isset($attrs['id']) ? (int)$attrs['id'] : 0;
            $style = in_array($attrs['style'] ?? '', ['card','full','minimal'], true)
                     ? $attrs['style'] : '';
            GCWeatherService::resetAssets();
            return $id > 0 ? $svc->render($id, $style) : '';
        }
    });
} catch (\Throwable) {}

// ── Admin Routes ───────────────────────────────────────────────────────────────

$router->group('/manage/gcweather', static function ($r) use ($container): void {
    $r->get('',                [GCWeatherAdminController::class, 'locations']);
    $r->get('/form',           [GCWeatherAdminController::class, 'locationForm']);
    $r->post('/save',          [GCWeatherAdminController::class, 'locationSave']);
    $r->post('/delete',        [GCWeatherAdminController::class, 'locationDelete']);
    $r->post('/toggle',        [GCWeatherAdminController::class, 'locationToggle']);
    $r->post('/refresh',       [GCWeatherAdminController::class, 'locationRefresh']);
    $r->get('/settings',       [GCWeatherAdminController::class, 'settings']);
    $r->post('/settings',      [GCWeatherAdminController::class, 'settingsSave']);
});

// ── Sidebar Nav ─────────────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $h     = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
    $isAct = str_starts_with($activeNav, 'gcweather');
    $cls   = $isAct ? ' active' : '';
    echo '<li>'
       . '<a href="' . $h($base . '/manage/gcweather') . '" class="' . $cls . '">'
       . '<span class="nav-icon">🌤️</span> GCWeather'
       . '</a>'
       . '</li>';
}, 46);

// ── Global helper ──────────────────────────────────────────────────────────────
//
//   gcweather(1)              → render location #1 with default style
//   gcweather(1, 'full')      → render full widget with forecast
//   gcweather(1, 'minimal')   → inline minimal

if (!function_exists('gcweather')) {
    function gcweather(int $id, string $style = ''): string
    {
        $svc = GCWeatherService::getInstance();
        if (!$svc) return '';
        GCWeatherService::resetAssets();
        return $svc->render($id, $style);
    }
}
