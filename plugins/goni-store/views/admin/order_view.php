<?php
$pageTitle = 'Order #'.e($order['order_number']);
$activeNav = 'store';
ob_start(); ?>
<a href="<?= e($base) ?>/manage/store/orders" class="topbar-btn ghost">← Orders</a>
<?php $topbarActions = ob_get_clean();
$st = $statuses[$order['status']] ?? ['label'=>$order['status'],'color'=>'#94a3b8'];
?>
<?php if ($success ?? null): ?>
<div class="alert alert-success" style="margin-bottom:16px"><?= e($success) ?></div>
<?php endif ?>

<div style="display:grid;grid-template-columns:1fr 320px;gap:20px;align-items:start">
    <!-- Left -->
    <div style="display:flex;flex-direction:column;gap:16px">
        <!-- Items -->
        <div class="card" style="padding:0">
            <div class="card-header"><h3>Order Items</h3></div>
            <table style="width:100%;border-collapse:collapse">
                <thead><tr style="border-bottom:1px solid var(--border);font-size:12px;color:var(--muted);text-transform:uppercase">
                    <th style="padding:10px 16px;text-align:left">Product</th>
                    <th style="padding:10px 16px;text-align:center">Qty</th>
                    <th style="padding:10px 16px;text-align:right">Price</th>
                    <th style="padding:10px 16px;text-align:right">Total</th>
                </tr></thead>
                <tbody>
                <?php foreach ($order['items'] as $item): ?>
                <tr style="border-bottom:1px solid var(--border)">
                    <td style="padding:10px 16px">
                        <div style="font-weight:600"><?= e($item['name']) ?></div>
                        <?php if (!empty($item['sku'])): ?><div style="font-size:12px;color:var(--muted)">SKU: <?= e($item['sku']) ?></div><?php endif ?>
                        <?php foreach ($item['attributes'] as $ak=>$av): ?>
                        <div style="font-size:12px;color:var(--muted)"><?= e($ak) ?>: <?= e($av) ?></div>
                        <?php endforeach ?>
                    </td>
                    <td style="padding:10px 16px;text-align:center"><?= (int)$item['quantity'] ?></td>
                    <td style="padding:10px 16px;text-align:right"><?= number_format((float)$item['price'],2) ?></td>
                    <td style="padding:10px 16px;text-align:right;font-weight:700"><?= number_format((float)$item['total'],2) ?></td>
                </tr>
                <?php endforeach ?>
                </tbody>
                <tfoot>
                    <tr><td colspan="3" style="padding:8px 16px;text-align:right;color:var(--muted);font-size:13px">Subtotal</td><td style="padding:8px 16px;text-align:right"><?= number_format((float)$order['subtotal'],2) ?></td></tr>
                    <?php if ((float)$order['tax']>0): ?><tr><td colspan="3" style="padding:4px 16px;text-align:right;color:var(--muted);font-size:13px">Tax</td><td style="padding:4px 16px;text-align:right"><?= number_format((float)$order['tax'],2) ?></td></tr><?php endif ?>
                    <?php if ((float)$order['shipping_cost']>0): ?><tr><td colspan="3" style="padding:4px 16px;text-align:right;color:var(--muted);font-size:13px">Shipping</td><td style="padding:4px 16px;text-align:right"><?= number_format((float)$order['shipping_cost'],2) ?></td></tr><?php endif ?>
                    <?php if ((float)$order['discount']>0): ?><tr><td colspan="3" style="padding:4px 16px;text-align:right;color:var(--muted);font-size:13px">Discount</td><td style="padding:4px 16px;text-align:right;color:var(--danger)">-<?= number_format((float)$order['discount'],2) ?></td></tr><?php endif ?>
                    <tr style="border-top:2px solid var(--border)"><td colspan="3" style="padding:10px 16px;text-align:right;font-weight:700">Total</td><td style="padding:10px 16px;text-align:right;font-weight:800;font-size:16px"><?= e($order['currency']) ?> <?= number_format((float)$order['total'],2) ?></td></tr>
                </tfoot>
            </table>
        </div>

        <!-- Billing & Shipping -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <?php foreach (['billing'=>'Billing Address','shipping'=>'Shipping Address'] as $key=>$title): $addr = $order[$key]; ?>
            <div class="card">
                <div class="card-header"><h3><?= $title ?></h3></div>
                <div class="card-body" style="font-size:13.5px;line-height:1.8">
                    <strong><?= e(($addr['first_name']??'').' '.($addr['last_name']??'')) ?></strong><br>
                    <?= e($addr['email']??'') ?><br>
                    <?= e($addr['phone']??'') ?><br>
                    <?= e($addr['address']??'') ?><br>
                    <?= e($addr['city']??'') ?><?= !empty($addr['state'])?' '.e($addr['state']):'' ?> <?= e($addr['zip']??'') ?><br>
                    <?= e($addr['country']??'') ?>
                </div>
            </div>
            <?php endforeach ?>
        </div>

        <!-- Notes history -->
        <?php if (!empty($order['notes'])): ?>
        <div class="card">
            <div class="card-header"><h3>Order History</h3></div>
            <div class="card-body" style="padding:0">
                <?php foreach ($order['notes'] as $note): ?>
                <div style="padding:10px 16px;border-bottom:1px solid var(--border);font-size:13px">
                    <div style="color:var(--muted);font-size:11px;margin-bottom:4px"><?= date('M j, Y H:i', strtotime($note['created_at'])) ?></div>
                    <?= e($note['note']) ?>
                </div>
                <?php endforeach ?>
            </div>
        </div>
        <?php endif ?>
    </div>

    <!-- Right sidebar -->
    <div style="display:flex;flex-direction:column;gap:14px">
        <!-- Status update -->
        <div class="card">
            <div class="card-header"><h3>Order Status</h3></div>
            <div class="card-body" style="padding:16px">
                <div style="margin-bottom:12px">
                    <span style="background:<?= $st['color'] ?>22;color:<?= $st['color'] ?>;padding:6px 14px;border-radius:20px;font-size:13px;font-weight:700"><?= $st['label'] ?></span>
                </div>
                <form method="POST" action="<?= e($base) ?>/manage/store/orders/<?= (int)$order['id'] ?>/status">
                    <div class="form-group" style="margin-bottom:10px">
                        <label class="form-label">Change Status</label>
                        <select name="status" class="form-select">
                            <?php foreach ($statuses as $sk=>$sv): ?>
                            <option value="<?= $sk ?>" <?= $order['status']===$sk?'selected':'' ?>><?= $sv['label'] ?></option>
                            <?php endforeach ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin-bottom:12px">
                        <label class="form-label">Note (optional)</label>
                        <textarea name="note" class="form-textarea" rows="2" placeholder="Add a note..."></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">Update Status</button>
                </form>
            </div>
        </div>

        <!-- Order details -->
        <div class="card">
            <div class="card-header"><h3>Details</h3></div>
            <div class="card-body" style="padding:16px;font-size:13px;line-height:2">
                <div><strong>Order #:</strong> <?= e($order['order_number']) ?></div>
                <div><strong>Date:</strong> <?= date('M j, Y H:i', strtotime($order['created_at'])) ?></div>
                <div><strong>Payment:</strong> <?= e($order['payment_method']) ?></div>
                <div><strong>Payment Status:</strong> <?= e($order['payment_status']) ?></div>
                <?php if ($order['coupon_code']): ?>
                <div><strong>Coupon:</strong> <?= e($order['coupon_code']) ?></div>
                <?php endif ?>
                <?php if ($order['customer_note']): ?>
                <div><strong>Note:</strong> <?= e($order['customer_note']) ?></div>
                <?php endif ?>
            </div>
        </div>
    </div>
</div>
