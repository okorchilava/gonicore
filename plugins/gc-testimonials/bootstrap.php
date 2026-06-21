<?php

declare(strict_types=1);

/**
 * Plugin Name: GC Testimonials
 * Description: Collect, moderate and showcase customer reviews via shortcodes.
 * Version:     1.0.0
 * Author:      GoniCore
 *
 * Shortcodes (place inside any page/post content):
 *   [gc_testimonials id="slug" limit="12"]   — review grid
 *   [gc_testimonials_slider id="slug"]        — auto-playing slider
 *   [gc_testimonial_form id="slug"]           — moderated submission form
 *
 * Scope from PluginLoader: $router, $container, $hooks, $pluginDir
 */

use GCTestimonials\TestimonialsAdmin;
use GCTestimonials\TestimonialsFrontend;
use GCTestimonials\TestimonialsService;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Language\LanguageRepository;
use GoniCore\Modules\Language\LanguageService;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;
use GoniCore\Modules\Notifications\NotificationService;

// ── Autoloader ─────────────────────────────────────────────────────────────────
spl_autoload_register(static function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GCTestimonials\\')) return;
    $rel  = substr($class, strlen('GCTestimonials\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── Self-migration: create tables if missing (CREATE TABLE IF NOT EXISTS) ──────────
// Runs regardless of how the plugin became active, so the admin pages never hit
// a "table doesn't exist" error.
try {
    $conn = $container->get(Connection::class);
    if (empty($conn->query("SHOW TABLES LIKE 'gc_testimonials'"))) {
        (require $pluginDir . '/database/migration.php')->up($conn);
    }
} catch (\Throwable) {}

// ── DI bindings ──────────────────────────────────────────────────────────────────
$container->bind(TestimonialsService::class, static fn ($c) => new TestimonialsService(
    $c->get(Connection::class),
));

$container->bind(TestimonialsFrontend::class, static fn ($c) => new TestimonialsFrontend(
    $c->get(TestimonialsService::class),
    $c->get(SessionManager::class),
));

$container->bind(TestimonialsAdmin::class, static fn ($c) => new TestimonialsAdmin(
    $c->get(LoginService::class),
    $c->get(SessionManager::class),
    $c->get(TestimonialsService::class),
    $c->get(LanguageService::class),
    $c->get(LanguageRepository::class),
    $c->get(NotificationService::class),
    $c->get(QueryBuilder::class),
    $c->get(HookManager::class),
));

// ── Admin routes ─────────────────────────────────────────────────────────────────
$router->group('/manage', static function ($router): void {
    $router->get('/testimonials',                  [TestimonialsAdmin::class, 'index']);
    $router->post('/testimonials/save',            [TestimonialsAdmin::class, 'save']);
    $router->post('/testimonials/delete',          [TestimonialsAdmin::class, 'delete']);
    $router->post('/testimonials/toggle',          [TestimonialsAdmin::class, 'toggle']);
    $router->post('/testimonials/campaign/save',   [TestimonialsAdmin::class, 'saveCampaign']);
    $router->post('/testimonials/campaign/delete', [TestimonialsAdmin::class, 'deleteCampaign']);
});

// ── Frontend: public review submission (AJAX) ────────────────────────────────────
$router->post('/gc-testimonials/submit', [TestimonialsFrontend::class, 'submit']);

// ── Frontend: render shortcodes inside page/post content ─────────────────────────
gc_filter('the_content', static function (string $html) use ($container): string {
    return $container->get(TestimonialsFrontend::class)->process($html);
}, 15);

// ── Admin sidebar nav entry ──────────────────────────────────────────────────────
gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $cls = $activeNav === 'testimonials' ? 'active' : '';
    echo '<li><a href="' . htmlspecialchars($base . '/manage/testimonials', ENT_QUOTES) . '" class="' . $cls . '">'
       . '<span class="nav-icon material-symbols-outlined">reviews</span> GC Testimonials'
       . '</a></li>';
}, 60);
