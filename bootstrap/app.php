<?php

declare(strict_types=1);

use GoniCore\Core\Application;
use GoniCore\Core\Config\Config;
use GoniCore\Core\Config\Env;
use GoniCore\Core\Mail\MailService;
use GoniCore\Core\Container\Container;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Hooks\PluginLoader;
use GoniCore\Core\Http\Middleware\AuthMiddleware;
use GoniCore\Core\Http\Middleware\CorsMiddleware;
use GoniCore\Core\Http\Router;
use GoniCore\Core\Validation\Validator;
use GoniCore\Core\Shortcodes\ShortcodeManager;
use GoniCore\Core\Widgets\WidgetManager;
use GoniCore\Modules\Auth\AuthController;
use GoniCore\Modules\Auth\AuthService;
use GoniCore\Modules\Auth\JwtService;
use GoniCore\Modules\Category\CategoryController;
use GoniCore\Modules\Category\CategoryRepository;
use GoniCore\Modules\Media\MediaController;
use GoniCore\Modules\Media\MediaService;
use GoniCore\Modules\Menu\MenuService;
use GoniCore\Modules\Post\PostController;
use GoniCore\Modules\Post\PostRepository;
use GoniCore\Modules\Post\PostService;
use GoniCore\Modules\Login\LoginController;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;
use GoniCore\Modules\Manage\ActivityLogger;
use GoniCore\Modules\Manage\ManageController;
use GoniCore\Modules\Manage\PluginManager;
use GoniCore\Modules\Manage\TodoRepository;
use GoniCore\Modules\Notifications\NotificationRepository;
use GoniCore\Modules\Notifications\NotificationService;
use GoniCore\Modules\Settings\SettingsRepository;
use GoniCore\Modules\Settings\SettingsService;
use GoniCore\Modules\Language\LanguageController;
use GoniCore\Modules\Language\LanguageRepository;
use GoniCore\Modules\Language\LanguageService;
use GoniCore\Modules\Theme\ThemeController;
use GoniCore\Modules\User\UserRepository;
use GoniCore\Modules\Widget\WidgetController;
use GoniCore\Modules\Widget\WidgetRepository;
use GoniCore\Modules\Widget\WidgetService;

// ============================================================
// 1. Environment
// ============================================================

Env::load(__DIR__ . '/../.env');

// ============================================================
// 2. Configuration
// ============================================================

$config = new Config();
$config->loadFile(__DIR__ . '/../config/database.php', 'database');
$config->loadFile(__DIR__ . '/../config/app.php',      'app');
$config->loadFile(__DIR__ . '/../config/auth.php',     'auth');

// ============================================================
// 3. DI Container — singleton bindings
// ============================================================

$container = new Container();
Container::setGlobalInstance($container);

// Config
$container->instance(Config::class, $config);

// ---- Error logging (file-based → storage/logs, viewable in the manage panel) ----
$errorLogger = new \GoniCore\Core\Logging\ErrorLogger(__DIR__ . '/../storage/logs');
$errorLogger->register();
$container->instance(\GoniCore\Core\Logging\ErrorLogger::class, $errorLogger);

// ---- Infrastructure ----

$container->singleton(
    Connection::class,
    static fn(Container $c): Connection =>
        Connection::fromConfig($c->get(Config::class)->require('database')),
);

$container->bind(
    QueryBuilder::class,
    static fn(Container $c): QueryBuilder =>
        new QueryBuilder($c->get(Connection::class)),
);

$container->singleton(HookManager::class,     static fn(): HookManager     => new HookManager());
$container->singleton(WidgetManager::class,   static fn(): WidgetManager   => new WidgetManager());
$container->singleton(ShortcodeManager::class, static fn(): ShortcodeManager => new ShortcodeManager());

// ---- Validation ----

$container->bind(
    Validator::class,
    static fn(Container $c): Validator =>
        new Validator($c->get(QueryBuilder::class)),
);

// ---- Auth / JWT ----

$container->singleton(
    JwtService::class,
    static fn(Container $c): JwtService => new JwtService(
        secret: (string) $c->get(Config::class)->require('auth.jwt_secret'),
        ttl:    (int)    $c->get(Config::class)->get('auth.jwt_ttl', 3600),
    ),
);

$container->singleton(
    AuthMiddleware::class,
    static fn(Container $c): AuthMiddleware =>
        new AuthMiddleware($c->get(JwtService::class)),
);

$container->singleton(
    CorsMiddleware::class,
    static fn(): CorsMiddleware => new CorsMiddleware(),
);

// ---- Repositories ----

$container->bind(
    UserRepository::class,
    static fn(Container $c): UserRepository =>
        new UserRepository($c->get(QueryBuilder::class)),
);

$container->bind(
    PostRepository::class,
    static fn(Container $c): PostRepository =>
        new PostRepository($c->get(QueryBuilder::class)),
);

$container->bind(
    CategoryRepository::class,
    static fn(Container $c): CategoryRepository =>
        new CategoryRepository($c->get(QueryBuilder::class)),
);

$container->singleton(
    SettingsRepository::class,
    static fn(Container $c): SettingsRepository =>
        new SettingsRepository($c->get(QueryBuilder::class)),
);

$container->singleton(
    SettingsService::class,
    static fn(Container $c): SettingsService =>
        new SettingsService($c->get(SettingsRepository::class)),
);

$container->bind(
    LanguageRepository::class,
    static fn(Container $c): LanguageRepository =>
        new LanguageRepository($c->get(QueryBuilder::class)),
);

$container->singleton(
    LanguageService::class,
    static fn(Container $c): LanguageService =>
        new LanguageService($c->get(LanguageRepository::class)),
);

// ---- Services ----

$container->bind(
    AuthService::class,
    static fn(Container $c): AuthService => new AuthService(
        $c->get(UserRepository::class),
        $c->get(JwtService::class),
    ),
);

$container->bind(
    PostService::class,
    static fn(Container $c): PostService =>
        new PostService($c->get(PostRepository::class)),
);

$container->singleton(
    MediaService::class,
    static fn(Container $c): MediaService => new MediaService(
        (string) $c->get(Config::class)->get('auth.media_storage', __DIR__ . '/../storage/media'),
    ),
);

$container->bind(
    MenuService::class,
    static fn(Container $c): MenuService =>
        new MenuService($c->get(QueryBuilder::class)),
);

// ---- Mail ----

$container->singleton(
    MailService::class,
    static fn(Container $c): MailService => new MailService($c->get(SettingsService::class)),
);

// ---- Controllers ----

$container->bind(
    AuthController::class,
    static fn(Container $c): AuthController => new AuthController(
        $c->get(AuthService::class),
        $c->get(JwtService::class),
        $c->get(Validator::class),
    ),
);

$container->bind(
    PostController::class,
    static fn(Container $c): PostController => new PostController(
        $c->get(PostService::class),
        $c->get(PostRepository::class),
        $c->get(Validator::class),
    ),
);

$container->singleton(
    SessionManager::class,
    static fn(): SessionManager => new SessionManager(),
);

$container->singleton(
    LoginService::class,
    static fn(Container $c): LoginService => new LoginService(
        $c->get(UserRepository::class),
        $c->get(SessionManager::class),
    ),
);

$container->bind(
    LoginController::class,
    static fn(Container $c): LoginController => new LoginController(
        $c->get(LoginService::class),
        $c->get(SessionManager::class),
        $c->get(CategoryRepository::class),
        $c->get(HookManager::class),
        $c->get(UserRepository::class),
        $c->get(MailService::class),
        $c->get(LanguageService::class),
    ),
);

$container->bind(
    CategoryController::class,
    static fn(Container $c): CategoryController => new CategoryController(
        $c->get(CategoryRepository::class),
        $c->get(Validator::class),
    ),
);

$container->bind(
    MediaController::class,
    static fn(Container $c): MediaController => new MediaController(
        $c->get(MediaService::class),
        $c->get(QueryBuilder::class),
    ),
);

$container->bind(
    WidgetRepository::class,
    static fn(Container $c): WidgetRepository =>
        new WidgetRepository($c->get(QueryBuilder::class)),
);

$container->bind(
    WidgetService::class,
    static fn(Container $c): WidgetService => new WidgetService(
        $c->get(WidgetRepository::class),
        $c->get(HookManager::class),
    ),
);

$container->bind(
    WidgetController::class,
    static fn(Container $c): WidgetController => new WidgetController(
        $c->get(WidgetService::class),
        $c->get(WidgetRepository::class),
        $c->get(WidgetManager::class),
        $c->get(Validator::class),
    ),
);

$container->bind(
    NotificationRepository::class,
    static fn(Container $c): NotificationRepository =>
        new NotificationRepository($c->get(QueryBuilder::class)),
);

$container->bind(
    NotificationService::class,
    static fn(Container $c): NotificationService =>
        new NotificationService($c->get(NotificationRepository::class)),
);

$container->singleton(
    ActivityLogger::class,
    static fn(Container $c): ActivityLogger =>
        new ActivityLogger($c->get(QueryBuilder::class)),
);

$container->bind(
    TodoRepository::class,
    static fn(Container $c): TodoRepository =>
        new TodoRepository($c->get(QueryBuilder::class)),
);

$container->singleton(
    ThemeController::class,
    static fn(Container $c): ThemeController => new ThemeController(
        $c->get(PostRepository::class),
        $c->get(CategoryRepository::class),
        $c->get(LanguageService::class),
        $c->get(SettingsService::class),
        $c->get(ShortcodeManager::class),
        $c->get(WidgetService::class),
        $c->get(MenuService::class),
    ),
);

$container->singleton(
    PluginManager::class,
    static fn(Container $c): PluginManager =>
        new PluginManager(__DIR__ . '/../plugins', $c->get(Connection::class)),
);

$container->bind(
    ManageController::class,
    static fn(Container $c): ManageController => new ManageController(
        $c->get(LoginService::class),
        $c->get(PostRepository::class),
        $c->get(PostService::class),
        $c->get(CategoryRepository::class),
        $c->get(UserRepository::class),
        $c->get(ActivityLogger::class),
        $c->get(TodoRepository::class),
        $c->get(NotificationService::class),
        $c->get(LanguageRepository::class),
        $c->get(LanguageService::class),
        $c->get(SettingsService::class),
        $c->get(ThemeController::class),
        $c->get(PluginManager::class),
        $c->get(WidgetRepository::class),
        $c->get(WidgetService::class),
        $c->get(MediaService::class),
        $c->get(MenuService::class),
        $c->get(HookManager::class),
        $c->get(QueryBuilder::class),
        $c->get(SessionManager::class),
    ),
);

$container->bind(
    LanguageController::class,
    static fn(Container $c): LanguageController => new LanguageController(
        $c->get(LanguageService::class),
        $c->get(LanguageRepository::class),
        $c->get(PostRepository::class),
        $c->get(LoginService::class),
        $c->get(NotificationService::class),
        $c->get(UserRepository::class),
        $c->get(SessionManager::class),
    ),
);

// ============================================================
// 4. Theme functions (register widget areas, menu locations)
// ============================================================

$themeDir = __DIR__ . '/../themes/default';
$themeFunc = $themeDir . '/functions.php';
if (is_file($themeFunc)) require_once $themeFunc;

// ============================================================
// 5. Router
// ============================================================

$router = new Router($container);

// ── Public front-end ──────────────────────────────────────────────────────────

$router->get('/',                   [ThemeController::class, 'home']);
$router->get('/post/{slug}',        [ThemeController::class, 'post']);
$router->get('/page/{slug}',        [ThemeController::class, 'page']);
$router->get('/category/{slug}',    [ThemeController::class, 'category']);

// ── Language switch ───────────────────────────────────────────────────────────

$router->get('/lang/switch/{code}', [LanguageController::class, 'switchLang']);
$router->get('/lang/{code}',        [LanguageController::class, 'switchLang']); // legacy alias

// ── Login / logout ────────────────────────────────────────────────────────────

$router->get('/login',              [LoginController::class, 'showLogin']);
$router->post('/login',             [LoginController::class, 'processLogin']);
$router->get('/logout',             [LoginController::class, 'logout']);

// ── Management panel ──────────────────────────────────────────────────────────

$router->group('/manage', static function (Router $r): void {

    $r->get('',                          [ManageController::class, 'dashboard']);

    // Posts
    $r->get('/posts',                    [ManageController::class, 'postsList']);
    $r->get('/posts/new',                [ManageController::class, 'postNew']);
    $r->post('/posts/new',               [ManageController::class, 'postCreate']);
    $r->post('/posts',                   [ManageController::class, 'postCreate']); // form posts here for a new post
    $r->get('/posts/{id}',               [ManageController::class, 'postEdit']);
    $r->post('/posts/{id}',              [ManageController::class, 'postUpdate']);
    $r->post('/posts/{id}/delete',       [ManageController::class, 'postDelete']);

    // Pages
    $r->get('/pages',                    [ManageController::class, 'pagesList']);
    $r->get('/pages/new',                [ManageController::class, 'pageNew']);
    $r->post('/pages/new',               [ManageController::class, 'pageCreate']);
    $r->post('/pages',                   [ManageController::class, 'pageCreate']); // form posts here for a new page
    $r->get('/pages/{id}',               [ManageController::class, 'pageEdit']);
    $r->post('/pages/{id}',              [ManageController::class, 'pageUpdate']);
    $r->post('/pages/{id}/delete',       [ManageController::class, 'pageDelete']);

    // Users
    $r->get('/users',                    [ManageController::class, 'usersList']);
    $r->get('/users/new',                [ManageController::class, 'userNew']);
    $r->post('/users/new',               [ManageController::class, 'userCreate']);
    $r->get('/users/{id}',               [ManageController::class, 'userEdit']);
    $r->get('/users/{id}/edit',          [ManageController::class, 'userEdit']);
    $r->post('/users/{id}',              [ManageController::class, 'userUpdate']);
    $r->post('/users/{id}/edit',         [ManageController::class, 'userUpdate']);
    $r->post('/users/{id}/delete',       [ManageController::class, 'userDelete']);

    // Categories
    $r->get('/categories',               [ManageController::class, 'categoriesList']);
    $r->post('/categories',              [ManageController::class, 'categoryCreate']);
    $r->post('/categories/create',       [ManageController::class, 'categoryCreate']);
    $r->post('/categories/{id}/update',  [ManageController::class, 'categoryUpdate']);
    $r->post('/categories/{id}',         [ManageController::class, 'categoryUpdate']);
    $r->post('/categories/{id}/delete',  [ManageController::class, 'categoryDelete']);

    // Menus
    $r->get('/menus',                            [ManageController::class, 'menusList']);
    $r->post('/menus',                           [ManageController::class, 'menuCreate']);
    $r->post('/menus/create',                    [ManageController::class, 'menuCreate']);
    $r->post('/menus/assign-locations',          [ManageController::class, 'menuAssignLocations']);
    $r->post('/menus/items/reorder',             [ManageController::class, 'menuItemReorder']);
    $r->post('/menus/items/{item_id}/update',     [ManageController::class, 'menuItemUpdate']);
    $r->post('/menus/items/{item_id}/delete',    [ManageController::class, 'menuItemDelete']);
    $r->post('/menus/{id}/items/add',            [ManageController::class, 'menuItemAdd']);
    $r->post('/menus/{id}/delete',               [ManageController::class, 'menuDelete']);
    $r->post('/menus/{id}/rename',               [ManageController::class, 'menuRename']);

    // Languages
    $r->get('/languages',                [LanguageController::class, 'index']);
    $r->post('/languages',               [LanguageController::class, 'store']);
    $r->get('/languages/{code}/edit',    [LanguageController::class, 'editForm']);
    $r->post('/languages/{code}/edit',   [LanguageController::class, 'editSave']);
    $r->post('/languages/{code}/default',[LanguageController::class, 'setDefault']);
    $r->post('/languages/{code}/toggle', [LanguageController::class, 'toggle']);
    $r->post('/languages/{code}/delete', [LanguageController::class, 'delete']);

    // Language file translation editor
    $r->get('/languages/{code}/file',    [LanguageController::class, 'fileForm']);
    $r->post('/languages/{code}/file',   [LanguageController::class, 'fileSave']);

    // Post translations
    $r->get('/posts/{id}/translate/{code}',  [LanguageController::class, 'translateForm']);
    $r->post('/posts/{id}/translate/{code}', [LanguageController::class, 'translateSave']);

    // Widgets
    $r->get('/widgets',                  [ManageController::class, 'widgetsList']);
    $r->post('/widgets',                 [ManageController::class, 'widgetCreate']);
    $r->post('/widgets/{id}',            [ManageController::class, 'widgetUpdate']);
    $r->post('/widgets/{id}/delete',     [ManageController::class, 'widgetDelete']);
    $r->post('/widgets/{id}/toggle',     [ManageController::class, 'widgetToggle']);

    // Gallery / media
    $r->get('/gallery',                  [ManageController::class, 'galleryList']);
    $r->get('/gallery/json',             [ManageController::class, 'galleryJson']);
    $r->post('/gallery/upload',          [ManageController::class, 'galleryUpload']);
    $r->post('/gallery/{id}/delete',     [ManageController::class, 'galleryDelete']);

    // Plugins
    $r->get('/plugins',                  [ManageController::class, 'pluginsList']);
    $r->post('/plugins/upload',          [ManageController::class, 'pluginUpload']);
    $r->post('/plugins/{slug}/activate', [ManageController::class, 'pluginActivate']);
    $r->post('/plugins/{slug}/deactivate',[ManageController::class, 'pluginDeactivate']);
    $r->post('/plugins/{slug}/delete',   [ManageController::class, 'pluginDelete']);

    // Settings
    $r->get('/settings',                 [ManageController::class, 'settingsForm']);
    $r->post('/settings',                [ManageController::class, 'settingsSave']);

    // Logs (error log viewer)
    $r->get('/logs',                     [ManageController::class, 'logsList']);
    $r->post('/logs/clear',              [ManageController::class, 'logsClear']);

    // Profile
    $r->get('/profile',                      [ManageController::class, 'profileForm']);
    $r->post('/profile',                     [ManageController::class, 'profileSave']);
    $r->post('/profile/notifications',       [ManageController::class, 'profileNotifications']);

    // Notifications
    $r->post('/notifications/{id}/read', [ManageController::class, 'notificationRead']);
    $r->post('/notifications/read-all',  [ManageController::class, 'notificationReadAll']);

});

// ── REST API (JWT-protected) ──────────────────────────────────────────────────

$router->group('/api/v1', static function (Router $r) use ($container): void {
    $auth = $container->get(AuthMiddleware::class);

    $r->post('/auth/register', [AuthController::class, 'register']);
    $r->post('/auth/login',    [AuthController::class, 'login']);
    $r->post('/auth/refresh',  [AuthController::class, 'refresh']);
    $r->get('/auth/me',        [AuthController::class, 'me'])->middleware($auth);

    $r->get('/posts',          [PostController::class, 'index']);
    $r->get('/posts/{id}',     [PostController::class, 'show']);
    $r->post('/posts',         [PostController::class, 'store'])->middleware($auth);
    $r->put('/posts/{id}',     [PostController::class, 'update'])->middleware($auth);
    $r->delete('/posts/{id}',  [PostController::class, 'destroy'])->middleware($auth);

    $r->get('/categories',     [CategoryController::class, 'index']);
    $r->post('/categories',    [CategoryController::class, 'store'])->middleware($auth);
    $r->put('/categories/{id}',[CategoryController::class, 'update'])->middleware($auth);
    $r->delete('/categories/{id}', [CategoryController::class, 'destroy'])->middleware($auth);
});

// ============================================================
// 6. Plugins
// ============================================================

$hooks     = $container->get(HookManager::class);
$pluginDir = __DIR__ . '/../plugins';

// Register global HookManager instance + load global plugin API functions
HookManager::setGlobalInstance($hooks);
require_once __DIR__ . '/../src/Core/functions.php';

/** @var PluginLoader $pluginLoader */
$pluginLoader = new PluginLoader();
$pluginLoader->load($pluginDir, $router, $container, $hooks);

// Boot language detection for front-end requests
$langService = $container->get(LanguageService::class);

// Core listener: admin.notify — plugins/core call gc_emit('admin.notify', ...) to send admin email
// Usage: gc_emit('admin.notify', 'Subject', '<p>HTML body</p>')
// Extended: gc_emit('admin.notify', 'Subject', '<p>HTML</p>', $ctaUrl, $ctaText)
$hooks->on('admin.notify', static function (
    string  $subject,
    string  $html,
    ?string $ctaUrl  = null,
    ?string $ctaText = null,
) use ($container): void {
    /** @var MailService $mailer */
    $mailer  = $container->get(MailService::class);
    $body    = $ctaUrl ? $mailer->template($subject, $html, $ctaUrl, $ctaText) : $mailer->template($subject, $html);
    $mailer->adminNotify($subject, $body);
}, 10);

// ============================================================
// 7. Session lifetime from settings
// ============================================================

try {
    $sessionMgr      = $container->get(SessionManager::class);
    $settingsSvc     = $container->get(SettingsService::class);
    $sessionMinutes  = (int) $settingsSvc->get('session_lifetime', 120);
    if ($sessionMinutes > 0) {
        $sessionMgr->configure($sessionMinutes);
    }
} catch (\Throwable) {}

// ============================================================
// 8. Application
// ============================================================

return new Application($container, $router, $config);
