<?php

declare(strict_types=1);

/**
 * Goni Builder Plugin — Bootstrap
 *
 * Variables available from PluginLoader (method scope):
 *   $router    — GoniCore\Core\Http\Router
 *   $container — GoniCore\Core\Container\Container
 *   $hooks     — GoniCore\Core\Hooks\HookManager
 *   $pluginDir — absolute path to this plugin's directory
 */

use GoniBuilder\BuilderService;
use GoniBuilder\GoniBuilderController;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Modules\Category\CategoryRepository;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;
use GoniCore\Modules\Theme\ThemeController;

// ── Autoloader ────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    // Namespace: GoniBuilder\  →  pluginDir/src/*.php
    if (!str_starts_with($class, 'GoniBuilder\\')) return;
    $relative = substr($class, strlen('GoniBuilder\\'));
    $file     = $pluginDir . '/src/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($file)) require_once $file;
});

// ── Run DB migration if needed ────────────────────────────────────────────────

try {
    $conn = $container->get(Connection::class);
    // Check if builder columns exist; if not, run migration
    $cols = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'posts' AND COLUMN_NAME = 'builder_data'");
    if ((int)($cols[0]['cnt'] ?? 0) === 0) {
        $migFile = $pluginDir . '/database/migration.php';
        if (is_file($migFile)) {
            $migration = require $migFile;
            $migration->up($conn);
        }
    }
} catch (\Throwable) {
    // Table might not exist yet — migrations will handle it
}

// ── Register DI bindings ──────────────────────────────────────────────────────

$container->bind(
    BuilderService::class,
    static fn($c) => new BuilderService($c->get(QueryBuilder::class)),
);

$container->bind(
    GoniBuilderController::class,
    static fn($c) => new GoniBuilderController(
        $c->get(BuilderService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(CategoryRepository::class),
        $c->get(SessionManager::class),
    ),
);

// ── Register routes ───────────────────────────────────────────────────────────

$router->group('/goni-builder', static function ($router) use ($container): void {
    $router->get('/{id}',          [GoniBuilderController::class, 'edit']);
    $router->post('/{id}/save',    [GoniBuilderController::class, 'save']);
    $router->get('/{id}/preview',  [GoniBuilderController::class, 'preview']);
});

// ── Front-end rendering ───────────────────────────────────────────────────────
// The core stays plugin-agnostic: it calls gc_apply('page.render', null, $post,
// $request) for every page and uses whatever Response a plugin returns. We opt
// in here only for pages that use the "builder" template and have builder data.

gc_filter('page.render', static function ($carry, array $post, $request) use ($container, $pluginDir): mixed {
    if ($carry instanceof \GoniCore\Core\Http\Response) return $carry;              // already handled
    if (($post['template'] ?? '') !== 'builder' || empty($post['builder_data'])) return $carry;

    $root = dirname($pluginDir, 2); // …/GoniCore
    require_once $root . '/themes/default/views/helpers.php';

    /** @var BuilderService $bs */
    $bs       = $container->get(BuilderService::class);
    $settings = $container->get(\GoniCore\Modules\Settings\SettingsService::class);
    $langSvc  = $container->get(\GoniCore\Modules\Language\LanguageService::class);
    $langSvc->boot($request);

    $base        = $request->basePath();
    $siteName    = (string) ($settings->siteName() ?: 'GoniCore');
    $siteTagline = $settings->siteTagline();
    $categories  = $container->get(\GoniCore\Modules\Category\CategoryRepository::class)->findAll();
    $lang        = $langSvc->currentCode();
    $languages   = $langSvc->activeLanguages();
    $langService = $langSvc;

    // Globals the theme partials + BuilderService read.
    global $widgetServiceInstance, $menuServiceInstance, $shortcodeManagerInstance;
    $widgetServiceInstance    = $container->get(\GoniCore\Modules\Widget\WidgetService::class);
    $menuServiceInstance      = $container->get(\GoniCore\Modules\Menu\MenuService::class);
    $shortcodeManagerInstance = $container->get(\GoniCore\Core\Shortcodes\ShortcodeManager::class);

    $builderHtml = $bs->render((string) $post['builder_data'], $base);

    ob_start();
    include $pluginDir . '/views/page_builder.php';
    return \GoniCore\Core\Http\Response::html((string) ob_get_clean());
}, 10);

// ── Inject "Goni Builder" button into page editor topbar ──────────────────────

gc_on('manage.page_form.topbar', static function (array $post, string $base): void {
    if (empty($post['id'])) return;
    echo '<a href="' . htmlspecialchars($base . '/goni-builder/' . (int)$post['id'], ENT_QUOTES)
        . '" class="topbar-btn" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">'
        . '<span class="material-symbols-outlined mi-sm">brush</span> Goni Builder</a>';
}, 10);
