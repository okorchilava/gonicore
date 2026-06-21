<?php
declare(strict_types=1);

use GoniTickets\AdminController;
use GoniTickets\FrontendController;
use GoniTickets\TicketService;
use GoniTickets\GtUserService;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;

// ── Autoloader ────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GoniTickets\\')) return;
    $rel  = substr($class, strlen('GoniTickets\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DB Migration ──────────────────────────────────────────────────────────────

try {
    $conn = $container->get(Connection::class);
    $rows = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gt_events'"
    );
    if ((int)($rows[0]['cnt'] ?? 0) === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── DB Upgrade (adds new tables/columns to existing installs) ────────────────

try {
    $conn = $container->get(Connection::class);
    // gt_categories
    $conn->execute("
        CREATE TABLE IF NOT EXISTS `gt_categories` (
            `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `slug`       VARCHAR(100) NOT NULL UNIQUE,
            `label`      VARCHAR(255) NOT NULL,
            `icon`       VARCHAR(8)   NOT NULL DEFAULT '🎟',
            `accent`     VARCHAR(20)  NOT NULL DEFAULT '#a78bfa',
            `grad_from`  VARCHAR(20)  NOT NULL DEFAULT '#0a0812',
            `grad_to`    VARCHAR(20)  NOT NULL DEFAULT '#4c1d95',
            `sort_order` INT          NOT NULL DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    // gt_organizers
    $conn->execute("
        CREATE TABLE IF NOT EXISTS `gt_organizers` (
            `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `slug`        VARCHAR(200)  NOT NULL UNIQUE,
            `name`        VARCHAR(255)  NOT NULL,
            `description` TEXT          NOT NULL DEFAULT '',
            `logo`        VARCHAR(1000) NOT NULL DEFAULT '',
            `website`     VARCHAR(1000) NOT NULL DEFAULT '',
            `sort_order`  INT           NOT NULL DEFAULT 0,
            `created_at`  TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    // organizer_id on gt_events
    $hasCol = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gt_events' AND COLUMN_NAME = 'organizer_id'"
    );
    if ((int)($hasCol[0]['cnt'] ?? 0) === 0) {
        $conn->execute("ALTER TABLE `gt_events` ADD COLUMN `organizer_id` INT UNSIGNED NULL DEFAULT NULL");
    }
    // cover column on gt_organizers
    $hasCov = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gt_organizers' AND COLUMN_NAME = 'cover'"
    );
    if ((int)($hasCov[0]['cnt'] ?? 0) === 0) {
        $conn->execute("ALTER TABLE `gt_organizers` ADD COLUMN `cover` VARCHAR(1000) NOT NULL DEFAULT ''");
    }
    // category column on gt_events (legacy)
    $hasCat = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gt_events' AND COLUMN_NAME = 'category'"
    );
    if ((int)($hasCat[0]['cnt'] ?? 0) === 0) {
        $conn->execute("ALTER TABLE `gt_events` ADD COLUMN `category` VARCHAR(50) NOT NULL DEFAULT 'other'");
    }
    // gt_users (plugin frontend users)
    $conn->execute("
        CREATE TABLE IF NOT EXISTS `gt_users` (
            `id`            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            `email`         VARCHAR(255) NOT NULL UNIQUE,
            `name`          VARCHAR(255) NOT NULL DEFAULT '',
            `phone`         VARCHAR(50)  NOT NULL DEFAULT '',
            `password_hash` VARCHAR(255) NOT NULL,
            `created_at`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    // phone column on existing gt_users installs
    $hasPhone = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gt_users' AND COLUMN_NAME = 'phone'"
    );
    if ((int)($hasPhone[0]['cnt'] ?? 0) === 0) {
        $conn->execute("ALTER TABLE `gt_users` ADD COLUMN `phone` VARCHAR(50) NOT NULL DEFAULT '' AFTER `name`");
    }
} catch (\Throwable) {}

// ── Auto-create events page ───────────────────────────────────────────────────

try {
    $qb   = $container->get(QueryBuilder::class);
    $flag = $qb->table('gt_settings')->where('key', '=', 'pages_created')->first();

    if (!$flag || $flag['value'] !== '1') {
        $eventsSlug = 'events';
        try {
            $s = $qb->table('gt_settings')->where('key', '=', 'events_page_slug')->first();
            if ($s) $eventsSlug = (string) $s['value'];
        } catch (\Throwable) {}

        if (!$qb->table('posts')->where('slug', '=', $eventsSlug)->first()) {
            $user     = $qb->table('users')->orderBy('id', 'ASC')->first();
            $authorId = $user ? (int) $user['id'] : 1;
            $qb->table('posts')->insert([
                'type'      => 'page',
                'title'     => 'Events',
                'slug'      => $eventsSlug,
                'content'   => '',
                'status'    => 'published',
                'author_id' => $authorId,
            ]);
        }

        if ($flag) {
            $qb->table('gt_settings')->where('key', '=', 'pages_created')->update(['value' => '1']);
        } else {
            $qb->table('gt_settings')->insert(['key' => 'pages_created', 'value' => '1']);
        }
    }
} catch (\Throwable) {}

// ── DI Bindings ───────────────────────────────────────────────────────────────

$container->singleton(TicketService::class,
    static fn($c) => new TicketService($c->get(QueryBuilder::class))
);

$container->singleton(GtUserService::class,
    static fn($c) => new GtUserService($c->get(QueryBuilder::class))
);

$container->bind(AdminController::class,
    static fn($c) => new AdminController(
        $c->get(TicketService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string) $c->get(\GoniCore\Core\Config\Config::class)->get('app.name', 'GoniCore'),
    )
);

$container->bind(FrontendController::class,
    static fn($c) => new FrontendController(
        $c->get(TicketService::class),
        $c->get(GtUserService::class),
    )
);

// ── Admin Routes ──────────────────────────────────────────────────────────────

$router->group('/manage/tickets', static function ($r) use ($container): void {
    $r->get('',                                          [AdminController::class, 'dashboard']);
    // Events
    $r->get('/events',                                   [AdminController::class, 'eventsList']);
    $r->get('/events/new',                               [AdminController::class, 'eventNew']);
    $r->post('/events/new',                              [AdminController::class, 'eventCreate']);
    $r->get('/events/{id}/edit',                         [AdminController::class, 'eventEdit']);
    $r->post('/events/{id}/edit',                        [AdminController::class, 'eventUpdate']);
    $r->post('/events/{id}/delete',                      [AdminController::class, 'eventDelete']);
    // Ticket Types
    $r->post('/events/{event_id}/ticket-types/create',   [AdminController::class, 'ticketTypeCreate']);
    $r->post('/ticket-types/{id}/update',                [AdminController::class, 'ticketTypeUpdate']);
    $r->post('/ticket-types/{id}/delete',                [AdminController::class, 'ticketTypeDelete']);
    // Bookings
    $r->get('/bookings',                                 [AdminController::class, 'bookingsList']);
    $r->get('/bookings/{id}',                            [AdminController::class, 'bookingView']);
    $r->post('/bookings/{id}/status',                    [AdminController::class, 'bookingUpdateStatus']);
    // Categories
    $r->get('/categories',                               [AdminController::class, 'categoriesList']);
    $r->get('/categories/new',                           [AdminController::class, 'categoryNew']);
    $r->post('/categories/new',                          [AdminController::class, 'categoryCreate']);
    $r->get('/categories/{id}/edit',                     [AdminController::class, 'categoryEdit']);
    $r->post('/categories/{id}/edit',                    [AdminController::class, 'categoryUpdate']);
    $r->post('/categories/{id}/delete',                  [AdminController::class, 'categoryDelete']);
    // Organizers
    $r->get('/organizers',                               [AdminController::class, 'organizersList']);
    $r->get('/organizers/new',                           [AdminController::class, 'organizerNew']);
    $r->post('/organizers/new',                          [AdminController::class, 'organizerCreate']);
    $r->get('/organizers/{id}/edit',                     [AdminController::class, 'organizerEdit']);
    $r->post('/organizers/{id}/edit',                    [AdminController::class, 'organizerUpdate']);
    $r->post('/organizers/{id}/delete',                  [AdminController::class, 'organizerDelete']);
    // Users
    $r->get('/users',                                    [AdminController::class, 'usersList']);
    // Settings
    $r->get('/settings',                                 [AdminController::class, 'settingsForm']);
    $r->post('/settings',                                [AdminController::class, 'settingsSave']);
});

// ── Frontend Routes ───────────────────────────────────────────────────────────

try {
    $_eventsSlug = $container->get(TicketService::class)->setting('events_page_slug', 'events');
} catch (\Throwable) {
    $_eventsSlug = 'events';
}

$router->get('/' . $_eventsSlug,                                      [FrontendController::class, 'events']);
$router->get('/' . $_eventsSlug . '/organizers',                      [FrontendController::class, 'organizersList']);
$router->get('/' . $_eventsSlug . '/organizer/{slug}',                [FrontendController::class, 'organizerPage']);
$router->get('/' . $_eventsSlug . '/{slug}',                          [FrontendController::class, 'event']);
$router->post('/' . $_eventsSlug . '/{slug}/book',                    [FrontendController::class, 'book']);
$router->get('/tickets/confirmation/{number}',                        [FrontendController::class, 'confirmation']);
$router->post('/tickets/bog-callback',                                [FrontendController::class, 'bogCallback']);
$router->get('/tickets/my-ticket',                                    [FrontendController::class, 'ticketLookup']);
$router->post('/tickets/my-ticket',                                   [FrontendController::class, 'ticketLookupPost']);
$router->get('/tickets/view/{number}',                                [FrontendController::class, 'ticketView']);
$router->get('/tickets/login',                                        [FrontendController::class, 'userLogin']);
$router->post('/tickets/login',                                       [FrontendController::class, 'userLoginPost']);
$router->get('/tickets/register',                                     [FrontendController::class, 'userRegister']);
$router->post('/tickets/register',                                    [FrontendController::class, 'userRegisterPost']);
$router->get('/tickets/logout',                                       [FrontendController::class, 'userLogout']);
$router->get('/tickets/account',                                      [FrontendController::class, 'userAccount']);

unset($_eventsSlug);

// ── Sidebar Nav Hook ──────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $h    = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
    $isTk = str_starts_with($activeNav, 'tickets');
    $open = $isTk ? ' open' : '';
    $sub  = static function(string $url, string $icon, string $label, string $key) use ($h, $activeNav): string {
        $cls = $activeNav === $key ? ' active' : '';
        return '<li class="nav-sub"><a href="' . $h($url) . '" class="' . $cls . '">'
             . '<span class="nav-icon">' . $icon . '</span> ' . $label . '</a></li>';
    };
    echo '<li>'
       . '<div class="nav-parent-toggle' . $open . '" onclick="navToggle(this)">'
       . '<span class="nav-icon">🎟</span> GoniTickets'
       . '<span class="nav-arrow">▾</span>'
       . '</div>'
       . '<ul class="nav-children' . $open . '">'
       . $sub($base . '/manage/tickets',               '📊', 'Dashboard',  'tickets')
       . $sub($base . '/manage/tickets/events',        '🎭', 'Events',     'tickets-events')
       . $sub($base . '/manage/tickets/bookings',      '📋', 'Bookings',   'tickets-bookings')
       . $sub($base . '/manage/tickets/categories',    '🏷',  'Categories', 'tickets-categories')
       . $sub($base . '/manage/tickets/organizers',    '👤',  'Organizers', 'tickets-organizers')
       . $sub($base . '/manage/tickets/users',         '👥',  'Users',      'tickets-users')
       . $sub($base . '/manage/tickets/settings',      '⚙',   'Settings',  'tickets-settings')
       . '</ul>'
       . '</li>';
}, 25);

// ── Intercept /page/events → /events ─────────────────────────────────────────

gc_filter('page.intercept', static function (mixed $existing, array $post, \GoniCore\Core\Http\Request $request) use ($container): mixed {
    try {
        $slug = $container->get(TicketService::class)->setting('events_page_slug', 'events');
        if ($post['slug'] === $slug) {
            return \GoniCore\Core\Http\Response::redirect($request->basePath() . '/' . $slug);
        }
    } catch (\Throwable) {}
    return $existing;
});
