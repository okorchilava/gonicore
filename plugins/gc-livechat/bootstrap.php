<?php

declare(strict_types=1);

/**
 * Plugin Name: GC Live Chat
 * Description: AI chat agent (Claude / Gemini / ChatGPT) that answers & triages
 *              visitors, then hands off to a human operator. Floating widget +
 *              operator inbox. Real-time via AJAX polling.
 * Version:     1.0.0
 * Author:      GoniCore
 *
 * Scope from PluginLoader: $router, $container, $hooks, $pluginDir
 */

use GCLiveChat\Ai\AiResponder;
use GCLiveChat\ChatService;
use GCLiveChat\LiveChatAdmin;
use GCLiveChat\LiveChatFrontend;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Language\LanguageRepository;
use GoniCore\Modules\Language\LanguageService;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;
use GoniCore\Modules\Notifications\NotificationService;
use GoniCore\Modules\Settings\SettingsService;

// ── Autoloader ─────────────────────────────────────────────────────────────────
spl_autoload_register(static function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GCLiveChat\\')) return;
    $rel  = substr($class, strlen('GCLiveChat\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── Self-migration: create tables if missing (CREATE TABLE IF NOT EXISTS) ──────────
// Runs regardless of how the plugin became active (panel activation OR a forced
// .disabled removal), so the admin pages never hit a "table doesn't exist" error.
try {
    $conn = $container->get(Connection::class);
    if (empty($conn->query("SHOW TABLES LIKE 'gc_chat_conversations'"))) {
        (require $pluginDir . '/database/migration.php')->up($conn);
    }
} catch (\Throwable) {}

// ── DI bindings ──────────────────────────────────────────────────────────────────
$container->bind(ChatService::class, static fn ($c) => new ChatService(
    $c->get(Connection::class),
));

$container->bind(AiResponder::class, static fn ($c) => new AiResponder(
    $c->get(QueryBuilder::class),
));

$container->bind(LiveChatFrontend::class, static fn ($c) => new LiveChatFrontend(
    $c->get(ChatService::class),
    $c->get(AiResponder::class),
));

$container->bind(LiveChatAdmin::class, static fn ($c) => new LiveChatAdmin(
    $c->get(LoginService::class),
    $c->get(SessionManager::class),
    $c->get(ChatService::class),
    $c->get(AiResponder::class),
    $c->get(SettingsService::class),
    $c->get(LanguageService::class),
    $c->get(LanguageRepository::class),
    $c->get(NotificationService::class),
    $c->get(QueryBuilder::class),
    $c->get(HookManager::class),
));

// ── Frontend endpoints (visitor; authorised by the conversation token) ───────────
$router->post('/gc-chat/start',            [LiveChatFrontend::class, 'start']);
$router->post('/gc-chat/send',             [LiveChatFrontend::class, 'send']);
$router->get('/gc-chat/poll',              [LiveChatFrontend::class, 'poll']);
$router->post('/gc-chat/request-operator', [LiveChatFrontend::class, 'requestOperator']);

// ── Admin (operator) routes ──────────────────────────────────────────────────────
$router->group('/manage', static function ($router): void {
    $router->get('/livechat',           [LiveChatAdmin::class, 'inbox']);
    $router->get('/livechat/poll',      [LiveChatAdmin::class, 'poll']);
    $router->post('/livechat/reply',    [LiveChatAdmin::class, 'reply']);
    $router->post('/livechat/takeover', [LiveChatAdmin::class, 'takeover']);
    $router->post('/livechat/close',    [LiveChatAdmin::class, 'close']);
    $router->get('/livechat/settings',  [LiveChatAdmin::class, 'settings']);
    $router->post('/livechat/settings', [LiveChatAdmin::class, 'saveSettings']);
});

// ── Frontend widget (only on the public theme) ───────────────────────────────────
gc_on('theme.footer', static function () use ($container): void {
    $container->get(LiveChatFrontend::class)->widget();
}, 20);

// ── Admin sidebar nav (with a "waiting" badge) ───────────────────────────────────
gc_on('manage.sidebar.nav', static function (string $base, string $activeNav) use ($container): void {
    $waiting = 0;
    try {
        $waiting = $container->get(ChatService::class)->waitingCount();
    } catch (\Throwable) {
        // tables not migrated yet — show no badge
    }
    $cls   = $activeNav === 'livechat' ? 'active' : '';
    $badge = $waiting > 0
        ? ' <span style="background:#ef4444;color:#fff;font-size:11px;font-weight:700;border-radius:20px;padding:1px 7px;margin-left:auto">' . (int) $waiting . '</span>'
        : '';
    echo '<li><a href="' . htmlspecialchars($base . '/manage/livechat', ENT_QUOTES) . '" class="' . $cls . '" style="display:flex;align-items:center">'
       . '<span class="nav-icon material-symbols-outlined">support_agent</span> GC Live Chat' . $badge
       . '</a></li>';
}, 70);
