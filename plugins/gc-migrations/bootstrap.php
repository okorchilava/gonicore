<?php

declare(strict_types=1);

/**
 * Plugin Name: GC Migrations
 * Description: Import posts, pages, categories and translations from another
 *              GoniCore-compatible database into this site.
 * Version:     1.0.0
 * Author:      GoniCore
 *
 * Security: the importer reads/writes ONLY a fixed allow-list of content tables
 * (categories, posts, post_translations). users / settings / roles and every
 * other system table are never touched. See GCMigrationsService.
 *
 * No own database tables — it writes into the core content tables, so there is
 * nothing to migrate on activation.
 *
 * Scope from PluginLoader: $router, $container, $hooks, $pluginDir
 */

use GCMigrations\GCMigrationsController;
use GCMigrations\GCMigrationsService;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Language\LanguageRepository;
use GoniCore\Modules\Language\LanguageService;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;
use GoniCore\Modules\Notifications\NotificationService;
use GoniCore\Modules\Role\AuthorizationService;

// ── Autoloader ─────────────────────────────────────────────────────────────────
spl_autoload_register(static function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GCMigrations\\')) return;
    $rel  = substr($class, strlen('GCMigrations\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── Plugin-owned translator (for the sidebar label) ─────────────────────────────
$gcm_t = gc_plugin_translator($pluginDir);

// ── DI binding ──────────────────────────────────────────────────────────────────
$container->bind(GCMigrationsController::class, static fn($c) => new GCMigrationsController(
    new GCMigrationsService($c->get(Connection::class)),
    $c->get(QueryBuilder::class),
    $c->get(LoginService::class),
    $c->get(AuthorizationService::class),
    $c->get(HookManager::class),
    $c->get(SessionManager::class),
    $c->get(LanguageService::class),
    $c->get(LanguageRepository::class),
    $c->get(NotificationService::class),
    (string) gc_setting('site_name', 'GoniCore'),
));

// ── Routes (admin only — guarded inside the controller) ─────────────────────────
$router->group('/manage', static function ($router): void {
    $router->get('/migrations',          [GCMigrationsController::class, 'index']);
    $router->post('/migrations/preview', [GCMigrationsController::class, 'preview']);
    $router->post('/migrations/import',  [GCMigrationsController::class, 'import']);
});

// ── Admin sidebar nav entry ─────────────────────────────────────────────────────
gc_on('manage.sidebar.nav', static function (string $base, string $activeNav) use ($gcm_t): void {
    $cls   = $activeNav === 'migrations' ? 'active' : '';
    $label = $gcm_t('title');
    echo '<li><a href="' . htmlspecialchars($base . '/manage/migrations', ENT_QUOTES) . '" class="' . $cls . '">'
       . '<span class="nav-icon material-symbols-outlined">database</span> ' . htmlspecialchars($label)
       . '</a></li>';
}, 60);
