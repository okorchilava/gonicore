<?php
declare(strict_types=1);

use GoniSlider\AdminController;
use GoniSlider\SliderService;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Shortcodes\ShortcodeManager as CoreShortcodeManager;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Shared\Contracts\ShortcodeInterface;

// ── Autoloader ────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GoniSlider\\')) return;
    $rel  = substr($class, strlen('GoniSlider\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── Run DB migration if needed ────────────────────────────────────────────────

try {
    $conn = $container->get(Connection::class);
    $existing = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ps_sliders'");
    if ((int)($existing[0]['cnt'] ?? 0) === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {
    // DB not ready yet — migrations will handle it
}

// ── DI bindings ───────────────────────────────────────────────────────────────

$container->bind(
    SliderService::class,
    static fn($c) => new SliderService($c->get(QueryBuilder::class))
);

$container->bind(
    AdminController::class,
    static fn($c) => new AdminController(
        $c->get(SliderService::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string) $c->get(\GoniCore\Core\Config\Config::class)->get('app.name', 'GoniCore'),
    )
);

// ── Admin routes ──────────────────────────────────────────────────────────────

$router->group('/manage/sliders', static function ($router) use ($container): void {
    $router->get('',                                      [AdminController::class, 'index']);
    $router->post('/create',                              [AdminController::class, 'create']);
    $router->post('/{id}/delete',                         [AdminController::class, 'delete']);
    $router->get('/{id}/edit',                            [AdminController::class, 'edit']);
    $router->post('/{id}/settings',                       [AdminController::class, 'updateSettings']);
    $router->post('/{id}/slides/add',                     [AdminController::class, 'addSlide']);
    $router->post('/slides/{slide_id}/update',            [AdminController::class, 'updateSlide']);
    $router->post('/slides/{slide_id}/delete',            [AdminController::class, 'deleteSlide']);
    $router->post('/slides/{slide_id}/reorder',           [AdminController::class, 'reorderSlides']);
    $router->get('/slides/{slide_id}/layers',             [AdminController::class, 'getLayers']);
    $router->post('/slides/{slide_id}/layers',            [AdminController::class, 'saveLayers']);
});

// Public JSON API
$router->get('/api/v1/sliders/{id}', [AdminController::class, 'apiGet']);

// ── Sidebar nav hook ──────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $isActive = str_starts_with($activeNav, 'slider') ? 'active' : '';
    echo '<li><a href="' . htmlspecialchars($base . '/manage/sliders', ENT_QUOTES) . '" class="' . $isActive . '">'
        . '<span class="nav-icon">🎞</span> GoniSlider'
        . '</a></li>';
}, 10);

// ── Shortcode: [parallax_slider id="1"] ──────────────────────────────────────

$shortcodeMgr = $container->get(CoreShortcodeManager::class);
$shortcodeMgr->register(new class($container, $pluginDir) implements ShortcodeInterface {
    public function __construct(
        private readonly \GoniCore\Core\Container\Container $container,
        private readonly string $pluginDir,
    ) {}

    public function getTag(): string { return 'parallax_slider'; }

    public function render(array $attrs, string $content): string
    {
        $id = (int)($attrs['id'] ?? 0);
        if (!$id) return '';
        /** @var SliderService $svc */
        $svc    = $this->container->get(SliderService::class);
        $slider = $svc->getSlider($id);
        if (!$slider || !$slider['active']) return '';

        $settings   = $svc->decodeSettings((string)$slider['settings']);
        $slides     = $svc->getSlides($id);
        $slidesData = [];
        $pluginDir  = $this->pluginDir;
        foreach ($slides as $slide) {
            if (!$slide['active']) continue;
            $layers = $svc->getLayers((int)$slide['id']);
            foreach ($layers as &$l) {
                $l['settings'] = $svc->decodeLayerSettings((string)$l['settings']);
            }
            unset($l);
            $slide['layers'] = $layers;
            $slidesData[] = $slide;
        }

        ob_start();
        include $pluginDir . '/views/frontend/render.php';
        return (string) ob_get_clean();
    }
});
