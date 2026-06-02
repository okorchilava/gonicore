<?php
/**
 * GoniStore — Order Received / Thank You page
 * Variables: $order, $settings, $base
 */
$symbol  = $settings['currency_symbol'] ?? '$';
$billing = json_decode((string)($order['billing'] ?? ''), true) ?: [];
$items   = json_decode((string)($order['items'] ?? ''), true) ?: [];

// items may be stored as a joined query result instead
if (empty($items) && isset($order['order_items'])) {
    $items = $order['order_items'];
}

$statusColors = [
    'pending'    => ['#f59e0b','#fffbeb','#fef3c7'],
    'processing' => ['#3b82f6','#eff6ff','#dbeafe'],
    'completed'  => ['#10b981','#f0fdf4','#d1fae5'],
    'cancelled'  => ['#ef4444','#fef2f2','#fecaca'],
    'refunded'   => ['#8b5cf6','#f5f3ff','#ede9fe'],
    'on-hold'    => ['#64748b','#f8fafc','#e2e8f0'],
];
$sc = $statusColors[$order['status']] ?? $statusColors['pending'];
?>
<style>
.gs-ty{max-width:760px;margin:0 auto;padding:48px 24px}
.gs-ty-hero{text-align:center;margin-bottom:40px}
.gs-ty-icon{font-size:64px;margin-bottom:12px;line-height:1}
.gs-ty-hero h1{font-size:26px;font-weight:800;color:#0f172a;margin-bottom:8px}
.gs-ty-hero p{font-size:16px;color:#64748b}
.gs-ty-hero a{color:#10B27C;text-decoration:none;font-weight:600}
.gs-order-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;margin-bottom:24px}
.gs-order-card-header{background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:16px 24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px}
.gs-order-card-header h2{font-size:15px;font-weight:700;color:#0f172a}
.gs-status-badge{display:inline-block;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:700;text-transform:capitalize}
.gs-order-meta{display:grid;grid-template-columns:repeat(4,1fr);gap:0;border-bottom:1px solid #f1f5f9}
.gs-meta-cell{padding:16px 24px;border-right:1px solid #f1f5f9}
.gs-meta-cell:last-child{border-right:none}
.gs-meta-label{font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;font-weight:700;margin-bottom:4px}
.gs-meta-value{font-size:14px;font-weight:700;color:#0f172a}
.gs-items-table{width:100%;border-collapse:collapse}
.gs-items-table th{padding:10px 24px;text-align:left;font-size:11px;text-transform:uppercase;letter-spacing:.05em;color:#94a3b8;font-weight:700;background:#fafafa;border-bottom:1px solid #f1f5f9}
.gs-items-table td{padding:12px 24px;border-bottom:1px solid #f8fafc;font-size:14px;color:#374151;vertical-align:middle}
.gs-items-table tr:last-child td{border-bottom:none}
.gs-item-n{font-weight:600;color:#0f172a}
.gs-item-attrs{font-size:12px;color:#94a3b8;margin-top:2px}
.gs-totals-block{padding:16px 24px;border-top:1px solid #f1f5f9}
.gs-totals-row{display:flex;justify-content:space-between;font-size:14px;color:#374151;padding:4px 0}
.gs-totals-row.final{font-size:16px;font-weight:700;color:#0f172a;border-top:1px solid #e2e8f0;padding-top:10px;margin-top:6px}
.gs-totals-row .green{color:#10B27C}
.gs-billing-card{background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:20px 24px;margin-bottom:24px}
.gs-billing-card h3{font-size:14px;font-weight:700;color:#0f172a;margin-bottom:10px}
.gs-billing-card p{font-size:13px;color:#64748b;line-height:1.7}
.gs-actions{display:flex;gap:12px;justify-content:center;margin-top:8px;flex-wrap:wrap}
.gs-actions a{display:inline-block;padding:11px 24px;border-radius:9px;font-size:14px;font-weight:700;text-decoration:none;transition:all .15s}
.gs-btn-primary{background:#10B27C;color:#fff}
.gs-btn-primary:hover{background:#0e9c6c}
.gs-btn-secondary{background:#f1f5f9;color:#374151;border:1px solid #e2e8f0}
.gs-btn-secondary:hover{background:#e2e8f0}
@media(max-width:600px){.gs-order-meta{grid-template-columns:repeat(2,1fr)}.gs-meta-cell{border-bottom:1px solid #f1f5f9}}
</style>

<div class="gs-ty">
    <!-- Hero -->
    <div class="gs-ty-hero">
        <div class="gs-ty-icon">🎉</div>
        <h1>Thank you for your order!</h1>
        <p>Your order has been placed and is being processed. We'll email you a confirmation to
            <?php if (!empty($billing['email'])): ?>
            <a href="mailto:<?= e($billing['email']) ?>"><?= e($billing['email']) ?></a>
            <?php else: ?>
            your email address
            <?php endif ?>
            shortly.
        </p>
    </div>

    <!-- Order summary card -->
    <div class="gs-order-card">
        <div class="gs-order-card-header">
            <h2>Order #<?= (int)$order['id'] ?></h2>
            <span class="gs-status-badge" style="background:<?= e($sc[1]) ?>;color:<?= e($sc[0]) ?>;border:1px solid <?= e($sc[2]) ?>">
                <?= e($order['status']) ?>
            </span>
        </div>

        <!-- Meta row -->
        <div class="gs-order-meta">
            <div class="gs-meta-cell">
                <div class="gs-meta-label">Date</div>
                <div class="gs-meta-value"><?= date('M j, Y', strtotime((string)$order['created_at'])) ?></div>
            </div>
            <div class="gs-meta-cell">
                <div class="gs-meta-label">Payment</div>
                <div class="gs-meta-value"><?= e(ucwords(str_replace('_',' ',(string)$order['payment_method']))) ?></div>
            </div>
            <div class="gs-meta-cell">
                <div class="gs-meta-label">Currency</div>
                <div class="gs-meta-value"><?= e($order['currency'] ?? 'USD') ?></div>
            </div>
            <div class="gs-meta-cell">
                <div class="gs-meta-label">Total</div>
                <div class="gs-meta-value" style="color:#10B27C"><?= $symbol ?><?= number_format((float)$order['total'],2) ?></div>
            </div>
        </div>

        <!-- Items -->
        <?php if (!empty($items)): ?>
        <table class="gs-items-table">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>SKU</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td>
                    <div class="gs-item-n"><?= e($item['name'] ?? '') ?></div>
                    <?php
                        $iatts = is_array($item['attributes']??null)
                            ? $item['attributes']
                            : json_decode((string)($item['attributes']??''),true) ?: [];
                        if (!empty($iatts)):
                            $attrStr = implode(', ', array_map(fn($k,$v)=>$k.': '.$v, array_keys($iatts), $iatts));
                    ?>
                    <div class="gs-item-attrs"><?= e($attrStr) ?></div>
                    <?php endif ?>
                </td>
                <td style="color:#94a3b8"><?= e($item['sku'] ?? '—') ?></td>
                <td><?= (int)($item['quantity'] ?? $item['qty'] ?? 1) ?></td>
                <td><?= $symbol ?><?= number_format((float)($item['price']??0),2) ?></td>
                <td style="font-weight:700"><?= $symbol ?><?= number_format((float)($item['total'] ?? ($item['price'] * ($item['quantity']??1))),2) ?></td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php endif ?>

        <!-- Totals -->
        <div class="gs-totals-block">
            <div class="gs-totals-row">
                <span>Subtotal</span>
                <span><?= $symbol ?><?= number_format((float)($order['subtotal']??0),2) ?></span>
            </div>
            <?php if (!empty($order['tax']) && (float)$order['tax'] > 0): ?>
            <div class="gs-totals-row">
                <span>Tax</span>
                <span><?= $symbol ?><?= number_format((float)$order['tax'],2) ?></span>
            </div>
            <?php endif ?>
            <?php if (!empty($order['shipping_cost']) && (float)$order['shipping_cost'] > 0): ?>
            <div class="gs-totals-row">
                <span>Shipping</span>
                <span><?= $symbol ?><?= number_format((float)$order['shipping_cost'],2) ?></span>
            </div>
            <?php endif ?>
            <?php if (!empty($order['discount']) && (float)$order['discount'] > 0): ?>
            <div class="gs-totals-row">
                <span>Discount <?= !empty($order['coupon_code']) ? '('.$order['coupon_code'].')' : '' ?></span>
                <span style="color:#ef4444">−<?= $symbol ?><?= number_format((float)$order['discount'],2) ?></span>
            </div>
            <?php endif ?>
            <div class="gs-totals-row final">
                <span>Order Total</span>
                <span class="green"><?= $symbol ?><?= number_format((float)$order['total'],2) ?></span>
            </div>
        </div>
    </div>

    <!-- Billing address -->
    <?php if (!empty($billing)): ?>
    <div class="gs-billing-card">
        <h3>Billing Address</h3>
        <p>
            <?= e(trim(($billing['first_name']??'').' '.($billing['last_name']??''))) ?><br>
            <?php if (!empty($billing['address'])): ?><?= e($billing['address']) ?><br><?php endif ?>
            <?php if (!empty($billing['city'])): ?><?= e($billing['city']) ?><?php endif ?>
            <?php if (!empty($billing['state'])): ?>, <?= e($billing['state']) ?><?php endif ?>
            <?php if (!empty($billing['zip'])): ?> <?= e($billing['zip']) ?><?php endif ?><br>
            <?php if (!empty($billing['country'])): ?><?= e($billing['country']) ?><br><?php endif ?>
            <?php if (!empty($billing['email'])): ?><?= e($billing['email']) ?><?php endif ?>
            <?php if (!empty($billing['phone'])): ?> · <?= e($billing['phone']) ?><?php endif ?>
        </p>
    </div>
    <?php endif ?>

    <?php if (!empty($order['customer_note'])): ?>
    <div class="gs-billing-card">
        <h3>Order Note</h3>
        <p><?= e($order['customer_note']) ?></p>
    </div>
    <?php endif ?>

    <!-- Actions -->
    <div class="gs-actions">
        <a href="<?= e($base) ?>/shop" class="gs-actions a gs-btn-primary">Continue Shopping</a>
        <a href="<?= e($base) ?>" class="gs-actions a gs-btn-secondary">Back to Home</a>
    </div>
</div>
