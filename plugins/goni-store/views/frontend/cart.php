<?php
/**
 * GoniStore — Cart page
 * Variables: $cart, $totals, $couponMsg, $settings, $base
 */
$symbol   = $settings['currency_symbol'] ?? '$';
// Calculate total discount from original prices vs effective prices
$totalSaved = 0;
foreach ($cart ?? [] as $item) {
    if (!empty($item['original_price'])) {
        $totalSaved += ((float)$item['original_price'] - (float)$item['price']) * (int)$item['qty'];
    }
}
?>
<style>
.gs-cart{max-width:1100px;margin:0 auto;padding:40px 24px}
.gs-cart h1{font-size:26px;font-weight:800;margin-bottom:28px;color:#0f172a}
.gs-cart-layout{display:grid;grid-template-columns:1fr 320px;gap:32px;align-items:start}
.gs-cart-table{width:100%;border-collapse:collapse;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden}
.gs-cart-table thead tr{background:#f8fafc;border-bottom:1px solid #e2e8f0}
.gs-cart-table th{padding:12px 16px;text-align:left;font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b}
.gs-cart-table td{padding:14px 16px;border-bottom:1px solid #f1f5f9;vertical-align:middle}
.gs-cart-table tr:last-child td{border-bottom:none}
.gs-product-cell{display:flex;align-items:center;gap:14px}
.gs-product-cell img{width:56px;height:56px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0;flex-shrink:0}
.gs-product-cell .ph{width:56px;height:56px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0}
.gs-item-name{font-size:14px;font-weight:700;color:#0f172a}
.gs-item-attrs{font-size:12px;color:#64748b;margin-top:2px}
.gs-qty-input{width:64px;padding:6px 8px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:14px;text-align:center}
.gs-item-price{font-size:14px;font-weight:600;color:#374151}
.gs-item-total{font-size:15px;font-weight:700;color:#10B27C}
.gs-remove-btn{background:none;border:none;color:#94a3b8;cursor:pointer;font-size:16px;padding:4px;border-radius:4px;transition:color .15s}
.gs-remove-btn:hover{color:#ef4444}
.gs-cart-actions{display:flex;justify-content:space-between;align-items:center;margin-top:16px;flex-wrap:wrap;gap:10px}
.gs-update-btn{padding:9px 18px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;color:#374151;transition:background .15s}
.gs-update-btn:hover{background:#e2e8f0}
.gs-continue-link{font-size:13px;color:#10B27C;text-decoration:none;font-weight:600}
.gs-continue-link:hover{text-decoration:underline}
/* Summary sidebar */
.gs-summary{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:24px;position:sticky;top:24px}
.gs-summary h2{font-size:17px;font-weight:800;margin-bottom:20px;color:#0f172a}
.gs-summary-row{display:flex;justify-content:space-between;font-size:14px;color:#374151;margin-bottom:10px}
.gs-summary-row.bold{font-size:16px;font-weight:700;color:#0f172a;border-top:1px solid #e2e8f0;padding-top:12px;margin-top:4px}
.gs-summary-row .green{color:#10B27C}
.gs-checkout-btn{display:block;width:100%;padding:13px;background:#10B27C;color:#fff;border:none;border-radius:9px;font-size:15px;font-weight:700;cursor:pointer;transition:background .15s;margin-top:18px;text-align:center;text-decoration:none}
.gs-checkout-btn:hover{background:#0e9c6c}
/* Coupon */
.gs-coupon{margin-top:18px;border-top:1px solid #e2e8f0;padding-top:16px}
.gs-coupon label{font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#64748b;display:block;margin-bottom:6px}
.gs-coupon-row{display:flex;gap:6px}
.gs-coupon-row input{flex:1;padding:8px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px}
.gs-coupon-row button{padding:8px 14px;background:#0f172a;color:#fff;border:none;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;transition:background .15s}
.gs-coupon-row button:hover{background:#1e293b}
.gs-coupon-msg{margin-top:8px;font-size:12px;padding:7px 10px;border-radius:6px;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0}
.gs-coupon-msg.error{background:#fef2f2;color:#dc2626;border-color:#fecaca}
/* Empty cart */
.gs-empty{text-align:center;padding:80px 20px}
.gs-empty-icon{font-size:56px;margin-bottom:16px}
.gs-empty p{font-size:16px;color:#64748b;margin-bottom:20px}
@media(max-width:768px){.gs-cart-layout{grid-template-columns:1fr}.gs-summary{position:static}}
</style>

<div class="gs-cart">
    <h1>Shopping Cart</h1>

    <?php if (empty($cart)): ?>
    <div class="gs-empty">
        <div class="gs-empty-icon">🛒</div>
        <p>Your cart is empty.</p>
        <a href="<?= e($base) ?>/shop" style="display:inline-block;padding:10px 24px;background:#10B27C;color:#fff;border-radius:8px;font-weight:700;text-decoration:none">Browse Shop</a>
    </div>
    <?php else: ?>
    <div class="gs-cart-layout">
        <!-- Cart items -->
        <div>
            <form method="POST" action="<?= e($base) ?>/cart/update" id="gs-cart-form">
                <table class="gs-cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Total</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($cart as $key => $item):
                        $imgs  = json_decode((string)($item['images']??''),true) ?: [];
                        $thumb = $imgs[0] ?? '';
                        $attrs = $item['attrs'] ?? [];
                    ?>
                    <tr>
                        <td>
                            <div class="gs-product-cell">
                                <?php if ($thumb): ?>
                                <img src="<?= e($thumb) ?>" alt="<?= e($item['name']) ?>">
                                <?php else: ?>
                                <div class="ph">📦</div>
                                <?php endif ?>
                                <div>
                                    <div class="gs-item-name"><a href="<?= e($base) ?>/shop/<?= e($item['slug']??'') ?>" style="color:inherit;text-decoration:none"><?= e($item['name']) ?></a></div>
                                    <?php if (!empty($attrs)): ?>
                                    <div class="gs-item-attrs">
                                        <?= e(implode(', ', array_map(fn($k,$v)=>$k.': '.$v, array_keys($attrs), $attrs))) ?>
                                    </div>
                                    <?php endif ?>
                                </div>
                            </div>
                        </td>
                        <td><span class="gs-item-price"><?= $symbol ?><?= number_format((float)$item['price'],2) ?></span></td>
                        <td><input type="number" name="qty[<?= e($key) ?>]" value="<?= (int)$item['qty'] ?>" min="0" class="gs-qty-input"></td>
                        <td><span class="gs-item-total"><?= $symbol ?><?= number_format((float)$item['price'] * (int)$item['qty'],2) ?></span></td>
                        <td>
                            <form method="POST" action="<?= e($base) ?>/cart/remove" style="display:inline">
                                <input type="hidden" name="key" value="<?= e($key) ?>">
                                <button type="submit" class="gs-remove-btn" title="Remove">✕</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach ?>
                    </tbody>
                </table>
                <div class="gs-cart-actions">
                    <a href="<?= e($base) ?>/shop" class="gs-continue-link">← Continue Shopping</a>
                    <button type="submit" class="gs-update-btn">Update Cart</button>
                </div>
            </form>
        </div>

        <!-- Summary -->
        <div class="gs-summary">
            <h2>Order Summary</h2>
            <div class="gs-summary-row">
                <span>Subtotal</span>
                <span><?= $symbol ?><?= number_format((float)($totals['subtotal']??0),2) ?></span>
            </div>
            <?php if (!empty($totals['tax'])): ?>
            <div class="gs-summary-row">
                <span>Tax</span>
                <span><?= $symbol ?><?= number_format((float)$totals['tax'],2) ?></span>
            </div>
            <?php endif ?>
            <?php if (!empty($totals['shipping'])): ?>
            <div class="gs-summary-row">
                <span>Shipping</span>
                <span><?= (float)$totals['shipping']>0 ? $symbol.number_format((float)$totals['shipping'],2) : '<span class="green">Free</span>' ?></span>
            </div>
            <?php endif ?>
            <?php if ($totalSaved > 0): ?>
            <div class="gs-summary-row" style="color:#16a34a;font-weight:600">
                <span>🏷 You save</span>
                <span>-<?= $symbol ?><?= number_format($totalSaved, 2) ?></span>
            </div>
            <?php endif ?>
            <div class="gs-summary-row bold">
                <span>Total</span>
                <span class="green"><?= $symbol ?><?= number_format((float)($totals['total']??0),2) ?></span>
            </div>
            <a href="<?= e($base) ?>/checkout" class="gs-checkout-btn">Proceed to Checkout →</a>

            <!-- Coupon -->
            <div class="gs-coupon">
                <label>Have a coupon?</label>
                <form method="POST" action="<?= e($base) ?>/cart/coupon">
                    <div class="gs-coupon-row">
                        <input type="text" name="coupon_code" placeholder="Enter code" value="">
                        <button type="submit">Apply</button>
                    </div>
                </form>
                <?php if (!empty($couponMsg)): ?>
                <div class="gs-coupon-msg <?= str_contains(strtolower($couponMsg),'invalid')||str_contains(strtolower($couponMsg),'error')?'error':'' ?>">
                    <?= e($couponMsg) ?>
                </div>
                <?php endif ?>
            </div>
        </div>
    </div>
    <?php endif ?>
</div>
