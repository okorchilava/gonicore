<?php

declare(strict_types=1);

use BogPayment\BogService;
use BogPayment\BogController;
use GoniCore\Core\Database\Connection;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;
use GoniCore\Core\Shortcodes\ShortcodeManager;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Shared\Contracts\ShortcodeInterface;

// ── Autoloader ────────────────────────────────────────────────────────────────

spl_autoload_register(function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'BogPayment\\')) return;
    $rel  = substr($class, strlen('BogPayment\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── DB migration ──────────────────────────────────────────────────────────────

try {
    $conn = $container->get(Connection::class);
    $rows = $conn->query(
        "SELECT COUNT(*) AS cnt FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bog_transactions'"
    );
    if ((int)($rows[0]['cnt'] ?? 0) === 0) {
        $migration = require $pluginDir . '/database/migration.php';
        $migration->up($conn);
    }
} catch (\Throwable) {}

// ── DI bindings ───────────────────────────────────────────────────────────────

$container->singleton(BogService::class,
    static fn($c) => new BogService($c->get(QueryBuilder::class))
);

$container->bind(BogController::class,
    static fn($c) => new BogController(
        $c->get(BogService::class),
        $c->get(QueryBuilder::class),
        $c->get(LoginService::class),
        $c->get(HookManager::class),
        (string) $c->get(\GoniCore\Core\Config\Config::class)->get('app.name', 'GoniCore'),
    )
);

// ── Public routes ─────────────────────────────────────────────────────────────

$router->get('/bog/success',   [BogController::class, 'success']);
$router->get('/bog/fail',      [BogController::class, 'fail']);
$router->post('/bog/callback', [BogController::class, 'callback']);
$router->post('/bog/pay',      [BogController::class, 'pay']);       // shortcode

// ── Admin routes ──────────────────────────────────────────────────────────────

$router->get('/manage/store/bog-settings',  [BogController::class, 'settingsForm']);
$router->post('/manage/store/bog-settings', [BogController::class, 'settingsSave']);

$router->get('/manage/store/bog-transactions',          [BogController::class, 'transactionsList']);
$router->get('/manage/store/bog-transactions/{id}',     [BogController::class, 'transactionDetail']);
$router->post('/manage/store/bog-transactions/{id}/refund',  [BogController::class, 'refund']);
$router->post('/manage/store/bog-transactions/{id}/approve', [BogController::class, 'preAuthApprove']);
$router->post('/manage/store/bog-transactions/{id}/cancel',  [BogController::class, 'preAuthCancel']);

// ── Register payment method at checkout ───────────────────────────────────────

gc_filter('store.payment.methods', static function (array $methods) use ($container): array {
    $bog = $container->get(BogService::class);
    if (!$bog->isEnabled()) return $methods;
    $methods['bog'] = [
        'icon'  => '🏦',
        'label' => 'Bank of Georgia',
        'desc'  => 'Pay securely via BOG (card, internet banking, Google Pay).',
    ];
    return $methods;
}, 10);

// ── Process payment when GoniStore order is placed ────────────────────────────

gc_filter('store.payment.process', static function (
    mixed   $existing,
    string  $paymentMethod,
    int     $orderId,
    float   $total,
    array   $billing,
    array   $items,
    Request $request
) use ($container): mixed {

    if ($paymentMethod !== 'bog') return $existing;

    /** @var BogService $bog */
    $bog     = $container->get(BogService::class);
    $base    = $request->basePath();
    $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host    = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $absBase = rtrim($scheme . '://' . $host . $base, '/');

    // Build basket from order items
    $basket = array_map(static fn($item) => [
        'product_id'  => (string) ($item['product_id'] ?? 0),
        'description' => (string) ($item['name']       ?? ''),
        'quantity'    => max(1, (int) ($item['quantity'] ?? 1)),
        'unit_price'  => round((float) ($item['price']  ?? 0), 2),
        'total_price' => round((float) ($item['total']  ?? 0), 2),
    ], $items);

    // Optional: buyer info
    $buyer = [];
    if (!empty($billing['email'])) {
        $buyer['masked_email'] = $billing['email'];
        $buyer['full_name']    = trim(($billing['first_name'] ?? '') . ' ' . ($billing['last_name'] ?? ''));
        if (!empty($billing['phone'])) $buyer['masked_phone'] = $billing['phone'];
    }

    $capture = $bog->setting('capture', 'automatic');

    $result = $bog->createOrder(
        externalOrderId: (string) $orderId,
        total:           $total,
        currency:        $bog->setting('currency', 'GEL'),
        basket:          $basket,
        callbackUrl:     $absBase . '/bog/callback',
        successUrl:      $absBase . '/bog/success',
        failUrl:         $absBase . '/bog/fail',
        buyer:           $buyer,
        capture:         $capture,
    );

    if (!$result) {
        return Response::redirect(
            $base . '/' . $bog->setting('checkout_slug', 'checkout')
            . '?error=' . urlencode('Could not connect to BOG payment gateway. Please try again.')
        );
    }

    // Store transaction
    $bog->txCreate([
        'bog_order_id'      => $result['bog_order_id'],
        'external_order_id' => (string) $orderId,
        'amount'            => $total,
        'currency'          => $bog->setting('currency', 'GEL'),
        'status'            => 'created',
    ]);

    // Save BOG order ID in gs_orders.transaction_id
    try {
        $container->get(QueryBuilder::class)
            ->table('gs_orders')
            ->where('id', '=', $orderId)
            ->update(['transaction_id' => $result['bog_order_id']]);
    } catch (\Throwable) {}

    return Response::redirect($result['redirect_url']);
}, 10);

// ── Shortcode: [bog_payment amount="100" ...] ─────────────────────────────────

try {
    $shortcodeMgr = $container->get(ShortcodeManager::class);
    $shortcodeMgr->register(new class($container, $pluginDir) implements ShortcodeInterface {
        public function __construct(
            private readonly \GoniCore\Core\Container\Container $c,
            private readonly string $pluginDir,
        ) {}

        public function getTag(): string { return 'bog_payment'; }

        public function render(array $attrs, string $content): string
        {
            /** @var BogService $bog */
            $bog = $this->c->get(BogService::class);
            if (!$bog->isEnabled()) return '';

            $amount      = (float)  ($attrs['amount']      ?? 0);
            $currency    = strtoupper((string) ($attrs['currency']    ?? $bog->setting('currency','GEL')));
            $description = (string) ($attrs['description'] ?? '');
            $orderId     = (string) ($attrs['order_id']    ?? '');
            $btnText     = (string) ($attrs['button']      ?? 'Pay with BOG');
            $success     = (string) ($attrs['success']     ?? '');
            $fail        = (string) ($attrs['fail']        ?? '');

            if ($amount <= 0) return '<!-- bog_payment: amount required -->';

            $action = rtrim((string)($_SERVER['REQUEST_URI'] ?? ''), '?');
            // Use absolute base from SERVER
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
            $script = (string) ($_SERVER['SCRIPT_NAME'] ?? '');
            // Remove /index.php if present
            $basePath = rtrim(str_replace('/index.php', '', dirname($script)), '/');
            $payUrl   = $scheme . '://' . $host . $basePath . '/bog/pay';

            $icon = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20"/></svg>';

            ob_start(); ?>
<form method="POST" action="<?= htmlspecialchars($payUrl, ENT_QUOTES) ?>" class="bog-pay-form" style="display:inline-block">
    <input type="hidden" name="amount"      value="<?= htmlspecialchars((string)$amount,      ENT_QUOTES) ?>">
    <input type="hidden" name="currency"    value="<?= htmlspecialchars($currency,            ENT_QUOTES) ?>">
    <input type="hidden" name="description" value="<?= htmlspecialchars($description,         ENT_QUOTES) ?>">
    <input type="hidden" name="order_id"    value="<?= htmlspecialchars($orderId ?: 'BOG-' . time(), ENT_QUOTES) ?>">
    <?php if ($success): ?><input type="hidden" name="success" value="<?= htmlspecialchars($success, ENT_QUOTES) ?>"><?php endif ?>
    <?php if ($fail):    ?><input type="hidden" name="fail"    value="<?= htmlspecialchars($fail,    ENT_QUOTES) ?>"><?php endif ?>
    <button type="submit" style="display:inline-flex;align-items:center;gap:8px;background:#0f172a;color:#fff;border:none;border-radius:9px;padding:12px 24px;font-size:15px;font-weight:700;cursor:pointer;font-family:system-ui,sans-serif;transition:background .15s" onmouseover="this.style.background='#1e293b'" onmouseout="this.style.background='#0f172a'">
        <?= $icon ?>
        <?= htmlspecialchars($btnText, ENT_QUOTES) ?>
        <span style="margin-left:4px;font-size:13px;opacity:.7"><?= htmlspecialchars(number_format($amount,2).' '.$currency, ENT_QUOTES) ?></span>
    </button>
</form>
<?php
            return (string) ob_get_clean();
        }
    });
} catch (\Throwable) {}

// ── Admin sidebar nav ─────────────────────────────────────────────────────────

gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $isTx  = str_starts_with($activeNav, 'bog-transactions') ? 'active' : '';
    $isCfg = $activeNav === 'bog-settings' ? 'active' : '';
    echo '<li><a href="' . htmlspecialchars($base . '/manage/store/bog-transactions', ENT_QUOTES) . '" class="' . $isTx . '">'
       . '<span class="nav-icon">💳</span> BOG Transactions'
       . '</a></li>';
    echo '<li><a href="' . htmlspecialchars($base . '/manage/store/bog-settings', ENT_QUOTES) . '" class="' . $isCfg . '">'
       . '<span class="nav-icon">🏦</span> BOG Settings'
       . '</a></li>';
}, 25);
