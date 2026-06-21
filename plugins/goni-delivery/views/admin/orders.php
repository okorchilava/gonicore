<?php
$pageTitle = 'Delivery Orders';
$activeNav = 'delivery-orders';
$topbarActions = '';
?>
<div class="card">
    <div class="card-header" style="justify-content:space-between;flex-wrap:wrap;gap:10px">
        <h3>Orders <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= $total ?>)</span></h3>
        <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
            <select name="status" class="form-select" style="font-size:13px;padding:6px 10px" onchange="this.form.submit()">
                <option value="">All Statuses</option>
                <?php foreach ($delivery->allStatuses() as $s): ?>
                <option value="<?= $s ?>" <?= $s===$filterStatus?'selected':'' ?>><?= $delivery->statusLabel($s) ?></option>
                <?php endforeach ?>
            </select>
            <select name="type" class="form-select" style="font-size:13px;padding:6px 10px" onchange="this.form.submit()">
                <option value="">All Types</option>
                <option value="courier" <?= $filterType==='courier'?'selected':'' ?>>Courier</option>
                <option value="food" <?= $filterType==='food'?'selected':'' ?>>Food</option>
            </select>
        </form>
    </div>
    <?php if (empty($items)): ?>
    <div class="empty"><div class="empty-icon">📦</div><h3>No orders found</h3></div>
    <?php else: ?>
    <table class="data-table"><thead><tr>
        <th>Order #</th><th>Type</th><th>Sender</th><th>Delivery Address</th><th>Price</th><th>Payment</th><th>Status</th><th>Date</th><th></th>
    </tr></thead><tbody>
    <?php foreach ($items as $o):
        $c = $delivery->statusColor($o['status']);
    ?>
    <tr>
        <td style="font-family:monospace;font-size:12px;font-weight:700"><?= e($o['order_number']) ?></td>
        <td><span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:<?= $o['type']==='food'?'#fef3c7':'#eff6ff' ?>;color:<?= $o['type']==='food'?'#92400e':'#1e40af' ?>"><?= $o['type']==='food'?'🍕 Food':'📦 Courier' ?></span></td>
        <td>
            <div style="font-size:13px;font-weight:600"><?= e($o['sender_name'] ?: '—') ?></div>
            <div style="font-size:11.5px;color:var(--muted)"><?= e($o['sender_phone']) ?></div>
        </td>
        <td style="font-size:12.5px;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($o['delivery_address']) ?></td>
        <td style="font-size:13px;font-weight:700"><?= $delivery->formatPrice((float)$o['price']) ?></td>
        <td>
            <span style="font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;background:<?= $o['payment_status']==='paid'?'#d1fae5':'#fef3c7' ?>;color:<?= $o['payment_status']==='paid'?'#059669':'#92400e' ?>">
                <?= $o['payment_status']==='paid'?'Paid':'Unpaid' ?>
            </span>
        </td>
        <td><span style="background:<?= $c ?>22;color:<?= $c ?>;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;white-space:nowrap"><?= $delivery->statusLabel($o['status']) ?></span></td>
        <td style="font-size:12px;color:var(--muted);white-space:nowrap"><?= date('d M, H:i', strtotime($o['created_at'])) ?></td>
        <td><a href="<?= e($base) ?>/manage/delivery/orders/<?= (int)$o['id'] ?>" class="btn btn-ghost" style="font-size:12px">View</a></td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php if ($pages > 1): ?>
    <div style="padding:16px;display:flex;gap:6px;justify-content:center">
        <?php for ($i=1;$i<=$pages;$i++): ?>
        <a href="?page=<?= $i ?>&status=<?= urlencode($filterStatus) ?>&type=<?= urlencode($filterType) ?>"
           style="padding:5px 12px;border-radius:6px;border:1px solid var(--border);font-size:13px;<?= $i===$page?'background:var(--accent);color:#fff;border-color:var(--accent)':'' ?>"><?= $i ?></a>
        <?php endfor ?>
    </div>
    <?php endif ?>
    <?php endif ?>
</div>
