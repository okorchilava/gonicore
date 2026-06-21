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
    $rows = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gs_products'"
    );
    if ((int)($rows[0]['cnt'] ?? 0) === 0) {
        $migration = require $pluginDir.'/database/migration.php';
        $migration->up($conn);
    }
    // v2: sale date columns
    $colRows = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gs_products' AND COLUMN_NAME = 'sale_from'");
    if ((int)($colRows[0]['cnt'] ?? 0) === 0) {
        $conn->execute("ALTER TABLE `gs_products`
            ADD COLUMN `sale_from` DATETIME NULL DEFAULT NULL AFTER `sale_price`,
            ADD COLUMN `sale_to`   DATETIME NULL DEFAULT NULL AFTER `sale_from`");
    }
    // v3: payment transaction id on orders
    $colRows2 = $conn->query("SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'gs_orders' AND COLUMN_NAME = 'transaction_id'");
    if ((int)($colRows2[0]['cnt'] ?? 0) === 0) {
        $conn->execute("ALTER TABLE `gs_orders`
            ADD COLUMN `transaction_id` VARCHAR(255) NULL DEFAULT NULL AFTER `payment_method`");
    }

} catch (\Throwable) {}

// ── Auto-create store pages (once; flag stored in gs_settings) ───────────────
try {
    $qb = $container->get(QueryBuilder::class);

    $flag = $qb->table('gs_settings')->where('key', '=', 'store_pages_created')->first();
    if (!$flag || $flag['value'] !== '1') {
        $user     = $qb->table('users')->orderBy('id', 'ASC')->first();
        $authorId = $user ? (int) $user['id'] : 1;

        foreach ([
            ['shop',     'Shop'],
            ['cart',     'Cart'],
            ['checkout', 'Checkout'],
        ] as [$slug, $title]) {
            if (!$qb->table('posts')->where('slug', '=', $slug)->first()) {
                $qb->table('posts')->insert([
                    'type'      => 'page',
                    'title'     => $title,
                    'slug'      => $slug,
                    'content'   => '',
                    'status'    => 'published',
                    'author_id' => $authorId,
                ]);
            }
        }

        if ($flag) {
            $qb->table('gs_settings')->where('key', '=', 'store_pages_created')->update(['value' => '1']);
        } else {
            $qb->table('gs_settings')->insert(['key' => 'store_pages_created', 'value' => '1']);
        }
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

// ── Redirect /page/{store-slug} → /{store-slug} ──────────────────────────────
gc_filter('page.intercept', static function (mixed $existing, array $post, \GoniCore\Core\Http\Request $request) use ($container): mixed {
    try {
        $store = $container->get(StoreService::class);
        $storeSlugs = [
            $store->setting('shop_page_slug', 'shop'),
            $store->setting('cart_page_slug', 'cart'),
            $store->setting('checkout_page_slug', 'checkout'),
        ];
        if (in_array($post['slug'], $storeSlugs, true)) {
            return \GoniCore\Core\Http\Response::redirect($request->basePath() . '/' . $post['slug']);
        }
    } catch (\Throwable) {}
    return $existing;
});

// ── Cart badge in site header ─────────────────────────────────────────────────
gc_on('theme.nav.extra', static function (string $base) use ($container): void {
    try {
        $cartSlug = $container->get(StoreService::class)->setting('cart_page_slug', 'cart');
    } catch (\Throwable) {
        $cartSlug = 'cart';
    }
    if (session_status() === PHP_SESSION_NONE) @session_start();
    $count = (int) array_sum(array_column($_SESSION['gs_cart'] ?? [], 'qty'));
    $url   = htmlspecialchars(rtrim($base, '/') . '/' . $cartSlug, ENT_QUOTES);
    echo '<a href="' . $url . '" aria-label="Cart" style="position:relative;display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;color:var(--muted);text-decoration:none;transition:background .15s" onmouseover="this.style.background=\'var(--surface)\'" onmouseout="this.style.background=\'transparent\'">';
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 01-8 0"/></svg>';
    if ($count > 0) {
        echo '<span style="position:absolute;top:1px;right:1px;background:#ef4444;color:#fff;font-size:9px;font-weight:800;border-radius:50%;min-width:15px;height:15px;display:flex;align-items:center;justify-content:center;line-height:1;padding:0 2px">' . $count . '</span>';
    }
    echo '</a>';
}, 20);

// ── Sidebar nav hook ──────────────────────────────────────────────────────────
gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $active = str_starts_with($activeNav, 'store') ? 'active' : '';
    echo '<li><a href="'.htmlspecialchars($base.'/manage/store', ENT_QUOTES).'" class="'.$active.'">'
        .'<span class="nav-icon">🛒</span> GoniStore'
        .'</a></li>';
}, 20);
