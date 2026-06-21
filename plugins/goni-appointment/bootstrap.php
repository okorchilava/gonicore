<?php
declare(strict_types=1);

use GoniAppointment\AdminController;
use GoniAppointment\FrontendController;
use GoniAppointment\AppointmentService;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;

// ── Autoloader ────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GoniAppointment\\')) return;
    $rel  = substr($class, strlen('GoniAppointment\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── Migration ─────────────────────────────────────────────────────────────────

try {
    $conn = $container->get(Connection::class);
    $rows = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gapp_services'"
    );
    if ((int)($rows[0]['cnt'] ?? 0) === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── Auto-create booking page (once) ───────────────────────────────────────────

try {
    $qb   = $container->get(QueryBuilder::class);
    $flag = $qb->table('gapp_settings')->where('key', '=', 'page_created')->first();
    if (!$flag || $flag['value'] !== '1') {
        $slug = 'book';
        try {
            $s = $qb->table('gapp_settings')->where('key', '=', 'page_slug')->first();
            if ($s) $slug = (string) $s['value'];
        } catch (\Throwable) {}
        if (!$qb->table('posts')->where('slug', '=', $slug)->first()) {
            $user     = $qb->table('users')->orderBy('id', 'ASC')->first();
            $authorId = $user ? (int) $user['id'] : 1;
            $qb->table('posts')->insert([
                'type'      => 'page',
                'title'     => 'Book Appointment',
                'slug'      => $slug,
                'content'   => '',
                'status'    => 'published',
                'author_id' => $authorId,
            ]);
        }
        if ($flag) {
            $qb->table('gapp_settings')->where('key', '=', 'page_created')->update(['value' => '1']);
        } else {
            $qb->table('gapp_settings')->insert(['key' => 'page_created', 'value' => '1']);
        }
    }
} catch (\Throwable) {}

// ── DI Bindings ───────────────────────────────────────────────────────────────

$container->singleton(AppointmentService::class,
    static fn($c) => new AppointmentService($c->get(QueryBuilder::class))
);

$container->bind(AdminController::class,
    static fn($c) => new AdminController(
        $c->get(AppointmentService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string) $c->get(\GoniCore\Core\Config\Config::class)->get('app.name', 'GoniCore'),
    )
);

$container->bind(FrontendController::class,
    static fn($c) => new FrontendController($c->get(AppointmentService::class))
);

// ── Admin Routes ──────────────────────────────────────────────────────────────

$router->group('/manage/appointment', static function ($r) use ($container): void {
    $r->get('',                                              [AdminController::class, 'dashboard']);
    // Services
    $r->get('/services',                                     [AdminController::class, 'services']);
    $r->get('/services/new',                                 [AdminController::class, 'serviceNew']);
    $r->post('/services/new',                                [AdminController::class, 'serviceCreate']);
    $r->get('/services/{id}/edit',                           [AdminController::class, 'serviceEdit']);
    $r->post('/services/{id}/edit',                          [AdminController::class, 'serviceUpdate']);
    $r->post('/services/{id}/delete',                        [AdminController::class, 'serviceDelete']);
    // Staff
    $r->get('/staff',                                        [AdminController::class, 'staff']);
    $r->get('/staff/new',                                    [AdminController::class, 'staffNew']);
    $r->post('/staff/new',                                   [AdminController::class, 'staffCreate']);
    $r->get('/staff/{id}/edit',                              [AdminController::class, 'staffEdit']);
    $r->post('/staff/{id}/edit',                             [AdminController::class, 'staffUpdate']);
    $r->post('/staff/{id}/delete',                           [AdminController::class, 'staffDelete']);
    $r->get('/staff/{id}/schedule',                          [AdminController::class, 'staffSchedule']);
    $r->post('/staff/{id}/schedule',                         [AdminController::class, 'staffScheduleSave']);
    // Appointments
    $r->get('/appointments',                                 [AdminController::class, 'appointments']);
    $r->get('/appointments/{id}',                            [AdminController::class, 'appointmentView']);
    $r->post('/appointments/{id}/status',                    [AdminController::class, 'appointmentStatus']);
    $r->post('/appointments/{id}/note',                      [AdminController::class, 'appointmentNote']);
    // Settings
    $r->get('/settings',                                     [AdminController::class, 'settings']);
    $r->post('/settings',                                    [AdminController::class, 'settingsSave']);
});

// ── Frontend Routes ───────────────────────────────────────────────────────────

try {
    $_bookSlug = $container->get(AppointmentService::class)->setting('page_slug', 'book');
} catch (\Throwable) {
    $_bookSlug = 'book';
}

$router->get('/' . $_bookSlug,                                            [FrontendController::class, 'booking']);
$router->post('/' . $_bookSlug . '/process',                              [FrontendController::class, 'bookingProcess']);
$router->get('/appointment/confirmation/{number}',                        [FrontendController::class, 'confirmation']);
$router->get('/api/appointment/staff/{service_id}',                       [FrontendController::class, 'apiStaff']);
$router->get('/api/appointment/slots/{service_id}/{staff_id}/{date}',     [FrontendController::class, 'apiSlots']);

unset($_bookSlug);

// ── Sidebar Nav Hook ──────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $isActive = str_starts_with($activeNav, 'appointment') ? 'active' : '';
    echo '<li><a href="' . htmlspecialchars($base . '/manage/appointment', ENT_QUOTES) . '" class="' . $isActive . '">'
       . '<span class="nav-icon">📅</span> GoniAppointment'
       . '</a></li>';
}, 26);

// ── Intercept /page/book → /book ─────────────────────────────────────────────

gc_filter('page.intercept', static function (mixed $existing, array $post, \GoniCore\Core\Http\Request $request) use ($container): mixed {
    try {
        $slug = $container->get(AppointmentService::class)->setting('page_slug', 'book');
        if ($post['slug'] === $slug) {
            return \GoniCore\Core\Http\Response::redirect($request->basePath() . '/' . $slug);
        }
    } catch (\Throwable) {}
    return $existing;
});
