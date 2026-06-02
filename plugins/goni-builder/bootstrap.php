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
    $cols = $conn->query("SHOW COLUMNS FROM `posts` LIKE 'builder_data'");
    if (empty($cols)) {
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
    ),
);

// ── Register routes ───────────────────────────────────────────────────────────

$router->group('/goni-builder', static function ($router) use ($container): void {
    $router->get('/{id}',          [GoniBuilderController::class, 'edit']);
    $router->post('/{id}/save',    [GoniBuilderController::class, 'save']);
    $router->get('/{id}/preview',  [GoniBuilderController::class, 'preview']);
});

// ── Override ThemeController page() — builder template rendering ───────────────

$hooks->addFilter('page.template.builder', static function (array $post, string $base) use ($container): string {
    /** @var BuilderService $bs */
    $bs = $container->get(BuilderService::class);
    return $bs->render((string) ($post['builder_data'] ?? ''), $base);
}, 10);

// ── Inject "Goni Builder" button into page editor topbar ──────────────────────

$hooks->addAction('manage.page_form.topbar', static function (array $post, string $base): void {
    if (empty($post['id'])) return;
    echo '<a href="' . htmlspecialchars($base . '/goni-builder/' . (int)$post['id'], ENT_QUOTES)
        . '" class="topbar-btn" style="background:linear-gradient(135deg,#6366f1,#8b5cf6)">'
        . '🎨 Goni Builder</a>';
}, 10);

// ── Make BuilderService available to ThemeController ──────────────────────────

// ThemeController uses a global to render builder pages; we provide it here.
$hooks->addAction('theme.init', static function () use ($container): void {
    $GLOBALS['goni_builder_service'] = $container->get(BuilderService::class);
}, 10);

$hooks->doAction('theme.init');
