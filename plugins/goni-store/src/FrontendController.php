<?php
declare(strict_types=1);

namespace GoniStore;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;

final class FrontendController
{
    private string $viewsDir;

    public function __construct(private readonly StoreService $store)
    {
        $this->viewsDir = dirname(__DIR__).'/views/frontend';
    }

    private function view(Request $r, string $tpl, array $data = []): Response
    {
        $file = $this->viewsDir.'/'.$tpl.'.php';
        if (!is_file($file)) return Response::error("Store view not found: $tpl", 500);

        $themeViews = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeViews . '/helpers.php';

        $base     = $r->basePath();
        $settings = $this->store->settings();
        $cats     = $this->store->allCategories();
        $store    = $this->store;

        // ── Pull theme services so header/footer render correctly ─────────────
        try {
            $c             = gc_container();
            $siteName      = $c->get(\GoniCore\Modules\Settings\SettingsService::class)->siteName() ?: 'GoniCore';
            $langService   = $c->get(\GoniCore\Modules\Language\LanguageService::class);
            $langService->boot($r);
            $menuService   = $c->get(\GoniCore\Modules\Menu\MenuService::class);
            $widgetService = $c->get(\GoniCore\Modules\Widget\WidgetService::class);
            $categories    = $c->get(\GoniCore\Modules\Category\CategoryRepository::class)->findAll();
            $hooks         = $c->get(\GoniCore\Core\Hooks\HookManager::class);
        } catch (\Throwable) {
            $siteName      = 'GoniCore';
            $langService   = null;
            $menuService   = null;
            $widgetService = null;
            $categories    = [];
            $hooks         = null;
        }

        extract($data, EXTR_SKIP);

        // ── Render inner view → $content ──────────────────────────────────────
        ob_start();
        try {
            include $file;
            $content = (string) ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }

        // ── Wrap in theme layout (header + content + footer) ──────────────────
        ob_start();
        try {
            include $themeViews . '/layout.php';
            return Response::html((string) ob_get_clean());
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }

    // GET /shop
    public function shop(Request $r): Response
    {
        $page     = max(1,(int)$r->query('page','1'));
        $perPage  = (int)$this->store->setting('products_per_page','12');
        $cat      = $r->query('category','');
        $data     = $this->store->allProducts(
            array_filter(['status'=>'published','category_id'=>$cat ? ($this->store->getCategoryBySlug($cat)['id'] ?? null) : null]),
            $page, $perPage
        );
        $cats     = $this->store->allCategories();
        $pageTitle= 'Shop';
        return $this->view($r, 'shop', array_merge($data, compact('page','cat','cats','pageTitle')));
    }

    // GET /shop/{slug}
    public function product(Request $r): Response
    {
        $slug    = (string)$r->getAttribute('slug');
        $product = $this->store->getProductBySlug($slug);
        if (!$product) return Response::redirect($r->basePath().'/'.$this->store->setting('shop_page_slug','shop'));
        $variations = $product['type'] === 'variable' ? $this->store->getVariations((int)$product['id']) : [];
        $related    = $this->store->productsByCategory($this->store->getCategory((int)($product['category_id'] ?? 0))['slug'] ?? '', 4);
        $related    = array_filter($related, fn($p) => $p['id'] !== $product['id']);
        $pageTitle  = $product['name'];
        return $this->view($r, 'product', compact('product','variations','related','pageTitle'));
    }

    // GET /cart
    public function cart(Request $r): Response
    {
        $cart   = $this->store->getCart();
        $totals = $this->store->cartTotals();
        $couponMsg = $r->query('coupon_msg','');
        $pageTitle = 'Cart';
        return $this->view($r, 'cart', compact('cart','totals','couponMsg','pageTitle'));
    }

    // POST /cart/add
    public function cartAdd(Request $r): Response
    {
        $productId   = (int)$r->post('product_id',0);
        $qty         = max(1,(int)$r->post('quantity',1));
        $variationId = $r->post('variation_id') ? (int)$r->post('variation_id') : null;
        if ($productId) $this->store->addToCart($productId, $qty, $variationId);
        $redirect = $r->post('redirect') ?: $r->basePath().'/'.$this->store->setting('cart_page_slug','cart');
        return Response::redirect($redirect);
    }

    // POST /cart/update
    public function cartUpdate(Request $r): Response
    {
        $updates = $r->post('qty') ?? [];
        if (is_array($updates)) {
            foreach ($updates as $key => $qty) {
                $this->store->updateCartItem((string)$key, (int)$qty);
            }
        }
        return Response::redirect($r->basePath().'/'.$this->store->setting('cart_page_slug','cart'));
    }

    // POST /cart/remove
    public function cartRemove(Request $r): Response
    {
        $key = (string)$r->post('key','');
        if ($key) $this->store->removeCartItem($key);
        return Response::redirect($r->basePath().'/'.$this->store->setting('cart_page_slug','cart'));
    }

    // POST /cart/coupon
    public function cartCoupon(Request $r): Response
    {
        $code   = strtoupper(trim((string)$r->post('coupon_code','')));
        $result = $this->store->applyCoupon($code);
        if ($result['ok']) {
            if (session_status()===PHP_SESSION_NONE) session_start();
            $_SESSION['gs_coupon'] = ['code'=>$code,'discount'=>$result['discount'],'coupon'=>$result['coupon']];
            $msg = 'Coupon applied! Discount: '.$this->store->formatPrice($result['discount']);
        } else {
            $msg = $result['error'];
        }
        return Response::redirect($r->basePath().'/'.$this->store->setting('cart_page_slug','cart').'?coupon_msg='.urlencode($msg));
    }

    // GET /checkout
    public function checkout(Request $r): Response
    {
        $cart   = $this->store->getCart();
        if (empty($cart)) return Response::redirect($r->basePath().'/'.$this->store->setting('cart_page_slug','cart'));
        $totals = $this->store->cartTotals();
        if (session_status()===PHP_SESSION_NONE) session_start();
        $coupon = $_SESSION['gs_coupon'] ?? null;
        if ($coupon) $totals['total'] -= $coupon['discount'];
        $error  = $r->query('error','');
        $pageTitle = 'Checkout';

        // Payment plugins register their methods via this filter
        $paymentMethods = gc_apply('store.payment.methods', [
            'cod' => [
                'icon'  => '💵',
                'label' => 'Cash on Delivery',
                'desc'  => 'Pay when you receive your order.',
            ],
            'bank_transfer' => [
                'icon'  => '🏦',
                'label' => 'Bank Transfer',
                'desc'  => 'Make a direct bank transfer. Details will follow.',
            ],
        ]);

        return $this->view($r, 'checkout', compact('cart','totals','coupon','error','pageTitle','paymentMethods'));
    }

    // POST /checkout/place
    public function checkoutPlace(Request $r): Response
    {
        $cart = $this->store->getCart();
        if (empty($cart)) return Response::redirect($r->basePath().'/'.$this->store->setting('checkout_page_slug','checkout'));

        if (session_status()===PHP_SESSION_NONE) session_start();
        $coupon = $_SESSION['gs_coupon'] ?? null;

        $billing = [
            'first_name' => $r->post('billing_first_name',''),
            'last_name'  => $r->post('billing_last_name',''),
            'email'      => $r->post('billing_email',''),
            'phone'      => $r->post('billing_phone',''),
            'address'    => $r->post('billing_address',''),
            'city'       => $r->post('billing_city',''),
            'state'      => $r->post('billing_state',''),
            'zip'        => $r->post('billing_zip',''),
            'country'    => $r->post('billing_country',''),
        ];

        if (empty($billing['email']) || empty($billing['first_name'])) {
            return Response::redirect($r->basePath().'/'.$this->store->setting('checkout_page_slug','checkout').'?error='.urlencode('Please fill required fields.'));
        }

        $totals    = $this->store->cartTotals();
        $discount  = $coupon ? (float)$coupon['discount'] : 0.0;
        $total     = $totals['total'] - $discount;

        $items = [];
        foreach ($cart as $item) {
            $items[] = [
                'product_id'   => $item['product_id'],
                'variation_id' => $item['variation_id'],
                'name'         => $item['name'],
                'sku'          => $item['sku'],
                'quantity'     => $item['qty'],
                'price'        => $item['price'],
                'total'        => $item['price'] * $item['qty'],
                'attributes'   => $item['attrs'],
            ];
        }

        $paymentMethod = (string) $r->post('payment_method', 'cod');

        $orderId = $this->store->createOrder([
            'status'         => 'pending',
            'subtotal'       => $totals['subtotal'],
            'tax'            => $totals['tax'],
            'shipping_cost'  => $totals['shipping'],
            'discount'       => $discount,
            'total'          => max(0.0, $total),
            'currency'       => $this->store->setting('currency', 'GEL'),
            'billing'        => json_encode($billing),
            'shipping'       => json_encode($billing),
            'payment_method' => $paymentMethod,
            'customer_note'  => $r->post('customer_note', ''),
            'coupon_code'    => $coupon['code'] ?? '',
            'ip_address'     => $_SERVER['REMOTE_ADDR'] ?? '',
        ], $items);

        // Allow payment plugins to take over (e.g. redirect to payment gateway)
        // Filter receives: (null, paymentMethod, orderId, total, billing, request)
        // Return a Response to redirect; return null to use default flow.
        $paymentRedirect = gc_apply('store.payment.process', null,
            $paymentMethod, $orderId, max(0.0, $total), $billing, $items, $r
        );

        if ($paymentRedirect instanceof Response) {
            // Gateway redirect: cart cleared only after confirmed payment (see success handler)
            return $paymentRedirect;
        }

        // Non-gateway payment (COD, bank transfer): clear cart immediately
        $this->store->clearCart();
        unset($_SESSION['gs_coupon']);

        return Response::redirect($r->basePath() . '/shop/order-received/' . $orderId);
    }

    // GET /shop/order-received/{id}
    public function orderReceived(Request $r): Response
    {
        $id    = (int)$r->getAttribute('id');
        $order = $this->store->getOrder($id);
        if (!$order) return Response::redirect($r->basePath().'/shop');
        $pageTitle = 'Order Confirmed';
        return $this->view($r, 'order_received', compact('order','pageTitle'));
    }
}
