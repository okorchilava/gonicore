<?php
declare(strict_types=1);

use GoniQuizz\GoniQuizzAdminController;
use GoniQuizz\GoniQuizzFrontController;
use GoniQuizz\GoniQuizzService;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;

// ── Autoloader ─────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GoniQuizz\\')) return;
    $rel  = substr($class, strlen('GoniQuizz\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DB Migration ───────────────────────────────────────────────────────────────

try {
    $conn = $container->get(Connection::class);
    $rows = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'goniquizz_quizzes'"
    );
    if ((int)($rows[0]['cnt'] ?? 0) === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── Base path (for public play/result URLs) ────────────────────────────────────

try {
    $appUrl = (string) $container->get(\GoniCore\Core\Config\Config::class)->get('app.url', '');
    $_gqzBase = rtrim(parse_url($appUrl, PHP_URL_PATH) ?? '', '/');
} catch (\Throwable) {
    $_gqzBase = '';
}
GoniQuizzService::setBasePath($_gqzBase);
unset($_gqzBase);

// ── DI Bindings ───────────────────────────────────────────────────────────────

$container->singleton(GoniQuizzService::class,
    static fn($c) => new GoniQuizzService(
        $c->get(QueryBuilder::class),
        $c->get(Connection::class),
    )
);

$container->bind(GoniQuizzAdminController::class,
    static fn($c) => new GoniQuizzAdminController(
        $c->get(GoniQuizzService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string) $c->get(\GoniCore\Core\Config\Config::class)->get('app.name', 'GoniCore'),
    )
);

$container->bind(GoniQuizzFrontController::class,
    static fn($c) => new GoniQuizzFrontController(
        $c->get(GoniQuizzService::class),
        $pluginDir,
    )
);

GoniQuizzService::register($container->get(GoniQuizzService::class));

// ── Public Routes ──────────────────────────────────────────────────────────────

$router->get('/goniquizz/play',    [GoniQuizzFrontController::class, 'play']);
$router->post('/goniquizz/submit', [GoniQuizzFrontController::class, 'submit']);
$router->get('/goniquizz/result',  [GoniQuizzFrontController::class, 'result']);

// ── Admin Routes ───────────────────────────────────────────────────────────────

$router->group('/manage/goniquizz', static function ($r) use ($container): void {
    $r->get('',                    [GoniQuizzAdminController::class, 'quizzes']);
    $r->get('/quizzes/form',       [GoniQuizzAdminController::class, 'quizForm']);
    $r->post('/quizzes/save',      [GoniQuizzAdminController::class, 'quizSave']);
    $r->post('/quizzes/delete',    [GoniQuizzAdminController::class, 'quizDelete']);
    $r->post('/quizzes/toggle',    [GoniQuizzAdminController::class, 'quizToggle']);
    $r->get('/questions',          [GoniQuizzAdminController::class, 'questions']);
    $r->get('/questions/form',     [GoniQuizzAdminController::class, 'questionForm']);
    $r->post('/questions/save',    [GoniQuizzAdminController::class, 'questionSave']);
    $r->post('/questions/delete',  [GoniQuizzAdminController::class, 'questionDelete']);
    $r->get('/results',            [GoniQuizzAdminController::class, 'results']);
    $r->post('/results/clear',     [GoniQuizzAdminController::class, 'resultsClear']);
});

// ── Sidebar Nav ────────────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $h     = static fn(string $s) => htmlspecialchars($s, ENT_QUOTES);
    $isAct = str_starts_with($activeNav, 'goniquizz');
    $open  = $isAct ? ' open' : '';
    $sub   = static function (string $url, string $icon, string $label, string $key) use ($h, $activeNav): string {
        $cls = $activeNav === $key ? ' active' : '';
        return '<li class="nav-sub"><a href="' . $h($url) . '" class="' . $cls . '">'
             . '<span class="nav-icon">' . $icon . '</span> ' . $label . '</a></li>';
    };
    echo '<li>'
       . '<div class="nav-parent-toggle' . $open . '" onclick="navToggle(this)">'
       . '<span class="nav-icon">🧠</span> GoniQuizz'
       . '<span class="nav-arrow">▾</span>'
       . '</div>'
       . '<ul class="nav-children' . $open . '">'
       . $sub($base . '/manage/goniquizz',              '📋', 'Quizzes',    'goniquizz-quizzes')
       . $sub($base . '/manage/goniquizz/quizzes/form', '+',  'ახალი Quiz', 'goniquizz-new')
       . '</ul>'
       . '</li>';
}, 50);

// ── Global helper ──────────────────────────────────────────────────────────────
//
//   goniquizz('my-quiz-slug')              → styled link button
//   goniquizz('my-quiz-slug', 'გავლა →')   → custom label
//
if (!function_exists('goniquizz')) {
    function goniquizz(string $slug, string $label = '🧠 Quiz-ის გავლა'): string
    {
        return GoniQuizzService::getInstance()?->quizLink($slug, $label) ?? '';
    }
}
