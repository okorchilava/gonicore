<?php
declare(strict_types=1);

namespace GoniDelivery;

use GoniCore\Core\Http\Request;
use GoniCore\Core\Http\Response;

final class FrontendController
{
    private string $viewsDir;

    public function __construct(private readonly DeliveryService $delivery)
    {
        $this->viewsDir = dirname(__DIR__) . '/views/frontend';
    }

    // ── Place order (legacy courier) ─────────────────────────────────────────

    public function place(Request $r): Response
    {
        $base = $r->basePath();
        $slug = $this->delivery->setting('page_slug', 'delivery');

        $type          = in_array($r->post('type'), ['courier','food']) ? (string) $r->post('type') : 'courier';
        $senderName    = trim((string) $r->post('sender_name', ''));
        $senderPhone   = trim((string) $r->post('sender_phone', ''));
        $senderEmail   = trim((string) $r->post('sender_email', ''));
        $pickupAddr    = trim((string) $r->post('pickup_address', ''));
        $recipientName = trim((string) $r->post('recipient_name', ''));
        $recipientPhone= trim((string) $r->post('recipient_phone', ''));
        $deliveryAddr  = trim((string) $r->post('delivery_address', ''));
        $zoneId        = $r->post('zone_id') ? (int) $r->post('zone_id') : null;
        $payment       = in_array($r->post('payment_method'), ['cash','bog']) ? (string) $r->post('payment_method') : 'cash';
        $note          = trim((string) $r->post('customer_note', ''));

        if (!$senderPhone || !$deliveryAddr) {
            return Response::redirect($base . '/' . $slug . '?error=' . urlencode('Please fill required fields.'));
        }

        $zone  = $zoneId ? $this->delivery->getZone($zoneId) : null;
        $price = $zone ? (float) $zone['price'] : (float) $this->delivery->setting('base_fee', '3');

        $packageType   = trim((string) $r->post('package_type', ''));
        $packageWeight = $r->post('package_weight') !== '' ? (float) $r->post('package_weight') : null;
        $packageDesc   = trim((string) $r->post('package_desc', ''));

        $orderId = $this->delivery->createOrder([
            'type'             => $type,
            'sender_name'      => $senderName,
            'sender_phone'     => $senderPhone,
            'sender_email'     => $senderEmail,
            'pickup_address'   => $pickupAddr,
            'recipient_name'   => $recipientName,
            'recipient_phone'  => $recipientPhone,
            'delivery_address' => $deliveryAddr,
            'zone_id'          => $zoneId,
            'package_type'     => $packageType,
            'package_weight'   => $packageWeight,
            'package_desc'     => $packageDesc,
            'price'            => $price,
            'currency'         => $this->delivery->setting('currency', 'GEL'),
            'payment_method'   => $payment,
            'payment_status'   => 'unpaid',
            'status'           => 'pending',
            'customer_note'    => $note,
            'ip_address'       => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        $order = $this->delivery->getOrder($orderId);

        // BOG payment
        if ($payment === 'bog' && $order) {
            try {
                $bog = gc_container()->get(\BogPayment\BogService::class);
                $scheme  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                $host    = (string) ($_SERVER['HTTP_HOST'] ?? 'localhost');
                $absBase = rtrim($scheme . '://' . $host . $base, '/');
                $result  = $bog->createOrder(
                    externalOrderId: 'gd-' . $orderId,
                    total:           $price,
                    currency:        $this->delivery->setting('currency', 'GEL'),
                    basket:          [['product_id'=>'delivery','description'=>'Delivery','quantity'=>1,'unit_price'=>$price,'total_price'=>$price]],
                    callbackUrl:     $absBase . '/delivery/bog-callback',
                    successUrl:      $absBase . '/delivery/track/' . urlencode($order['track_token'] ?? $order['order_number']) . '?payment=bog',
                    failUrl:         $absBase . '/' . $slug . '?error=' . urlencode('Payment failed. Please try again.'),
                );
                if ($result) {
                    $this->delivery->updateOrder($orderId, ['transaction_id' => $result['bog_order_id']]);
                    return Response::redirect($result['redirect_url']);
                }
            } catch (\Throwable $e) {
                error_log('[GoniDelivery] BOG error: ' . $e->getMessage());
            }
        }

        return Response::redirect($base . '/delivery/track/' . urlencode($order['track_token'] ?? $order['order_number']));
    }

    // ── Tracking ──────────────────────────────────────────────────────────────

    public function track(Request $r): Response
    {
        $token  = (string) $r->getAttribute('token');
        $order  = $this->delivery->getOrderByToken($token);
        if (!$order) {
            return Response::redirect($r->basePath() . '/' . $this->delivery->setting('page_slug', 'delivery') . '?error=' . urlencode('Order not found.'));
        }

        // Verify BOG on return
        if ($r->query('payment') === 'bog' && $order['payment_status'] === 'unpaid') {
            try {
                $bog = gc_container()->get(\BogPayment\BogService::class);
                $receipt = $order['transaction_id'] ? $bog->getReceipt($order['transaction_id']) : null;
                if ($receipt && ($receipt['order_status']['key'] ?? '') === 'completed') {
                    $this->delivery->updateOrder((int) $order['id'], ['payment_status' => 'paid', 'status' => 'accepted']);
                    $order['payment_status'] = 'paid';
                    $order['status']         = 'accepted';
                }
            } catch (\Throwable) {}
        }

        $order['items']  = $this->delivery->orderItems((int)$order['id']);
        $vendor          = $order['vendor_id'] ? $this->delivery->getVendor((int)$order['vendor_id']) : null;
        $sym             = $this->delivery->setting('currency_symbol', '₾');
        $slug            = $this->delivery->setting('page_slug', 'delivery');
        return $this->view($r, 'track', compact('order', 'vendor', 'sym', 'slug'), 'შეკვეთის თვალყური');
    }

    // ── BOG Callback ──────────────────────────────────────────────────────────

    public function bogCallback(Request $r): Response
    {
        $rawBody   = $r->body();
        $sigHeader = (string) ($_SERVER['HTTP_CALLBACK_SIGNATURE'] ?? '');
        try {
            $bog = gc_container()->get(\BogPayment\BogService::class);
            if (!$bog->verifySignature($rawBody, $sigHeader)) return Response::html('', 400);
        } catch (\Throwable) { return Response::html('', 400); }

        $payload = json_decode($rawBody, true);
        if (!is_array($payload) || ($payload['event'] ?? '') !== 'order_payment') return Response::html('', 200);

        $body       = $payload['body'] ?? [];
        $externalId = (string) ($body['external_order_id'] ?? '');
        $statusKey  = (string) ($body['order_status']['key'] ?? '');

        if (!str_starts_with($externalId, 'gd-')) return Response::html('', 200);

        $orderId = (int) substr($externalId, 3);
        if ($statusKey === 'completed') {
            $this->delivery->updateOrder($orderId, ['payment_status' => 'paid', 'status' => 'accepted']);
        } elseif (in_array($statusKey, ['rejected', 'expired'], true)) {
            $this->delivery->updateOrder($orderId, ['status' => 'cancelled']);
        }

        return Response::html('', 200);
    }

    // ── Live Map API ─────────────────────────────────────────────────────────

    public function apiLiveMapData(Request $r): Response
    {
        $drivers = $this->delivery->allDrivers();
        $orders  = $this->delivery->allOrders(1, 200)['items'];
        return Response::json(['drivers' => $drivers, 'orders' => $orders]);
    }

    // ── Customer Auth ─────────────────────────────────────────────────────────

    private function sessionStart(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name('gd_session'); session_start();
        }
    }

    private function currentCustomer(): ?array
    {
        $this->sessionStart();
        $id = $_SESSION['gd_customer_id'] ?? null;
        return $id ? $this->delivery->getCustomerById((int)$id) : null;
    }

    public function authPage(Request $r): Response
    {
        if ($this->currentCustomer()) return Response::redirect($r->basePath().'/'.$this->delivery->setting('page_slug','delivery'));
        $step  = $r->query('step','phone');
        $phone = $r->query('phone','');
        $error = $r->query('error','');
        return $this->view($r,'auth',compact('step','phone','error'),'შესვლა');
    }

    public function logout(Request $r): Response
    {
        $this->sessionStart();
        unset($_SESSION['gd_customer_id']);
        $slug = $this->delivery->setting('page_slug','delivery');
        return Response::redirect($r->basePath().'/'.$slug.'/auth');
    }

    public function apiOtpSend(Request $r): Response
    {
        $body  = $r->json();
        $phone = trim((string)($r->post('phone') ?? $body['phone'] ?? ''));
        if (!$phone) return Response::json(['ok'=>false,'error'=>'Phone required']);
        $code    = $this->delivery->generateOtp($phone);
        $devMode = filter_var($_ENV['APP_DEBUG'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
        return Response::json(['ok'=>true, 'dev_code'=> $devMode ? $code : null]);
    }

    public function apiOtpVerify(Request $r): Response
    {
        $body  = $r->json();
        $phone = trim((string)($r->post('phone') ?? $body['phone'] ?? ''));
        $code  = trim((string)($r->post('code')  ?? $body['code']  ?? ''));
        $name  = trim((string)($r->post('name')  ?? $body['name']  ?? ''));
        $slug  = $this->delivery->setting('page_slug','delivery');
        $base  = $r->basePath();
        if (!$phone || !$code) return Response::json(['ok'=>false,'error'=>'Phone and code required']);
        if (!$this->delivery->verifyOtp($phone,$code)) return Response::json(['ok'=>false,'error'=>'Invalid or expired code']);
        $customer = $this->delivery->getOrCreateCustomer($phone,$name);
        $this->sessionStart();
        session_regenerate_id(true);
        $_SESSION['gd_customer_id'] = (int)$customer['id'];
        return Response::json(['ok'=>true,'redirect'=>$base.'/'.$slug]);
    }

    // ── Customer: Vendor list ─────────────────────────────────────────────────

    public function index(Request $r): Response
    {
        $customer = $this->currentCustomer();
        $vendors  = $this->delivery->allVendors(true);
        $slug     = $this->delivery->setting('page_slug','delivery');
        $brand    = $this->delivery->setting('brand_name','GoniDelivery');
        $sym      = $this->delivery->setting('currency_symbol','₾');
        $error    = $r->query('error','');
        return $this->view($r,'index',compact('vendors','customer','slug','brand','sym','error'),'Order Food');
    }

    // ── Customer: Vendor menu + Cart ──────────────────────────────────────────

    public function vendorMenu(Request $r): Response
    {
        $slug   = $this->delivery->setting('page_slug','delivery');
        $vendor = $this->delivery->getVendorBySlug((string)$r->getAttribute('slug'));
        if (!$vendor) return Response::redirect($r->basePath().'/'.$slug.'?error='.urlencode('Vendor not found'));
        $menu     = $this->delivery->vendorMenuGrouped((int)$vendor['id']);
        $customer = $this->currentCustomer();
        $cart     = $this->getCart();
        $brand    = $this->delivery->setting('brand_name','GoniDelivery');
        $sym      = $this->delivery->setting('currency_symbol','₾');
        return $this->view($r,'vendor_menu',compact('vendor','menu','customer','cart','slug','brand','sym'),'Menu');
    }

    // ── Cart (session-based) ──────────────────────────────────────────────────

    private function getCart(): array
    {
        $this->sessionStart();
        return $_SESSION['gd_cart'] ?? [];
    }

    private function saveCart(array $cart): void
    {
        $this->sessionStart();
        $_SESSION['gd_cart'] = $cart;
    }

    public function apiCartAdd(Request $r): Response
    {
        $body       = $r->json();
        $productId  = (int)($body['product_id'] ?? 0);
        $quantity   = max(1,(int)($body['quantity'] ?? 1));
        $modifiers  = (array)($body['modifiers'] ?? []);
        $combos     = (array)($body['combos'] ?? []);   // ['comboId' => [productId, ...], ...]

        $product = $this->delivery->getProduct($productId);
        if (!$product || !$product['in_stock']) return Response::json(['ok'=>false,'error'=>'Product unavailable']);

        // Calculate modifier surcharge
        $modSurcharge = 0.0;
        $modNames = [];
        foreach ($modifiers as $modId) {
            $mod = $this->delivery->qb()->table('gd_modifiers')->where('id','=',(int)$modId)->first();
            if ($mod) {
                $modSurcharge += (float)$mod['price'];
                $grp = $this->delivery->qb()->table('gd_modifier_groups')->where('id','=',(int)$mod['group_id'])->first();
                $modNames[(int)$mod['id']] = [
                    'name'       => $mod['name'],
                    'price'      => (float)$mod['price'],
                    'group_type' => ($grp['type'] ?? 'choice'),
                ];
            }
        }

        // Resolve combo selections — choice/size types may add price_modifier surcharge
        $comboSelections  = [];
        $comboSurcharge   = 0.0;
        foreach ($combos as $comboId => $selectedProductIds) {
            $combo = $this->delivery->getCombo((int)$comboId);
            if (!$combo) continue;
            $comboType = $combo['type'] ?? 'choice';
            if ($comboType === 'included') continue; // handled below
            $comboItemsAll = $this->delivery->comboProducts((int)$comboId);
            $itemMap = [];
            foreach ($comboItemsAll as $ci) $itemMap[(int)$ci['id']] = $ci;
            $picks = [];
            foreach ((array)$selectedProductIds as $pid) {
                $pid = (int)$pid;
                $p   = $this->delivery->getProduct($pid);
                if (!$p) continue;
                $pm = (float)($itemMap[$pid]['price_modifier'] ?? 0);
                $comboSurcharge += $pm;
                $picks[] = ['id'=>$pid,'name'=>$p['name'],'price_modifier'=>$pm];
            }
            if ($picks) $comboSelections[(int)$comboId] = ['name'=>$combo['name'],'type'=>$comboType,'selections'=>$picks];
        }
        // Auto-include ALL products from "included" (mandatory) combo groups attached to this product
        foreach ($this->delivery->productCombos($productId) as $pc) {
            $pcId = (int)$pc['id'];
            if (($pc['type'] ?? '') === 'included' && !isset($comboSelections[$pcId])) {
                $picks = array_map(
                    fn($ci) => ['id'=>(int)$ci['id'], 'name'=>$ci['name'], 'auto'=>true],
                    $pc['products'] ?? []
                );
                if ($picks) $comboSelections[$pcId] = ['name'=>$pc['name'], 'type'=>'included', 'selections'=>$picks];
            }
        }

        $unitPrice = (float)$product['price'] + $modSurcharge + $comboSurcharge;
        $cart = $this->getCart();

        // Check same vendor
        if (!empty($cart) && (int)$cart[array_key_first($cart)]['vendor_id'] !== (int)$product['vendor_id']) {
            return Response::json(['ok'=>false,'error'=>'clear_cart','message'=>'Cart has items from another vendor']);
        }

        $key = $productId . '_' . md5(json_encode($modifiers) . json_encode($combos));
        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
            $cart[$key]['item_total'] = round($cart[$key]['unit_price'] * $cart[$key]['quantity'], 2);
        } else {
            $cart[$key] = [
                'product_id' => $productId,
                'vendor_id'  => (int)$product['vendor_id'],
                'name'       => $product['name'],
                'unit_price' => round($unitPrice, 2),
                'quantity'   => $quantity,
                'modifiers'  => $modNames,
                'combos'     => $comboSelections,
                'item_total' => round($unitPrice * $quantity, 2),
            ];
        }
        $this->saveCart($cart);
        return Response::json(['ok'=>true,'cart'=>$this->cartSummary($cart)]);
    }

    public function apiCartRemove(Request $r): Response
    {
        $body = $r->json();
        $key  = (string)($body['key'] ?? '');
        $cart = $this->getCart();
        unset($cart[$key]);
        $this->saveCart($cart);
        return Response::json(['ok'=>true,'cart'=>$this->cartSummary($cart)]);
    }

    public function apiCartClear(Request $r): Response
    {
        $this->saveCart([]);
        return Response::json(['ok'=>true]);
    }

    public function apiCartGet(Request $r): Response
    {
        return Response::json(['cart'=>$this->cartSummary($this->getCart())]);
    }

    public function apiCartUpdate(Request $r): Response
    {
        $body = $r->json();
        $key  = (string)($body['key'] ?? '');
        $qty  = (int)($body['qty'] ?? 0);
        $cart = $this->getCart();
        if (isset($cart[$key])) {
            if ($qty <= 0) {
                unset($cart[$key]);
            } else {
                $cart[$key]['quantity']   = $qty;
                $cart[$key]['item_total'] = round($cart[$key]['unit_price'] * $qty, 2);
            }
        }
        $this->saveCart($cart);
        return Response::json(['ok'=>true,'cart'=>$this->cartSummary($cart)]);
    }

    public function cartPage(Request $r): Response
    {
        $slug     = $this->delivery->setting('page_slug','delivery');
        $customer = $this->currentCustomer();
        $cart     = $this->delivery->enrichCartModifiers($this->getCart());
        if (empty($cart)) return Response::redirect($r->basePath().'/'.$slug);
        $summary  = $this->cartSummary($cart);
        $vendor   = $summary['vendor_id'] ? $this->delivery->getVendor((int)$summary['vendor_id']) : null;
        $sym      = $this->delivery->setting('currency_symbol','₾');
        $brand    = $this->delivery->setting('brand_name','GoniDelivery');
        $base     = $r->basePath();
        return $this->viewStandalone('cart', compact('cart','summary','vendor','customer','sym','slug','brand','base'));
    }

    private function cartSummary(array $cart): array
    {
        $items     = array_values($cart);
        $subtotal  = round(array_sum(array_column($items,'item_total')), 2);
        $vendorId  = $items ? (int)$items[0]['vendor_id'] : 0;
        $vendor    = $vendorId ? $this->delivery->getVendor($vendorId) : null;
        $delFee    = $vendor ? (float)$vendor['delivery_fee'] : 0.0;
        $freeThr   = $vendor ? (float)$vendor['free_delivery_threshold'] : 0.0;
        if ($freeThr > 0 && $subtotal >= $freeThr) $delFee = 0.0;

        // Below-minimum-order surcharge
        $belowMinFee = 0.0;
        $minOrder    = $vendor ? (float)$vendor['min_order'] : 0.0;
        if ($minOrder > 0 && $subtotal < $minOrder) {
            $belowMinFee = (float)$this->delivery->setting('below_min_surcharge', '0');
        }

        // Weather surcharge (admin-togglable)
        $weatherFee = 0.0;
        if ($this->delivery->setting('weather_surcharge_active', '0') === '1') {
            $weatherFee = (float)$this->delivery->setting('weather_surcharge_amount', '0');
        }

        return [
            'items'          => $items,
            'count'          => count($items),
            'subtotal'       => $subtotal,
            'delivery_fee'   => $delFee,
            'below_min_fee'  => $belowMinFee,
            'weather_fee'    => $weatherFee,
            'km_fee'         => 0.0,   // calculated server-side at order creation
            'total'          => round($subtotal + $delFee + $belowMinFee + $weatherFee, 2),
            'vendor_id'      => $vendorId,
            'vendor_name'    => $vendor['name'] ?? '',
            'vendor_lat'     => $vendor ? ($vendor['lat'] ?? null) : null,
            'vendor_lng'     => $vendor ? ($vendor['lng'] ?? null) : null,
        ];
    }

    // ── Checkout ──────────────────────────────────────────────────────────────

    public function checkout(Request $r): Response
    {
        $slug     = $this->delivery->setting('page_slug','delivery');
        $customer = $this->currentCustomer();
        if (!$customer) return Response::redirect($r->basePath().'/'.$slug.'/auth');
        $cart = $this->delivery->enrichCartModifiers($this->getCart());
        if (empty($cart)) return Response::redirect($r->basePath().'/'.$slug);
        $summary    = $this->cartSummary($cart);
        $sym        = $this->delivery->setting('currency_symbol','₾');
        $brand      = $this->delivery->setting('brand_name','GoniDelivery');
        $perKmRate  = (float)$this->delivery->setting('per_km_rate', '0');
        $perKmThreshold = (float)$this->delivery->setting('per_km_threshold', '5');
        return $this->view($r,'checkout',compact('customer','cart','summary','sym','slug','brand','perKmRate','perKmThreshold'),'Checkout');
    }

    public function checkoutPost(Request $r): Response
    {
        $slug     = $this->delivery->setting('page_slug','delivery');
        $base     = $r->basePath();
        $customer = $this->currentCustomer();
        if (!$customer) return Response::redirect($base.'/'.$slug.'/auth');

        $cart = $this->getCart();
        if (empty($cart)) return Response::redirect($base.'/'.$slug);

        $summary      = $this->cartSummary($cart);
        $deliveryAddr = trim((string)$r->post('delivery_address',''));
        $deliveryLat  = $r->post('delivery_lat') !== '' ? (float)$r->post('delivery_lat') : null;
        $deliveryLng  = $r->post('delivery_lng') !== '' ? (float)$r->post('delivery_lng') : null;
        $note         = trim((string)$r->post('customer_note',''));
        $payment      = (string)$r->post('payment_method','cash');

        if (!$deliveryAddr) return Response::redirect($base.'/'.$slug.'/checkout?error='.urlencode('Enter delivery address'));

        $vendor = $summary['vendor_id'] ? $this->delivery->getVendor((int)$summary['vendor_id']) : null;

        // ── Nearest branch routing ────────────────────────────────────────────
        $branch = null;
        if ($vendor && $deliveryLat && $deliveryLng) {
            $branch = $this->delivery->findNearestBranch((int)$vendor['id'], $deliveryLat, $deliveryLng);
        }
        $pickupAddress = $branch ? (string)$branch['address'] : ($vendor ? (string)$vendor['address'] : '');
        $pickupLat     = $branch ? (float)$branch['lat']      : ($vendor ? ($vendor['lat'] ?? null) : null);
        $pickupLng     = $branch ? (float)$branch['lng']      : ($vendor ? ($vendor['lng'] ?? null) : null);

        // ── Distance-based km surcharge ───────────────────────────────────────
        $kmFee = 0.0;
        $kmRate = (float)$this->delivery->setting('per_km_rate', '0');
        $kmThreshold = (float)$this->delivery->setting('per_km_threshold', '5');
        if ($kmRate > 0 && $pickupLat && $pickupLng && $deliveryLat && $deliveryLng) {
            $distKm = $this->delivery->haversine($pickupLat, $pickupLng, $deliveryLat, $deliveryLng);
            if ($distKm > $kmThreshold) {
                $kmFee = round(($distKm - $kmThreshold) * $kmRate, 2);
            }
        }
        $finalDeliveryFee = round($summary['delivery_fee'] + $kmFee, 2);
        $finalTotal       = round($summary['subtotal'] + $finalDeliveryFee + ($summary['below_min_fee'] ?? 0) + ($summary['weather_fee'] ?? 0), 2);

        $orderId = $this->delivery->createOrder([
            'type'             => 'food',
            'vendor_id'        => $summary['vendor_id'] ?: null,
            'branch_id'        => $branch ? (int)$branch['id'] : null,
            'customer_id'      => (int)$customer['id'],
            'sender_name'      => $customer['name'],
            'sender_phone'     => $customer['phone'],
            'sender_email'     => $customer['email'] ?? '',
            'pickup_address'   => $pickupAddress,
            'pickup_lat'       => $pickupLat,
            'pickup_lng'       => $pickupLng,
            'recipient_name'   => $customer['name'],
            'recipient_phone'  => $customer['phone'],
            'delivery_address' => $deliveryAddr,
            'delivery_lat'     => $deliveryLat,
            'delivery_lng'     => $deliveryLng,
            'subtotal'         => $summary['subtotal'],
            'delivery_fee'     => $finalDeliveryFee,
            'price'            => $finalTotal,
            'currency'         => $this->delivery->setting('currency','GEL'),
            'payment_method'   => $payment,
            'payment_status'   => 'unpaid',
            'status'           => 'pending',
            'vendor_status'    => 'pending',
            'prep_time_minutes'=> $vendor ? (int)$vendor['prep_time_min'] : 20,
            'customer_note'    => $note,
            'ip_address'       => $_SERVER['REMOTE_ADDR'] ?? '',
        ]);

        // Create order items
        $this->delivery->createOrderItems($orderId, array_values($cart));
        $this->saveCart([]);

        $order = $this->delivery->getOrder($orderId);
        return Response::redirect($base.'/delivery/track/'.urlencode($order['track_token'] ?? $order['order_number']));
    }

    // ── Vendor Portal ─────────────────────────────────────────────────────────

    /** AES-128-CBC encrypt branch ID into a URL-safe token. */
    private function branchTokenEncode(string $vendorToken, int $branchId): string
    {
        $key = substr(hash('sha256', 'gd_bt1_' . $vendorToken, true), 0, 16);
        $iv  = random_bytes(16);
        $enc = openssl_encrypt($branchId . ':' . time(), 'AES-128-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return rtrim(strtr(base64_encode($iv . $enc), '+/', '-_'), '=');
    }

    /** Decode & validate a branch token; returns branchId or null on failure. */
    private function branchTokenDecode(string $vendorToken, string $t): ?int
    {
        try {
            $key  = substr(hash('sha256', 'gd_bt1_' . $vendorToken, true), 0, 16);
            $b64  = strtr($t, '-_', '+/');
            $pad  = strlen($b64) % 4;
            if ($pad) $b64 .= str_repeat('=', 4 - $pad); // correct padding
            $raw  = base64_decode($b64, true);
            if ($raw === false || strlen($raw) < 17) return null;
            $plain = openssl_decrypt(substr($raw, 16), 'AES-128-CBC', $key, OPENSSL_RAW_DATA, substr($raw, 0, 16));
            if (!$plain) return null;
            [$bid, $ts] = array_pad(explode(':', $plain, 2), 2, '0');
            if (!$bid || (time() - (int)$ts) > 86400 * 7) return null; // expire after 7 days
            return (int)$bid;
        } catch (\Throwable) {
            return null;
        }
    }

    // ── Branch-verification persistent cookie helpers ─────────────────────────

    private function branchCookieName(string $vendorToken): string
    {
        return 'gdvb_' . substr(hash('sha256', $vendorToken), 0, 12);
    }

    private function setBranchVerifiedCookie(string $vendorToken, int $branchId): void
    {
        $exp = time() + 86400 * 30; // 30 days
        $sig = substr(hash_hmac('sha256', $branchId . ':' . $exp, $vendorToken), 0, 16);
        setcookie($this->branchCookieName($vendorToken), $branchId . ':' . $exp . ':' . $sig, [
            'expires'  => $exp,
            'path'     => '/',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private function getVerifiedBranchFromCookie(string $vendorToken): ?int
    {
        $raw = $_COOKIE[$this->branchCookieName($vendorToken)] ?? '';
        if (!$raw) return null;
        $parts = explode(':', $raw, 3);
        if (count($parts) !== 3) return null;
        [$bid, $exp, $sig] = $parts;
        $expected = substr(hash_hmac('sha256', $bid . ':' . $exp, $vendorToken), 0, 16);
        if (!hash_equals($expected, $sig) || (int)$exp < time() || !(int)$bid) return null;
        return (int)$bid;
    }

    public function vendorPortal(Request $r): Response
    {
        $token    = (string)$r->getAttribute('token');
        $vendor   = $this->delivery->getVendorByToken($token);
        if (!$vendor) return Response::html('<h2>Access denied</h2>', 403);
        $branches = $this->delivery->vendorBranches((int)$vendor['id']);

        // ── Per-vendor session ────────────────────────────────────────────────
        $sName = 'gdvp_' . substr(hash('sha256', $token), 0, 16);
        session_name($sName);
        if (session_status() === PHP_SESSION_NONE) { @session_start(); }

        // ── Branch switch ─────────────────────────────────────────────────────
        if ($r->query('switch') === '1') {
            unset($_SESSION['gd_branch_id']);
            setcookie($this->branchCookieName($token), '', ['expires' => time() - 1, 'path' => '/']);
            return Response::redirect($r->basePath() . '/delivery/portal/' . $token);
        }

        // ── Decode encrypted ?t= token → branchId ────────────────────────────
        $selectedBranch = null;
        $branchId       = null;
        $branchToken    = trim((string)($r->query('t') ?? ''));
        $needsBranchPin = false;

        if (count($branches) === 1) {
            $selectedBranch = $branches[0];
            $branchId       = (int)$selectedBranch['id'];
        } elseif ($branchToken) {
            $decoded = $this->branchTokenDecode($token, $branchToken);
            if ($decoded) {
                foreach ($branches as $b) {
                    if ((int)$b['id'] === $decoded) { $selectedBranch = $b; $branchId = $decoded; break; }
                }
            }
        }

        // Restore session-verified branch when URL has no token
        if (!$selectedBranch && count($branches) > 1) {
            $sessBid = (int)($_SESSION['gd_branch_id'] ?? 0);
            if ($sessBid) {
                foreach ($branches as $b) {
                    if ((int)$b['id'] === $sessBid) { $selectedBranch = $b; $branchId = $sessBid; break; }
                }
            }
        }

        // ── Backward compat: old ?branch=X URL format ────────────────────────
        // Pre-selects the branch and generates a fresh encrypted token so the
        // PIN-entry screen appears immediately (PIN still verified via AJAX).
        if (!$selectedBranch && count($branches) > 1) {
            $legacyBid = (int)($r->query('branch') ?? 0);
            if ($legacyBid) {
                foreach ($branches as $b) {
                    if ((int)$b['id'] === $legacyBid) {
                        $selectedBranch = $b;
                        $branchId       = $legacyBid;
                        $branchToken    = $this->branchTokenEncode($token, $legacyBid);
                        break;
                    }
                }
            }
        }

        // ── Cookie-based branch restore ───────────────────────────────────────
        // If no branch selected yet but a valid 30-day cookie exists, restore it.
        // This handles expired ?t= tokens, session expiry, and bookmarked base URLs.
        if (!$selectedBranch && count($branches) > 1) {
            $cookieBid = $this->getVerifiedBranchFromCookie($token);
            if ($cookieBid) {
                foreach ($branches as $b) {
                    if ((int)$b['id'] === $cookieBid) {
                        $selectedBranch           = $b;
                        $branchId                 = $cookieBid;
                        $branchToken              = $this->branchTokenEncode($token, $cookieBid);
                        $_SESSION['gd_branch_id'] = $cookieBid; // refresh session
                        break;
                    }
                }
            }
        }

        // ── Branch PIN gate ───────────────────────────────────────────────────
        // PIN is verified via AJAX (apiBranchAuth).
        // Check session first (fast), then fall back to 30-day persistent cookie.
        if ($selectedBranch && count($branches) > 1) {
            $alreadyVerified = ((int)($_SESSION['gd_branch_id'] ?? 0)) === $branchId;
            if (!$alreadyVerified) {
                // Check persistent cookie (survives session expiry / browser restarts)
                $cookieBid = $this->getVerifiedBranchFromCookie($token);
                if ($cookieBid === $branchId) {
                    $alreadyVerified = true;
                    $_SESSION['gd_branch_id'] = $branchId; // refresh session while we're here
                }
            }
            if (!$alreadyVerified) {
                $needsBranchPin = true;  // show PIN entry; submission goes to branch-auth API
            }
        }

        // ── Pre-compute encrypted tokens for every branch (for selector links) ─
        $branchTokens = [];
        foreach ($branches as $b) {
            $branchTokens[(int)$b['id']] = $this->branchTokenEncode($token, (int)$b['id']);
        }

        $needsBranchSelect = count($branches) > 1 && !$selectedBranch && !$needsBranchPin;

        // ── Orders + stats ────────────────────────────────────────────────────
        if ($needsBranchSelect || $needsBranchPin) {
            $orders         = [];
            $completedToday = 0;
        } else {
            $orders = $this->delivery->vendorOrders((int)$vendor['id'], '', $branchId ?: null);
            $cq     = $this->delivery->qb()
                ->table('gd_orders')
                ->where('vendor_id',   '=', (int)$vendor['id'])
                ->where('vendor_status','=', 'completed')
                ->where('created_at',  '>=', date('Y-m-d') . ' 00:00:00')
                ->get() ?: [];
            // PHP-level branch filter (same logic as vendorOrders) to include NULL-branch orders
            if ($branchId) {
                $cq = array_filter($cq, function (array $o) use ($branchId): bool {
                    $bid = (int)($o['branch_id'] ?? 0);
                    return $bid === $branchId || $bid === 0;
                });
            }
            $completedToday = count($cq);
        }

        $handoffPins = [];
        foreach ($orders as $o) {
            $handoffPins[(int)$o['id']] = $this->delivery->generateHandoffPin((int)$o['id']);
        }

        $sym = $this->delivery->setting('currency_symbol','₾');
        return $this->viewStandalone('vendor_portal',
            compact('vendor','orders','branches','sym','completedToday','selectedBranch','branchId',
                    'needsBranchSelect','needsBranchPin','branchToken','branchTokens','handoffPins')
            + ['base'=>$r->basePath(),'token'=>$token]);
    }

    /** POST /api/delivery/vendor/{token}/branch-auth — verify branch PIN, set session. */
    public function apiBranchAuth(Request $r): Response
    {
        $token  = (string)$r->getAttribute('token');
        $vendor = $this->delivery->getVendorByToken($token);
        if (!$vendor) return Response::json(['ok'=>false], 403);

        $body     = $r->json();
        $t        = trim((string)($body['t']   ?? ''));
        $pin      = trim((string)($body['pin'] ?? ''));
        $branchId = $this->branchTokenDecode($token, $t);
        if (!$branchId) return Response::json(['ok'=>false,'error'=>'invalid_token'], 400);

        // Confirm branch belongs to this vendor
        $found = false;
        foreach ($this->delivery->vendorBranches((int)$vendor['id']) as $b) {
            if ((int)$b['id'] === $branchId) { $found = true; break; }
        }
        if (!$found) return Response::json(['ok'=>false,'error'=>'invalid_branch'], 403);

        // Verify PIN
        if ($pin !== $this->delivery->generateBranchPin($branchId)) {
            return Response::json(['ok'=>false,'error'=>'invalid_pin','message'=>'PIN კოდი არასწორია'], 422);
        }

        // Record verified branch in session + persistent cookie
        $sName = 'gdvp_' . substr(hash('sha256', $token), 0, 16);
        session_name($sName);
        if (session_status() === PHP_SESSION_NONE) { @session_start(); }
        $_SESSION['gd_branch_id'] = $branchId;
        $this->setBranchVerifiedCookie($token, $branchId); // survives session expiry

        return Response::json(['ok'=>true]);
    }

    public function apiVendorOrderStatus(Request $r): Response
    {
        $vendor  = $this->delivery->getVendorByToken((string)$r->getAttribute('token'));
        if (!$vendor) return Response::json(['ok'=>false],403);
        $orderId = (int)$r->getAttribute('id');
        $order   = $this->delivery->getOrder($orderId);
        if (!$order || (int)$order['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false],403);

        $body     = $r->json();
        $status   = (string)($body['vendor_status'] ?? '');
        $prepTime = isset($body['prep_time']) ? (int)$body['prep_time'] : null;

        // ── Handoff PIN verification for completion ───────────────────────────
        if ($status === 'completed') {
            $submittedPin = trim((string)($body['handoff_pin'] ?? ''));
            $expectedPin  = $this->delivery->generateHandoffPin($orderId);
            if ($submittedPin !== $expectedPin) {
                return Response::json(['ok'=>false,'error'=>'invalid_pin','message'=>'კოდი არასწორია'], 422);
            }
        }

        $update = ['vendor_status' => $status];
        if ($prepTime) {
            $update['prep_time_minutes'] = $prepTime;
            $update['prep_ends_at']      = date('Y-m-d H:i:s', time() + $prepTime * 60);
        }

        $this->delivery->updateOrder($orderId, $update);

        // When vendor marks ready → offer order to a random courier
        if ($status === 'ready') {
            $this->delivery->dispatchOffer($orderId);
        }

        return Response::json(['ok'=>true]);
    }

    // ── Courier Portal ────────────────────────────────────────────────────────

    public function courierPortal(Request $r): Response
    {
        $driver = $this->delivery->qb()->table('gd_drivers')->where('driver_token','=',(string)$r->getAttribute('token'))->first();
        if (!$driver) return Response::html('<h2>Access denied</h2>', 403);

        // Mark driver as online whenever they load the portal
        $this->delivery->qb()->table('gd_drivers')
            ->where('id','=',(int)$driver['id'])
            ->update(['is_online'=>1]);
        $driver['is_online'] = 1;

        // Find active order for this courier
        $order = $this->delivery->qb()->table('gd_orders')
            ->where('driver_id','=',(int)$driver['id'])
            ->where('status','IN',['accepted','picked_up','in_transit'])
            ->orderBy('id','DESC')
            ->first();
        if ($order) $order = $this->delivery->getOrderWithItems((int)$order['id']);

        // Retry any expired offers (lazy housekeeping)
        $this->delivery->retryExpiredOffers();

        // Check for an active offer for this driver
        $offer = $this->delivery->getActiveOfferForDriver((int)$driver['id']);
        $offerOrder = null;
        if ($offer) {
            $offerOrder = $this->delivery->getOrderWithItems((int)$offer['order_id']);
        }

        $sym         = $this->delivery->setting('currency_symbol','₾');
        $token       = (string)$r->getAttribute('token');
        $handoffPin  = $order ? $this->delivery->generateHandoffPin((int)$order['id']) : null;
        return $this->viewStandalone('courier_portal', compact('driver','order','offer','offerOrder','sym','token','handoffPin') + ['base'=>$r->basePath()]);
    }

    public function apiCourierOrderStatus(Request $r): Response
    {
        $driver = $this->delivery->qb()->table('gd_drivers')->where('driver_token','=',(string)$r->getAttribute('token'))->first();
        if (!$driver) return Response::json(['ok'=>false],403);
        $orderId = (int)$r->getAttribute('id');
        $order   = $this->delivery->getOrder($orderId);
        if (!$order || (int)$order['driver_id'] !== (int)$driver['id']) return Response::json(['ok'=>false],403);
        $body   = $r->json();
        $status = (string)($body['status'] ?? '');
        $update = ['status' => $status];
        if ($status === 'delivered') {
            $update['delivered_at']   = date('Y-m-d H:i:s');
            $update['payment_status'] = 'paid';
        }
        $this->delivery->updateOrder($orderId, $update);
        return Response::json(['ok'=>true]);
    }

    public function apiCourierLocation(Request $r): Response
    {
        $driver = $this->delivery->qb()->table('gd_drivers')->where('driver_token','=',(string)$r->getAttribute('token'))->first();
        if (!$driver) return Response::json(['ok'=>false],403);
        $body = $r->json();
        $this->delivery->qb()->table('gd_drivers')->where('id','=',(int)$driver['id'])->update([
            'current_lat'         => (float)($body['lat'] ?? 0),
            'current_lng'         => (float)($body['lng'] ?? 0),
            'location_updated_at' => date('Y-m-d H:i:s'),
        ]);
        return Response::json(['ok'=>true]);
    }

    public function apiCourierToggleOnline(Request $r): Response
    {
        $driver = $this->delivery->qb()->table('gd_drivers')
            ->where('driver_token','=',(string)$r->getAttribute('token'))->first();
        if (!$driver) return Response::json(['ok'=>false], 403);
        $online = (int)!(int)$driver['is_online'];
        $this->delivery->qb()->table('gd_drivers')
            ->where('id','=',(int)$driver['id'])
            ->update(['is_online'=>$online]);
        return Response::json(['ok'=>true,'is_online'=>$online]);
    }

    // ── Courier offer API ────────────────────────────────────────────────────

    /** Poll: returns current active order + any pending offer. Called every ~5 s from the portal. */
    public function apiCourierPoll(Request $r): Response
    {
        $driver = $this->delivery->qb()->table('gd_drivers')
            ->where('driver_token','=',(string)$r->getAttribute('token'))->first();
        if (!$driver) return Response::json(['ok'=>false], 403);

        // Housekeeping — expire stale offers and re-dispatch
        $this->delivery->retryExpiredOffers();

        // Active order for this courier
        $activeOrder = $this->delivery->qb()->table('gd_orders')
            ->where('driver_id','=',(int)$driver['id'])
            ->where('status','IN',['accepted','picked_up','in_transit'])
            ->orderBy('id','DESC')->first();
        if ($activeOrder) $activeOrder = $this->delivery->getOrderWithItems((int)$activeOrder['id']);

        // Pending offer for this driver
        $offer      = $this->delivery->getActiveOfferForDriver((int)$driver['id']);
        $offerOrder = null;
        if ($offer) {
            $offerOrder = $this->delivery->getOrderWithItems((int)$offer['order_id']);
        }

        return Response::json([
            'ok'          => true,
            'is_online'   => (int)$driver['is_online'],
            'active_order'=> $activeOrder,
            'offer'       => (function() use ($offer, $offerOrder) {
                if (!$offer) return null;
                $secLeft = !empty($offer['expires_at'])
                    ? ((int)strtotime($offer['expires_at']) - time())
                    : -1;
                if ($secLeft <= 0) return null;  // Expired by the time we read it — skip
                return [
                    'id'          => (int)$offer['id'],
                    'expires_at'  => $offer['expires_at'],
                    'seconds_left'=> $secLeft,
                    'order'       => $offerOrder,
                ];
            })(),
        ]);
    }

    public function apiCourierOfferAccept(Request $r): Response
    {
        $driver = $this->delivery->qb()->table('gd_drivers')
            ->where('driver_token','=',(string)$r->getAttribute('token'))->first();
        if (!$driver) return Response::json(['ok'=>false], 403);

        $offerId = (int)$r->getAttribute('offer_id');
        $offer   = $this->delivery->qb()->table('gd_order_offers')
            ->where('id','=',$offerId)
            ->where('driver_id','=',(int)$driver['id'])
            ->where('status','=','pending')
            ->first();
        if (!$offer) return Response::json(['ok'=>false,'error'=>'offer_not_found'], 404);

        // Check it hasn't expired
        if (strtotime($offer['expires_at']) < time()) {
            $this->delivery->qb()->table('gd_order_offers')->where('id','=',$offerId)->update(['status'=>'expired']);
            return Response::json(['ok'=>false,'error'=>'offer_expired'], 410);
        }

        $orderId = (int)$offer['order_id'];
        $order   = $this->delivery->getOrder($orderId);
        if (!$order || $order['driver_id']) {
            // Already taken
            $this->delivery->qb()->table('gd_order_offers')->where('id','=',$offerId)->update(['status'=>'expired']);
            return Response::json(['ok'=>false,'error'=>'already_assigned'], 409);
        }

        // Ensure driver token is stored in the orders table
        $driverToken = $driver['driver_token'];

        // Mark offer accepted
        $this->delivery->qb()->table('gd_order_offers')->where('id','=',$offerId)->update(['status'=>'accepted']);

        // Assign courier to order
        $this->delivery->updateOrder($orderId, [
            'driver_id'             => (int)$driver['id'],
            'driver_token'          => $driverToken,
            'status'                => 'accepted',
            'courier_dispatched_at' => date('Y-m-d H:i:s'),
        ]);

        return Response::json(['ok'=>true]);
    }

    public function apiCourierOfferDecline(Request $r): Response
    {
        $driver = $this->delivery->qb()->table('gd_drivers')
            ->where('driver_token','=',(string)$r->getAttribute('token'))->first();
        if (!$driver) return Response::json(['ok'=>false], 403);

        $offerId = (int)$r->getAttribute('offer_id');
        $offer   = $this->delivery->qb()->table('gd_order_offers')
            ->where('id','=',$offerId)
            ->where('driver_id','=',(int)$driver['id'])
            ->where('status','=','pending')
            ->first();
        if (!$offer) return Response::json(['ok'=>false,'error'=>'offer_not_found'], 404);

        // Mark declined
        $this->delivery->qb()->table('gd_order_offers')->where('id','=',$offerId)->update(['status'=>'declined']);

        // Immediately try to find another courier
        $this->delivery->dispatchOffer((int)$offer['order_id']);

        return Response::json(['ok'=>true]);
    }

    // ── Track status API (public, no auth) ───────────────────────────────────

    public function apiTrackStatus(Request $r): Response
    {
        $token  = (string)$r->getAttribute('token');
        $order  = $this->delivery->getOrderByToken($token);
        if (!$order) return Response::json(['ok'=>false], 404);

        // Lazy housekeeping: expire timed-out offers and retry dispatch.
        // Piggybacks on the customer's 4-second poll so it runs even when
        // no courier has their portal open.
        $this->delivery->retryExpiredOffers();

        // Extra guard: order is ready but no courier assigned and no live offer →
        // dispatch now (handles cases where initial dispatch was never triggered
        // or all couriers let their offers expire without anyone polling the courier portal).
        if (
            !empty($order['vendor_status']) &&
            $order['vendor_status'] === 'ready' &&
            empty($order['driver_id']) &&
            !in_array($order['status'], ['delivered', 'cancelled'], true)
        ) {
            $liveOffer = $this->delivery->qb()->table('gd_order_offers')
                ->where('order_id',  '=', (int)$order['id'])
                ->where('status',    '=', 'pending')
                ->where('expires_at','>', date('Y-m-d H:i:s'))
                ->first();
            if (!$liveOffer) {
                $this->delivery->dispatchOffer((int)$order['id']);
            }
        }

        $resp = [
            'ok'              => true,
            'status'          => $order['status'],
            'vendor_status'   => $order['vendor_status'],
            'has_driver'      => !empty($order['driver_id']),
            'prep_ends_at_ms' => !empty($order['prep_ends_at'])
                                    ? (int)(strtotime($order['prep_ends_at']) * 1000)
                                    : null,
            'pickup_lat'    => isset($order['pickup_lat'])  ? (float)$order['pickup_lat']  : null,
            'pickup_lng'    => isset($order['pickup_lng'])  ? (float)$order['pickup_lng']  : null,
            'delivery_lat'  => isset($order['delivery_lat'])? (float)$order['delivery_lat']: null,
            'delivery_lng'  => isset($order['delivery_lng'])? (float)$order['delivery_lng']: null,
            'courier'       => null,
        ];

        // Courier location — expose for all active orders (order_status stays 'pending'
        // through the entire vendor-preparation phase, so 'pending' must not be excluded)
        if (!empty($order['driver_id']) && !in_array($order['status'], ['delivered','cancelled'], true)) {
            $driver = $this->delivery->qb()->table('gd_drivers')->where('id','=',(int)$order['driver_id'])->first();
            if ($driver && !empty($driver['current_lat'])) {
                $resp['courier'] = [
                    'lat'  => (float)$driver['current_lat'],
                    'lng'  => (float)$driver['current_lng'],
                    'name' => $driver['name'] ?? '',
                ];
            }
        }

        return Response::json($resp);
    }

    // ── Vendor Admin Portal ───────────────────────────────────────────────────

    public function vendorAdmin(Request $r): Response
    {
        $vendor = $this->delivery->getVendorByToken((string)$r->getAttribute('token'));
        if (!$vendor) return Response::html('<!DOCTYPE html><html><body style="font-family:sans-serif;padding:40px"><h2>⛔ Access denied</h2></body></html>', 403);
        $branches = $this->delivery->vendorBranches((int)$vendor['id']);
        $sym      = $this->delivery->setting('currency_symbol','₾');
        $token    = (string)$r->getAttribute('token');
        return $this->viewStandalone('vendor_admin', compact('vendor','branches','sym','token') + ['base'=>$r->basePath()]);
    }

    public function apiPortalAdminUpdate(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $b   = $r->json();
        $upd = [];
        $fields = ['name','description','phone','email','cuisine_tags','menu_size','status','open_time','close_time'];
        foreach ($fields as $f) {
            if (array_key_exists($f, $b)) $upd[$f] = trim((string)$b[$f]);
        }
        if (isset($b['days_open'])) $upd['days_open'] = trim((string)$b['days_open']);
        if (isset($b['prep_time_min'])) $upd['prep_time_min'] = (int)$b['prep_time_min'];
        if (isset($b['min_order']))   $upd['min_order']   = (float)$b['min_order'];
        if (isset($b['delivery_fee'])) $upd['delivery_fee'] = (float)$b['delivery_fee'];
        if (array_key_exists('cover_image', $b)) $upd['cover_image'] = trim((string)$b['cover_image']);
        if (array_key_exists('logo', $b))        $upd['logo']        = trim((string)$b['logo']);
        if ($upd) {
            $this->delivery->qb()->table('gd_vendors')->where('id','=',(int)$vendor['id'])->update($upd);
        }
        return Response::json(['ok'=>true]);
    }

    public function apiPortalUpload(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);

        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            return Response::json(['ok'=>false,'error'=>'No file or upload error']);
        }

        $mime = mime_content_type($file['tmp_name']);
        $allowed = ['image/jpeg','image/png','image/gif','image/webp'];
        if (!in_array($mime, $allowed, true)) {
            return Response::json(['ok'=>false,'error'=>'Allowed: jpg, png, gif, webp']);
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            return Response::json(['ok'=>false,'error'=>'Max file size: 5 MB']);
        }

        $ext      = match ($mime) {
            'image/jpeg' => 'jpg', 'image/png' => 'png',
            'image/gif'  => 'gif', 'image/webp'=> 'webp', default => 'jpg'
        };
        $type     = in_array($_POST['type'] ?? '', ['cover','logo']) ? ($_POST['type']) : 'cover';
        $root     = dirname(__DIR__, 3);
        $dir      = $root . '/public/uploads/delivery/vendors/' . (int)$vendor['id'];
        if (!is_dir($dir)) mkdir($dir, 0755, true);

        $filename = $type . '_' . time() . '.' . $ext;
        $dest     = $dir . '/' . $filename;
        if (!move_uploaded_file($file['tmp_name'], $dest)) {
            return Response::json(['ok'=>false,'error'=>'Failed to save file']);
        }

        $url  = $r->basePath() . '/uploads/delivery/vendors/' . (int)$vendor['id'] . '/' . $filename;
        $col  = $type === 'logo' ? 'logo' : 'cover_image';
        $this->delivery->qb()->table('gd_vendors')->where('id','=',(int)$vendor['id'])->update([$col => $url]);

        return Response::json(['ok'=>true,'url'=>$url]);
    }

    // ── Vendor Portal: Branch API ─────────────────────────────────────────────

    public function apiPortalBranchesList(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        return Response::json(['ok'=>true,'branches'=>$this->delivery->vendorBranches((int)$vendor['id'])]);
    }

    public function apiPortalBranchCreate(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $b    = $r->json();
        $name = trim((string)($b['name'] ?? ''));
        if (!$name) return Response::json(['ok'=>false,'error'=>'Name required']);
        $id = $this->delivery->createBranch([
            'vendor_id'  => (int)$vendor['id'],
            'name'       => $name,
            'address'    => trim((string)($b['address'] ?? '')),
            'lat'        => isset($b['lat']) && (float)$b['lat'] ? (float)$b['lat'] : null,
            'lng'        => isset($b['lng']) && (float)$b['lng'] ? (float)$b['lng'] : null,
            'phone'      => trim((string)($b['phone'] ?? '')),
            'active'     => 1,
            'sort_order' => (int)($b['sort_order'] ?? 0),
        ]);
        return Response::json(['ok'=>true,'id'=>$id]);
    }

    public function apiPortalBranchUpdate(Request $r): Response
    {
        $vendor   = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $branchId = (int)$r->getAttribute('id');
        $branch   = $this->delivery->getBranch($branchId);
        if (!$branch || (int)$branch['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        $b   = $r->json();
        $upd = [];
        if (isset($b['name']))       $upd['name']       = trim((string)$b['name']);
        if (isset($b['address']))    $upd['address']    = trim((string)$b['address']);
        if (array_key_exists('lat',$b)) $upd['lat']     = (float)$b['lat'] ?: null;
        if (array_key_exists('lng',$b)) $upd['lng']     = (float)$b['lng'] ?: null;
        if (isset($b['phone']))      $upd['phone']      = trim((string)$b['phone']);
        if (isset($b['active']))     $upd['active']     = (int)(bool)$b['active'];
        if (isset($b['sort_order'])) $upd['sort_order'] = (int)$b['sort_order'];
        if ($upd) $this->delivery->updateBranch($branchId, $upd);
        return Response::json(['ok'=>true]);
    }

    public function apiPortalBranchDelete(Request $r): Response
    {
        $vendor   = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $branchId = (int)$r->getAttribute('id');
        $branch   = $this->delivery->getBranch($branchId);
        if (!$branch || (int)$branch['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        $this->delivery->deleteBranch($branchId);
        return Response::json(['ok'=>true]);
    }

    // ── Vendor Portal: Catalog API ────────────────────────────────────────────

    private function portalVendor(Request $r): ?array
    {
        return $this->delivery->getVendorByToken((string)$r->getAttribute('token'));
    }

    public function apiPortalCatalog(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $cats   = $this->delivery->vendorCategories((int)$vendor['id']);
        $prods  = $this->delivery->vendorProducts((int)$vendor['id']);
        $combos = $this->delivery->vendorCombos((int)$vendor['id']);
        return Response::json(['ok'=>true,'categories'=>$cats,'products'=>$prods,'combos'=>$combos]);
    }

    public function apiPortalCategoryCreate(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $b    = $r->json();
        $name = trim((string)($b['name'] ?? ''));
        if (!$name) return Response::json(['ok'=>false,'error'=>'Name required']);
        $id = $this->delivery->createCategory([
            'vendor_id'  => (int)$vendor['id'],
            'name'       => $name,
            'sort_order' => (int)($b['sort_order'] ?? 0),
            'active'     => 1,
        ]);
        return Response::json(['ok'=>true,'id'=>$id]);
    }

    public function apiPortalCategoryUpdate(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $catId = (int)$r->getAttribute('id');
        $cat   = $this->delivery->getCategory($catId);
        if (!$cat || (int)$cat['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        $b = $r->json();
        $upd = array_filter([
            'name'       => isset($b['name'])       ? trim((string)$b['name']) : null,
            'sort_order' => isset($b['sort_order'])  ? (int)$b['sort_order']   : null,
            'active'     => isset($b['active'])      ? (int)(bool)$b['active'] : null,
        ], fn($v) => $v !== null);
        if ($upd) $this->delivery->updateCategory($catId, $upd);
        return Response::json(['ok'=>true]);
    }

    public function apiPortalCategoryDelete(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $catId = (int)$r->getAttribute('id');
        $cat   = $this->delivery->getCategory($catId);
        if (!$cat || (int)$cat['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        // Move products to uncategorized
        $this->delivery->qb()->table('gd_products')
            ->where('category_id','=',$catId)
            ->update(['category_id'=>null]);
        $this->delivery->deleteCategory($catId);
        return Response::json(['ok'=>true]);
    }

    // ── Vendor Portal: Product API ────────────────────────────────────────────

    public function apiPortalProductCreate(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $b    = $r->json();
        $name = trim((string)($b['name'] ?? ''));
        if (!$name) return Response::json(['ok'=>false,'error'=>'Name required']);
        $catId = isset($b['category_id']) && (int)$b['category_id'] ? (int)$b['category_id'] : null;
        $id = $this->delivery->createProduct([
            'vendor_id'   => (int)$vendor['id'],
            'category_id' => $catId,
            'name'        => $name,
            'description' => trim((string)($b['description'] ?? '')),
            'price'       => (float)($b['price'] ?? 0),
            'image'       => trim((string)($b['image'] ?? '')),
            'active'      => isset($b['active']) ? (int)(bool)$b['active'] : 1,
            'in_stock'    => isset($b['in_stock']) ? (int)(bool)$b['in_stock'] : 1,
            'sort_order'  => (int)($b['sort_order'] ?? 0),
        ]);
        return Response::json(['ok'=>true,'id'=>$id]);
    }

    public function apiPortalProductUpdate(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $prodId = (int)$r->getAttribute('id');
        $prod   = $this->delivery->getProduct($prodId);
        if (!$prod || (int)$prod['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        $b   = $r->json();
        $upd = [];
        if (isset($b['name']))        $upd['name']        = trim((string)$b['name']);
        if (isset($b['description'])) $upd['description'] = trim((string)$b['description']);
        if (isset($b['price']))       $upd['price']       = (float)$b['price'];
        if (isset($b['category_id'])) $upd['category_id'] = (int)$b['category_id'] ?: null;
        if (isset($b['image']))       $upd['image']       = trim((string)$b['image']);
        if (isset($b['sort_order']))  $upd['sort_order']  = (int)$b['sort_order'];
        if (isset($b['active']))      $upd['active']      = (int)(bool)$b['active'];
        if (isset($b['in_stock']))    $upd['in_stock']    = (int)(bool)$b['in_stock'];
        if ($upd) $this->delivery->updateProduct($prodId, $upd);
        return Response::json(['ok'=>true]);
    }

    public function apiPortalProductDelete(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $prodId = (int)$r->getAttribute('id');
        $prod   = $this->delivery->getProduct($prodId);
        if (!$prod || (int)$prod['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        $this->delivery->deleteProduct($prodId);
        return Response::json(['ok'=>true]);
    }

    public function apiPortalProductStock(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $prodId = (int)$r->getAttribute('id');
        $prod   = $this->delivery->getProduct($prodId);
        if (!$prod || (int)$prod['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        $b       = $r->json();
        $inStock = isset($b['in_stock']) ? (int)(bool)$b['in_stock'] : ((int)$prod['in_stock'] ? 0 : 1);
        $this->delivery->updateProduct($prodId, ['in_stock' => $inStock]);
        return Response::json(['ok'=>true,'in_stock'=>(bool)$inStock]);
    }

    // ── Vendor Portal: Offers API ─────────────────────────────────────────────

    public function apiPortalOffersList(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        return Response::json(['ok'=>true,'offers'=>$this->delivery->vendorOffers((int)$vendor['id'])]);
    }

    public function apiPortalOfferCreate(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $b    = $r->json();
        $name = trim((string)($b['name'] ?? ''));
        if (!$name) return Response::json(['ok'=>false,'error'=>'Name required']);
        $id = $this->delivery->createOffer([
            'vendor_id'  => (int)$vendor['id'],
            'name'       => $name,
            'type'       => in_array($b['type']??'',['percent','fixed','free_delivery']) ? $b['type'] : 'percent',
            'value'      => (float)($b['value'] ?? 0),
            'applies_to' => in_array($b['applies_to']??'',['order','product','category']) ? $b['applies_to'] : 'order',
            'min_order'  => (float)($b['min_order'] ?? 0),
            'start_date' => $b['start_date'] ?? null,
            'end_date'   => $b['end_date'] ?? null,
            'active'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return Response::json(['ok'=>true,'id'=>$id]);
    }

    public function apiPortalOfferUpdate(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $offerId = (int)$r->getAttribute('id');
        $offer   = $this->delivery->getOffer($offerId);
        if (!$offer || (int)$offer['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        $b   = $r->json();
        $upd = [];
        if (isset($b['name']))       $upd['name']       = trim((string)$b['name']);
        if (isset($b['type']))       $upd['type']       = in_array($b['type'],['percent','fixed','free_delivery']) ? $b['type'] : $offer['type'];
        if (isset($b['value']))      $upd['value']      = (float)$b['value'];
        if (isset($b['applies_to'])) $upd['applies_to'] = in_array($b['applies_to'],['order','product','category']) ? $b['applies_to'] : $offer['applies_to'];
        if (isset($b['min_order']))  $upd['min_order']  = (float)$b['min_order'];
        if (array_key_exists('start_date',$b)) $upd['start_date'] = $b['start_date'] ?: null;
        if (array_key_exists('end_date',$b))   $upd['end_date']   = $b['end_date']   ?: null;
        if (isset($b['active']))     $upd['active']     = (int)(bool)$b['active'];
        if ($upd) $this->delivery->updateOffer($offerId, $upd);
        return Response::json(['ok'=>true]);
    }

    public function apiPortalOfferDelete(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $offerId = (int)$r->getAttribute('id');
        $offer   = $this->delivery->getOffer($offerId);
        if (!$offer || (int)$offer['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        $this->delivery->deleteOffer($offerId);
        return Response::json(['ok'=>true]);
    }

    // ── Vendor Portal: Combos API ─────────────────────────────────────────────

    public function apiPortalCombosList(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        return Response::json(['ok'=>true,'combos'=>$this->delivery->vendorCombos((int)$vendor['id'])]);
    }

    public function apiPortalComboCreate(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $b    = $r->json();
        $name = trim((string)($b['name'] ?? ''));
        if (!$name) return Response::json(['ok'=>false,'error'=>'Name required']);
        $type = in_array($b['type'] ?? '', ['choice','included','size']) ? (string)$b['type'] : 'choice';
        try {
            $id = $this->delivery->createCombo([
                'vendor_id'  => (int)$vendor['id'],
                'name'       => $name,
                'type'       => $type,
                'required'   => isset($b['required'])   ? (int)(bool)$b['required']    : 0,
                'max_select' => isset($b['max_select'])  ? max(1,(int)$b['max_select']) : 1,
                'sort_order' => (int)($b['sort_order'] ?? 0),
                'active'     => 1,
            ]);
        } catch (\Throwable $e) {
            return Response::json(['ok'=>false,'error'=>'DB error: '.$e->getMessage()]);
        }
        // Optionally set initial product items
        $products = $b['products'] ?? ($b['product_ids'] ?? []);
        if (!empty($products) && is_array($products)) {
            try { $this->delivery->setComboProducts($id, $products); } catch (\Throwable) {}
        }
        return Response::json(['ok'=>true,'id'=>$id]);
    }

    public function apiPortalComboUpdate(Request $r): Response
    {
        $vendor  = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $comboId = (int)$r->getAttribute('id');
        $combo   = $this->delivery->getCombo($comboId);
        if (!$combo || (int)$combo['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        $b   = $r->json();
        $upd = [];
        if (isset($b['name']))       $upd['name']       = trim((string)$b['name']);
        if (isset($b['type']))       $upd['type']       = in_array($b['type'],['choice','included','size']) ? $b['type'] : ($combo['type'] ?? 'choice');
        if (isset($b['required']))   $upd['required']   = (int)(bool)$b['required'];
        if (isset($b['max_select'])) $upd['max_select'] = max(1,(int)$b['max_select']);
        if (isset($b['sort_order'])) $upd['sort_order'] = (int)$b['sort_order'];
        if (isset($b['active']))     $upd['active']     = (int)(bool)$b['active'];
        if ($upd) $this->delivery->updateCombo($comboId, $upd);
        return Response::json(['ok'=>true]);
    }

    public function apiPortalComboDelete(Request $r): Response
    {
        $vendor  = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $comboId = (int)$r->getAttribute('id');
        $combo   = $this->delivery->getCombo($comboId);
        if (!$combo || (int)$combo['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        $this->delivery->deleteCombo($comboId);
        return Response::json(['ok'=>true]);
    }

    public function apiPortalComboSetItems(Request $r): Response
    {
        $vendor  = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $comboId = (int)$r->getAttribute('id');
        $combo   = $this->delivery->getCombo($comboId);
        if (!$combo || (int)$combo['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        $b        = $r->json();
        // Accept both old format (product_ids array of ints) and new format (products array of {product_id, price_modifier})
        $products = $b['products'] ?? ($b['product_ids'] ?? []);
        if (!is_array($products)) $products = [];
        try {
            $this->delivery->setComboProducts($comboId, $products);
        } catch (\Throwable $e) {
            return Response::json(['ok'=>false,'error'=>'DB error: '.$e->getMessage()]);
        }
        return Response::json(['ok'=>true,'products'=>$this->delivery->comboProducts($comboId)]);
    }

    public function apiPortalProductCombos(Request $r): Response
    {
        $vendor  = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $prodId  = (int)$r->getAttribute('id');
        $product = $this->delivery->getProduct($prodId);
        if (!$product || (int)$product['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        return Response::json(['ok'=>true,'combos'=>$this->delivery->productCombos($prodId)]);
    }

    public function apiPortalProductSyncCombos(Request $r): Response
    {
        $vendor  = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $prodId  = (int)$r->getAttribute('id');
        $product = $this->delivery->getProduct($prodId);
        if (!$product || (int)$product['vendor_id'] !== (int)$vendor['id']) return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        $b        = $r->json();
        $comboIds = is_array($b['combo_ids'] ?? null) ? $b['combo_ids'] : [];
        $this->delivery->syncProductCombos($prodId, $comboIds);
        return Response::json(['ok'=>true]);
    }

    // ── Vendor Portal: Product Modifier Groups API ───────────────────────────

    public function apiPortalProductModifiers(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $productId = (int)$r->getAttribute('id');
        $prod = $this->delivery->getProduct($productId);
        if (!$prod || (int)$prod['vendor_id'] !== (int)$vendor['id'])
            return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        return Response::json(['ok'=>true, 'groups'=>$this->delivery->getProductModifiers($productId)]);
    }

    public function apiPortalProductModifiersSave(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $productId = (int)$r->getAttribute('id');
        $prod = $this->delivery->getProduct($productId);
        if (!$prod || (int)$prod['vendor_id'] !== (int)$vendor['id'])
            return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        $b = $r->json();
        try {
            $this->delivery->saveProductModifiers($productId, (array)($b['groups'] ?? []));
            return Response::json(['ok'=>true]);
        } catch (\Throwable $e) {
            return Response::json(['ok'=>false,'error'=>$e->getMessage()]);
        }
    }

    // ── Vendor Portal: Modifier Templates API ────────────────────────────────

    public function apiPortalModifierTemplateList(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        return Response::json(['ok'=>true, 'templates'=>$this->delivery->getVendorModifierTemplates((int)$vendor['id'])]);
    }

    public function apiPortalModifierTemplateCreate(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $b = $r->json();
        if (!trim((string)($b['name'] ?? ''))) return Response::json(['ok'=>false,'error'=>'სახელი სავალდებულოა']);
        try {
            $id = $this->delivery->saveModifierTemplate((int)$vendor['id'], $b);
            return Response::json(['ok'=>true,'id'=>$id]);
        } catch (\Throwable $e) {
            return Response::json(['ok'=>false,'error'=>$e->getMessage()]);
        }
    }

    public function apiPortalModifierTemplateUpdate(Request $r): Response
    {
        $vendor     = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $templateId = (int)$r->getAttribute('id');
        $b          = $r->json();
        try {
            $this->delivery->saveModifierTemplate((int)$vendor['id'], $b, $templateId);
            return Response::json(['ok'=>true]);
        } catch (\Throwable $e) {
            return Response::json(['ok'=>false,'error'=>$e->getMessage()]);
        }
    }

    public function apiPortalModifierTemplateDelete(Request $r): Response
    {
        $vendor     = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $templateId = (int)$r->getAttribute('id');
        try {
            $this->delivery->deleteModifierTemplate((int)$vendor['id'], $templateId);
            return Response::json(['ok'=>true]);
        } catch (\Throwable $e) {
            return Response::json(['ok'=>false,'error'=>$e->getMessage()]);
        }
    }

    public function apiPortalModifierTemplateApplyAll(Request $r): Response
    {
        $vendor     = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $templateId = (int)$r->getAttribute('id');
        try {
            $applied = $this->delivery->applyTemplateToAllProducts((int)$vendor['id'], $templateId);
            return Response::json(['ok'=>true,'applied'=>$applied]);
        } catch (\Throwable $e) {
            return Response::json(['ok'=>false,'error'=>$e->getMessage()]);
        }
    }

    // ── Vendor Portal: Combo Meals API ───────────────────────────────────────

    public function apiPortalComboMealList(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        return Response::json(['ok'=>true,'meals'=>$this->delivery->vendorComboMeals((int)$vendor['id'])]);
    }

    public function apiPortalComboMealCreate(Request $r): Response
    {
        $vendor = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $b    = $r->json();
        $name = trim((string)($b['name'] ?? ''));
        if (!$name) return Response::json(['ok'=>false,'error'=>'Name required']);
        try {
            $id = $this->delivery->saveComboMeal((int)$vendor['id'], $b);
            return Response::json(['ok'=>true,'id'=>$id]);
        } catch (\Throwable $e) {
            return Response::json(['ok'=>false,'error'=>$e->getMessage()]);
        }
    }

    public function apiPortalComboMealUpdate(Request $r): Response
    {
        $vendor    = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $productId = (int)$r->getAttribute('id');
        $prod      = $this->delivery->getProduct($productId);
        if (!$prod || (int)$prod['vendor_id'] !== (int)$vendor['id'])
            return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        $b = $r->json();
        try {
            $this->delivery->saveComboMeal((int)$vendor['id'], $b, $productId);
            return Response::json(['ok'=>true]);
        } catch (\Throwable $e) {
            return Response::json(['ok'=>false,'error'=>$e->getMessage()]);
        }
    }

    public function apiPortalComboMealDelete(Request $r): Response
    {
        $vendor    = $this->portalVendor($r);
        if (!$vendor) return Response::json(['ok'=>false,'error'=>'Unauthorized'], 403);
        $productId = (int)$r->getAttribute('id');
        $prod      = $this->delivery->getProduct($productId);
        if (!$prod || (int)$prod['vendor_id'] !== (int)$vendor['id'])
            return Response::json(['ok'=>false,'error'=>'Not found'], 404);
        try {
            $this->delivery->deleteComboMeal((int)$vendor['id'], $productId);
            return Response::json(['ok'=>true]);
        } catch (\Throwable $e) {
            return Response::json(['ok'=>false,'error'=>$e->getMessage()]);
        }
    }

    // ── Theme-aware renderer ──────────────────────────────────────────────────

    private function viewStandalone(string $tpl, array $data = []): Response
    {
        $file = $this->viewsDir . '/' . $tpl . '.php';
        if (!is_file($file)) return Response::error("View not found: $tpl", 500);
        $themeViews = dirname(__DIR__,3).'/themes/default/views';
        require_once $themeViews.'/helpers.php';
        $delivery = $this->delivery;
        extract($data, EXTR_SKIP);
        ob_start();
        try { include $file; return Response::html((string)ob_get_clean()); }
        catch (\Throwable $e) { ob_end_clean(); throw $e; }
    }

    public function qb(): \GoniCore\Core\Database\QueryBuilder { return $this->delivery->qb(); }

    // ── Theme-aware renderer ──────────────────────────────────────────────────

    private function view(Request $r, string $tpl, array $data = [], string $pageTitle = ''): Response
    {
        $file = $this->viewsDir . '/' . $tpl . '.php';
        if (!is_file($file)) return Response::error("Delivery view not found: $tpl", 500);
        $themeViews = dirname(__DIR__, 3) . '/themes/default/views';
        require_once $themeViews . '/helpers.php';
        $base     = $r->basePath();
        $delivery = $this->delivery;
        try {
            $c             = gc_container();
            $siteName      = $c->get(\GoniCore\Modules\Settings\SettingsService::class)->siteName() ?: 'GoniCore';
            $langService   = $c->get(\GoniCore\Modules\Language\LanguageService::class);
            $langService->boot($r);
            $menuService   = $c->get(\GoniCore\Modules\Menu\MenuService::class);
            $widgetService = $c->get(\GoniCore\Modules\Widget\WidgetService::class);
            $categories    = $c->get(\GoniCore\Modules\Category\CategoryRepository::class)->findAll();
        } catch (\Throwable) {
            $siteName = 'GoniCore'; $langService = null; $menuService = null;
            $widgetService = null; $categories = [];
        }
        extract($data, EXTR_SKIP);
        ob_start();
        try { include $file; $content = (string) ob_get_clean(); }
        catch (\Throwable $e) { ob_end_clean(); throw $e; }
        ob_start();
        try { include $themeViews . '/layout.php'; return Response::html((string) ob_get_clean()); }
        catch (\Throwable $e) { ob_end_clean(); throw $e; }
    }
}
