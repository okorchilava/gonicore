<?php
$pageTitle = 'Order ' . e($order['order_number']);
$activeNav = 'delivery-orders';
$topbarActions = '';
$sc = $delivery->statusColor($order['status']);
?>
<?php if (!empty($flash)): ?><div id="gc-flash" data-msg="<?= e($flash) ?>" data-icon="success" style="display:none"></div><?php endif ?>

<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;max-width:1000px">
<div style="display:flex;flex-direction:column;gap:16px">

    <div class="card">
        <div class="card-header" style="justify-content:space-between">
            <div>
                <span style="font-family:monospace;font-size:18px;font-weight:900"><?= e($order['order_number']) ?></span>
                <span style="background:<?= $sc ?>22;color:<?= $sc ?>;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;margin-left:8px"><?= $delivery->statusLabel($order['status']) ?></span>
                <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:<?= $order['type']==='food'?'#fef3c7':'#eff6ff' ?>;color:<?= $order['type']==='food'?'#92400e':'#1e40af' ?>;margin-left:4px"><?= $order['type']==='food'?'🍕 Food':'📦 Courier' ?></span>
            </div>
            <div style="font-size:12px;color:var(--muted)"><?= date('d M Y, H:i', strtotime($order['created_at'])) ?></div>
        </div>
        <div class="card-body" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:13.5px">
            <div><span style="color:var(--muted)">Sender</span><br><strong><?= e($order['sender_name'] ?: '—') ?></strong><br><a href="tel:<?= e($order['sender_phone']) ?>"><?= e($order['sender_phone']) ?></a></div>
            <div><span style="color:var(--muted)">Recipient</span><br><strong><?= e($order['recipient_name'] ?: '—') ?></strong><br><a href="tel:<?= e($order['recipient_phone']) ?>"><?= e($order['recipient_phone']) ?></a></div>
            <div><span style="color:var(--muted)">Pickup</span><br><?= e($order['pickup_address'] ?: '—') ?></div>
            <div><span style="color:var(--muted)">Delivery</span><br><strong><?= e($order['delivery_address']) ?></strong></div>
            <?php if ($order['package_desc']): ?>
            <div><span style="color:var(--muted)">Package</span><br><?= e($order['package_desc']) ?> <?= $order['package_weight'] ? '(' . $order['package_weight'] . ' kg)' : '' ?></div>
            <?php endif ?>
            <?php if (!empty($order['zone'])): ?>
            <div><span style="color:var(--muted)">Zone</span><br><?= e($order['zone']['name']) ?> · ETA <?= $order['zone']['eta_minutes'] ?> min</div>
            <?php endif ?>
            <?php if ($order['customer_note']): ?>
            <div style="grid-column:span 2"><span style="color:var(--muted)">Note</span><br><?= e($order['customer_note']) ?></div>
            <?php endif ?>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3>Payment</h3></div>
        <div class="card-body" style="font-size:13.5px;display:flex;gap:24px">
            <div><span style="color:var(--muted)">Method</span><br><strong><?= ucfirst($order['payment_method']) ?></strong></div>
            <div><span style="color:var(--muted)">Amount</span><br><strong style="font-size:18px"><?= $delivery->formatPrice((float)$order['price']) ?></strong></div>
            <div><span style="color:var(--muted)">Status</span><br>
                <span style="color:<?= $order['payment_status']==='paid'?'#10b981':'#f59e0b' ?>;font-weight:700"><?= $order['payment_status']==='paid'?'✓ Paid':'⏳ Unpaid' ?></span>
            </div>
            <?php if ($order['transaction_id']): ?>
            <div><span style="color:var(--muted)">Transaction</span><br><code style="font-size:11.5px"><?= e($order['transaction_id']) ?></code></div>
            <?php endif ?>
        </div>
    </div>

    <?php if (!empty($order['driver'])): ?>
    <div class="card">
        <div class="card-header"><h3>Driver</h3></div>
        <div class="card-body" style="display:flex;gap:16px;align-items:center">
            <div style="width:48px;height:48px;border-radius:50%;background:var(--accent)22;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0">🧑</div>
            <div style="font-size:13.5px">
                <div style="font-weight:700"><?= e($order['driver']['name']) ?></div>
                <div style="color:var(--muted)"><?= e($order['driver']['vehicle_type']) ?> · <?= e($order['driver']['vehicle_num']) ?></div>
                <div><a href="tel:<?= e($order['driver']['phone']) ?>"><?= e($order['driver']['phone']) ?></a></div>
                <?php if ($order['driver_note']): ?>
                <div style="margin-top:6px;font-size:12.5px;color:var(--muted)"><?= e($order['driver_note']) ?></div>
                <?php endif ?>
            </div>
        </div>
    </div>
    <?php endif ?>

</div>

<!-- Actions -->
<div style="display:flex;flex-direction:column;gap:16px">
    <div class="card">
        <div class="card-header"><h3>Assign / Payment</h3></div>
        <div class="card-body">
            <form method="POST" action="<?= e($base) ?>/manage/delivery/orders/<?= (int)$order['id'] ?>/update">
                <div class="form-group">
                    <label class="form-label">Assign Driver</label>
                    <select name="driver_id" class="form-select">
                        <option value="">— Unassigned —</option>
                        <?php foreach ($drivers as $d): ?>
                        <option value="<?= (int)$d['id'] ?>" <?= (int)$d['id']===(int)($order['driver_id']??0)?'selected':'' ?>><?= e($d['name']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Payment Status</label>
                    <select name="payment_status" class="form-select">
                        <option value="unpaid" <?= $order['payment_status']==='unpaid'?'selected':'' ?>>Unpaid</option>
                        <option value="paid"   <?= $order['payment_status']==='paid'?'selected':'' ?>>Paid</option>
                    </select>
                </div>
                <div class="form-group" style="margin-bottom:0">
                    <label class="form-label">Driver Note</label>
                    <textarea name="driver_note" class="form-input" rows="2"><?= e($order['driver_note']) ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:14px;font-size:13px">Update</button>
            </form>
        </div>
    </div>
    <a href="<?= e($base) ?>/manage/delivery/orders" class="btn btn-ghost" style="text-align:center;justify-content:center">← All Orders</a>
</div>
</div>
