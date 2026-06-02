<?php
declare(strict_types=1);

namespace GoniStore;

use GoniCore\Core\Database\QueryBuilder;

/**
 * Core data-access layer for GoniStore.
 */
final class StoreService
{
    public function __construct(private readonly QueryBuilder $qb) {}

    // ── Settings ──────────────────────────────────────────────────────────────

    public function setting(string $key, string $default = ''): string
    {
        $row = $this->qb->table('gs_settings')->where('key', '=', $key)->first();
        return $row ? (string)$row['value'] : $default;
    }

    public function settings(): array
    {
        $rows = $this->qb->table('gs_settings')->get() ?: [];
        $out  = [];
        foreach ($rows as $r) { $out[$r['key']] = $r['value']; }
        return $out;
    }

    public function setSetting(string $key, string $value): void
    {
        $exists = $this->qb->table('gs_settings')->where('key', '=', $key)->first();
        if ($exists) {
            $this->qb->table('gs_settings')->where('key', '=', $key)->update(['value' => $value]);
        } else {
            $this->qb->table('gs_settings')->insert(['key' => $key, 'value' => $value]);
        }
    }

    public function bulkSettings(array $data): void
    {
        foreach ($data as $k => $v) {
            $this->setSetting((string)$k, (string)$v);
        }
    }

    // ── Price formatting ──────────────────────────────────────────────────────

    public function formatPrice(float $amount): string
    {
        $symbol   = $this->setting('currency_symbol', '$');
        $position = $this->setting('currency_position', 'before');
        $decimals = (int) $this->setting('decimals', '2');
        $tSep     = $this->setting('thousand_sep', ',');
        $dSep     = $this->setting('decimal_sep', '.');
        $formatted = number_format($amount, $decimals, $dSep, $tSep);
        return $position === 'before' ? $symbol . $formatted : $formatted . $symbol;
    }

    // ── Categories ────────────────────────────────────────────────────────────

    public function allCategories(): array
    {
        return $this->qb->table('gs_categories')->orderBy('sort_order','ASC')->orderBy('name','ASC')->get() ?: [];
    }

    public function getCategory(int $id): ?array
    {
        return $this->qb->table('gs_categories')->where('id','=',$id)->first() ?: null;
    }

    public function getCategoryBySlug(string $slug): ?array
    {
        return $this->qb->table('gs_categories')->where('slug','=',$slug)->first() ?: null;
    }

    public function createCategory(array $data): int
    {
        return (int) $this->qb->table('gs_categories')->insert($data);
    }

    public function updateCategory(int $id, array $data): void
    {
        $this->qb->table('gs_categories')->where('id','=',$id)->update($data);
    }

    public function deleteCategory(int $id): void
    {
        $this->qb->table('gs_products')->where('category_id','=',$id)->update(['category_id' => null]);
        $this->qb->table('gs_categories')->where('id','=',$id)->delete();
    }

    // ── Products ──────────────────────────────────────────────────────────────

    public function allProducts(array $filters = [], int $page = 1, int $perPage = 20): array
    {
        $qb = $this->qb->table('gs_products')->orderBy('created_at','DESC');
        if (!empty($filters['status']))      $qb->where('status','=',$filters['status']);
        if (!empty($filters['category_id'])) $qb->where('category_id','=',(int)$filters['category_id']);
        if (!empty($filters['search'])) {
            // basic title search - would need raw SQL for LIKE, use simple approach
        }
        $total   = $qb->count();
        $products = $qb->limit($perPage)->offset(($page-1)*$perPage)->get() ?: [];
        return ['products' => $products, 'total' => $total, 'pages' => max(1,(int)ceil($total/$perPage))];
    }

    public function getProduct(int $id): ?array
    {
        $p = $this->qb->table('gs_products')->where('id','=',$id)->first();
        if (!$p) return null;
        $p['images']     = json_decode((string)$p['images'], true) ?: [];
        $p['gallery']    = json_decode((string)$p['gallery'], true) ?: [];
        $p['attributes'] = json_decode((string)$p['attributes'], true) ?: [];
        return $p;
    }

    public function getProductBySlug(string $slug): ?array
    {
        $p = $this->qb->table('gs_products')->where('slug','=',$slug)->where('status','=','published')->first();
        if (!$p) return null;
        $p['images']     = json_decode((string)$p['images'], true) ?: [];
        $p['gallery']    = json_decode((string)$p['gallery'], true) ?: [];
        $p['attributes'] = json_decode((string)$p['attributes'], true) ?: [];
        return $p;
    }

    public function createProduct(array $data): int
    {
        $data['images']     = json_encode($data['images']     ?? []);
        $data['gallery']    = json_encode($data['gallery']    ?? []);
        $data['attributes'] = json_encode($data['attributes'] ?? []);
        return (int) $this->qb->table('gs_products')->insert($data);
    }

    public function updateProduct(int $id, array $data): void
    {
        if (isset($data['images']))     $data['images']     = json_encode($data['images']);
        if (isset($data['gallery']))    $data['gallery']    = json_encode($data['gallery']);
        if (isset($data['attributes'])) $data['attributes'] = json_encode($data['attributes']);
        $this->qb->table('gs_products')->where('id','=',$id)->update($data);
    }

    public function deleteProduct(int $id): void
    {
        $this->qb->table('gs_products')->where('id','=',$id)->delete();
    }

    public function featuredProducts(int $limit = 8): array
    {
        return $this->qb->table('gs_products')
            ->where('status','=','published')
            ->where('featured','=',1)
            ->orderBy('sort_order','ASC')
            ->limit($limit)->get() ?: [];
    }

    public function productsByCategory(string $slug, int $limit = 20, int $page = 1): array
    {
        $cat = $this->getCategoryBySlug($slug);
        if (!$cat) return [];
        return $this->qb->table('gs_products')
            ->where('status','=','published')
            ->where('category_id','=',(int)$cat['id'])
            ->orderBy('sort_order','ASC')
            ->limit($limit)->offset(($page-1)*$limit)->get() ?: [];
    }

    public function latestProducts(int $limit = 12): array
    {
        return $this->qb->table('gs_products')
            ->where('status','=','published')
            ->orderBy('created_at','DESC')
            ->limit($limit)->get() ?: [];
    }

    // ── Variations ────────────────────────────────────────────────────────────

    public function getVariations(int $productId): array
    {
        $vars = $this->qb->table('gs_variations')->where('product_id','=',$productId)->get() ?: [];
        foreach ($vars as &$v) {
            $v['attributes'] = json_decode((string)$v['attributes'], true) ?: [];
        }
        return $vars;
    }

    public function saveVariation(array $data): int
    {
        $data['attributes'] = json_encode($data['attributes'] ?? []);
        if (!empty($data['id'])) {
            $id = (int)$data['id'];
            unset($data['id']);
            $this->qb->table('gs_variations')->where('id','=',$id)->update($data);
            return $id;
        }
        return (int) $this->qb->table('gs_variations')->insert($data);
    }

    public function deleteVariation(int $id): void
    {
        $this->qb->table('gs_variations')->where('id','=',$id)->delete();
    }

    // ── Stock ─────────────────────────────────────────────────────────────────

    public function decrementStock(int $productId, int $qty = 1): void
    {
        $p = $this->qb->table('gs_products')->where('id','=',$productId)->first();
        if ($p && $p['manage_stock'] && $p['stock'] !== null) {
            $this->qb->table('gs_products')->where('id','=',$productId)
                ->update(['stock' => max(0, (int)$p['stock'] - $qty)]);
        }
    }

    public function isInStock(int $productId, int $qty = 1): bool
    {
        $p = $this->qb->table('gs_products')->where('id','=',$productId)->first();
        if (!$p) return false;
        if (!$p['manage_stock']) return true;
        return (int)$p['stock'] >= $qty;
    }

    // ── Cart (session-based) ──────────────────────────────────────────────────

    public function getCart(): array
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        return $_SESSION['gs_cart'] ?? [];
    }

    public function addToCart(int $productId, int $qty = 1, ?int $variationId = null, array $attrs = []): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $cart = $_SESSION['gs_cart'] ?? [];
        $key  = $productId . ($variationId ? '_' . $variationId : '');
        if (isset($cart[$key])) {
            $cart[$key]['qty'] += $qty;
        } else {
            $p = $this->getProduct($productId);
            if (!$p) return;
            $price = $p['sale_price'] ?? $p['price'];
            if ($variationId) {
                $v = $this->qb->table('gs_variations')->where('id','=',$variationId)->first();
                if ($v) { $price = $v['sale_price'] ?? $v['price']; $attrs = json_decode((string)$v['attributes'],true) ?: $attrs; }
            }
            $cart[$key] = [
                'product_id'   => $productId,
                'variation_id' => $variationId,
                'name'         => $p['name'],
                'sku'          => $p['sku'],
                'price'        => (float)$price,
                'image'        => ($p['images'][0] ?? ''),
                'qty'          => $qty,
                'attrs'        => $attrs,
            ];
        }
        $_SESSION['gs_cart'] = $cart;
    }

    public function updateCartItem(string $key, int $qty): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if ($qty <= 0) { unset($_SESSION['gs_cart'][$key]); return; }
        if (isset($_SESSION['gs_cart'][$key])) {
            $_SESSION['gs_cart'][$key]['qty'] = $qty;
        }
    }

    public function removeCartItem(string $key): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        unset($_SESSION['gs_cart'][$key]);
    }

    public function clearCart(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $_SESSION['gs_cart'] = [];
    }

    public function cartTotals(): array
    {
        $cart      = $this->getCart();
        $subtotal  = 0.0;
        foreach ($cart as $item) { $subtotal += $item['price'] * $item['qty']; }
        $taxRate   = (float)$this->setting('tax_rate', '0') / 100;
        $tax       = round($subtotal * $taxRate, 2);
        $freeMin   = (float)$this->setting('free_shipping_min', '0');
        $shipCost  = (float)$this->setting('shipping_cost', '0');
        $shipping  = ($freeMin > 0 && $subtotal >= $freeMin) ? 0.0 : $shipCost;
        return [
            'subtotal' => $subtotal,
            'tax'      => $tax,
            'shipping' => $shipping,
            'total'    => $subtotal + $tax + $shipping,
            'count'    => array_sum(array_column($cart, 'qty')),
        ];
    }

    public function applyCoupon(string $code): array
    {
        $coupon = $this->qb->table('gs_coupons')
            ->where('code','=',$code)
            ->where('active','=',1)
            ->first();
        if (!$coupon) return ['ok'=>false,'error'=>'Coupon not found.'];
        if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
            return ['ok'=>false,'error'=>'Coupon has expired.'];
        }
        if ($coupon['max_uses'] && (int)$coupon['used'] >= (int)$coupon['max_uses']) {
            return ['ok'=>false,'error'=>'Coupon usage limit reached.'];
        }
        $totals = $this->cartTotals();
        if ($totals['subtotal'] < (float)$coupon['min_order']) {
            return ['ok'=>false,'error'=>'Minimum order not met.'];
        }
        $discount = $coupon['type'] === 'percent'
            ? round($totals['subtotal'] * (float)$coupon['value'] / 100, 2)
            : (float)$coupon['value'];
        return ['ok'=>true,'coupon'=>$coupon,'discount'=>$discount];
    }

    // ── Orders ────────────────────────────────────────────────────────────────

    public function createOrder(array $orderData, array $items): int
    {
        $orderData['order_number'] = 'ORD-' . strtoupper(substr(uniqid(), -6));
        $orderId = (int) $this->qb->table('gs_orders')->insert($orderData);
        foreach ($items as $item) {
            $item['order_id'] = $orderId;
            $item['attributes'] = json_encode($item['attributes'] ?? []);
            $item['meta']       = json_encode($item['meta'] ?? []);
            $this->qb->table('gs_order_items')->insert($item);
            if (!empty($item['product_id'])) {
                $this->decrementStock((int)$item['product_id'], (int)$item['quantity']);
            }
        }
        // Increment coupon usage
        if (!empty($orderData['coupon_code'])) {
            $this->qb->table('gs_coupons')
                ->where('code','=',$orderData['coupon_code'])
                ->update(['used' => $this->qb->table('gs_coupons')->where('code','=',$orderData['coupon_code'])->first()['used'] + 1]);
        }
        return $orderId;
    }

    public function allOrders(int $page = 1, int $perPage = 20, string $status = ''): array
    {
        $qb = $this->qb->table('gs_orders')->orderBy('created_at','DESC');
        if ($status) $qb->where('status','=',$status);
        $total = $qb->count();
        return [
            'orders' => $qb->limit($perPage)->offset(($page-1)*$perPage)->get() ?: [],
            'total'  => $total,
            'pages'  => max(1,(int)ceil($total/$perPage)),
        ];
    }

    public function getOrder(int $id): ?array
    {
        $order = $this->qb->table('gs_orders')->where('id','=',$id)->first();
        if (!$order) return null;
        $order['items']   = $this->qb->table('gs_order_items')->where('order_id','=',$id)->get() ?: [];
        $order['notes']   = $this->qb->table('gs_order_notes')->where('order_id','=',$id)->orderBy('created_at','ASC')->get() ?: [];
        $order['billing'] = json_decode((string)$order['billing'], true) ?: [];
        $order['shipping']= json_decode((string)$order['shipping'], true) ?: [];
        foreach ($order['items'] as &$item) {
            $item['attributes'] = json_decode((string)$item['attributes'], true) ?: [];
        }
        return $order;
    }

    public function updateOrderStatus(int $id, string $status, string $note = ''): void
    {
        $this->qb->table('gs_orders')->where('id','=',$id)->update([
            'status'     => $status,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        if ($note) {
            $this->qb->table('gs_order_notes')->insert([
                'order_id' => $id,
                'note'     => $note,
                'status'   => $status,
            ]);
        }
    }

    public static function orderStatuses(): array
    {
        return [
            'pending'    => ['label'=>'Pending',    'color'=>'#f59e0b'],
            'processing' => ['label'=>'Processing', 'color'=>'#3b82f6'],
            'on-hold'    => ['label'=>'On Hold',    'color'=>'#8b5cf6'],
            'completed'  => ['label'=>'Completed',  'color'=>'#10b981'],
            'cancelled'  => ['label'=>'Cancelled',  'color'=>'#ef4444'],
            'refunded'   => ['label'=>'Refunded',   'color'=>'#64748b'],
            'failed'     => ['label'=>'Failed',     'color'=>'#dc2626'],
        ];
    }

    // ── Coupons ───────────────────────────────────────────────────────────────

    public function allCoupons(): array
    {
        return $this->qb->table('gs_coupons')->orderBy('created_at','DESC')->get() ?: [];
    }

    public function saveCoupon(array $data): int
    {
        if (!empty($data['id'])) {
            $id = (int)$data['id']; unset($data['id']);
            $this->qb->table('gs_coupons')->where('id','=',$id)->update($data);
            return $id;
        }
        return (int) $this->qb->table('gs_coupons')->insert($data);
    }

    public function deleteCoupon(int $id): void
    {
        $this->qb->table('gs_coupons')->where('id','=',$id)->delete();
    }

    // ── Slug helper ───────────────────────────────────────────────────────────

    public function uniqueSlug(string $text, string $table): string
    {
        $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $text) ?? $text, '-'));
        $slug = $base;
        $i    = 1;
        while ($this->qb->table($table)->where('slug','=',$slug)->first()) {
            $slug = $base . '-' . $i++;
        }
        return $slug;
    }
}
