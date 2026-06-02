<?php
declare(strict_types=1);

use GoniStore\AdminController;
use GoniStore\FrontendController;
use GoniStore\StoreService;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;

// ── Autoloader ────────────────────────────────────────────────────────────────
spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GoniStore\\')) return;
    $rel  = substr($class, strlen('GoniStore\\'));
    $file = $pluginDir.'/src/'.str_replace('\\','/',$rel).'.php';
    if (is_file($file)) require_once $file;
});

// ── Migration ─────────────────────────────────────────────────────────────────
try {
    $conn = $container->get(Connection::class);
    if (empty($conn->query("SHOW TABLES LIKE 'gs_products'"))) {
        $migration = require $pluginDir.'/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── DI ────────────────────────────────────────────────────────────────────────
$container->bind(StoreService::class,
    static fn($c) => new StoreService($c->get(QueryBuilder::class))
);
$container->bind(AdminController::class,
    static fn($c) => new AdminController(
        $c->get(StoreService::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string)$c->get(\GoniCore\Core\Config\Config::class)->get('app.name','GoniCore'),
    )
);
$container->bind(FrontendController::class,
    static fn($c) => new FrontendController($c->get(StoreService::class))
);

// ── Admin routes ──────────────────────────────────────────────────────────────
$router->group('/manage/store', static function ($r) use ($container): void {
    $r->get('',                            [AdminController::class, 'dashboard']);
    // Products
    $r->get('/products',                   [AdminController::class, 'products']);
    $r->get('/products/new',               [AdminController::class, 'productNew']);
    $r->post('/products/new',              [AdminController::class, 'productCreate']);
    $r->get('/products/{id}/edit',         [AdminController::class, 'productEdit']);
    $r->post('/products/{id}/edit',        [AdminController::class, 'productUpdate']);
    $r->post('/products/{id}/delete',      [AdminController::class, 'productDelete']);
    // Categories
    $r->get('/categories',                 [AdminController::class, 'categories']);
    $r->post('/categories/create',         [AdminController::class, 'categoryCreate']);
    $r->post('/categories/{id}/update',    [AdminController::class, 'categoryUpdate']);
    $r->post('/categories/{id}/delete',    [AdminController::class, 'categoryDelete']);
    // Orders
    $r->get('/orders',                     [AdminController::class, 'orders']);
    $r->get('/orders/{id}',                [AdminController::class, 'orderView']);
    $r->post('/orders/{id}/status',        [AdminController::class, 'orderUpdateStatus']);
    // Coupons
    $r->get('/coupons',                    [AdminController::class, 'coupons']);
    $r->post('/coupons/save',              [AdminController::class, 'couponSave']);
    $r->post('/coupons/{id}/delete',       [AdminController::class, 'couponDelete']);
    // Settings
    $r->get('/settings',                   [AdminController::class, 'storeSettings']);
    $r->post('/settings',                  [AdminController::class, 'storeSettingsSave']);
});

// ── Frontend routes ───────────────────────────────────────────────────────────
// Resolve shop slug from settings (defaults to 'shop')
try {
    $_svc      = $container->get(StoreService::class);
    $_shopSlug = $_svc->setting('shop_page_slug', 'shop');
    $_cartSlug = $_svc->setting('cart_page_slug', 'cart');
    $_chkSlug  = $_svc->setting('checkout_page_slug', 'checkout');
    unset($_svc);
} catch (\Throwable) {
    $_shopSlug = 'shop';
    $_cartSlug = 'cart';
    $_chkSlug  = 'checkout';
}

$router->get('/'.$_shopSlug,                     [FrontendController::class, 'shop']);
$router->get('/'.$_shopSlug.'/{slug}',           [FrontendController::class, 'product']);
$router->get('/'.$_cartSlug,                     [FrontendController::class, 'cart']);
$router->post('/'.$_cartSlug.'/add',             [FrontendController::class, 'cartAdd']);
$router->post('/'.$_cartSlug.'/update',          [FrontendController::class, 'cartUpdate']);
$router->post('/'.$_cartSlug.'/remove',          [FrontendController::class, 'cartRemove']);
$router->post('/'.$_cartSlug.'/coupon',          [FrontendController::class, 'cartCoupon']);
$router->get('/'.$_chkSlug,                      [FrontendController::class, 'checkout']);
$router->post('/'.$_chkSlug.'/place',            [FrontendController::class, 'checkoutPlace']);
$router->get('/shop/order-received/{id}',        [FrontendController::class, 'orderReceived']);

unset($_shopSlug, $_cartSlug, $_chkSlug);

// ── Sidebar nav hook ──────────────────────────────────────────────────────────
$hooks->addAction('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $active = str_starts_with($activeNav, 'store') ? 'active' : '';
    echo '<li><a href="'.htmlspecialchars($base.'/manage/store', ENT_QUOTES).'" class="'.$active.'">'
        .'<span class="nav-icon">🛒</span> GoniStore'
        .'</a></li>';
}, 20);
