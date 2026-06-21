<?php
$pageTitle     = 'GoniDelivery';
$activeNav     = 'delivery';
$topbarActions = '<a href="' . e($base) . '/manage/delivery/orders" class="btn btn-primary" style="font-size:13px">All Orders</a>';
?>
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px">
    <?php foreach ([
        ['Orders',   $stats['total'],     '#4f46e5'],
        ['Pending',  $stats['pending'],   '#f59e0b'],
        ['Delivered',$stats['delivered'], '#10b981'],
        ['Revenue',  number_format($stats['revenue'],2), '#10b981'],
    ] as [$label,$val,$color]): ?>
    <div class="card"><div class="card-body" style="text-align:center;padding:20px">
        <div style="font-size:28px;font-weight:900;color:<?= $color ?>"><?= $val ?></div>
        <div style="font-size:12px;color:var(--muted);margin-top:4px"><?= $label ?></div>
    </div></div>
    <?php endforeach ?>
</div>

<div style="display:grid;grid-template-columns:1fr 280px;gap:16px">
<div class="card">
    <div class="card-header" style="justify-content:space-between">
        <h3>Recent Orders</h3>
        <a href="<?= e($base) ?>/manage/delivery/orders" style="font-size:13px;color:var(--accent)">View all →</a>
    </div>
    <?php if (empty($recent)): ?>
    <div class="empty"><div class="empty-icon">🚗</div><h3>No orders yet</h3></div>
    <?php else: ?>
    <table class="data-table"><thead><tr><th>#</th><th>Sender</th><th>Address</th><th>Status</th><th></th></tr></thead><tbody>
    <?php foreach ($recent as $o):
        $c = $delivery->statusColor($o['status']);
    ?>
    <tr>
        <td style="font-family:monospace;font-size:12px"><?= e($o['order_number']) ?></td>
        <td style="font-size:13px"><?= e($o['sender_name'] ?: $o['sender_phone']) ?></td>
        <td style="font-size:12.5px;color:var(--muted);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($o['delivery_address']) ?></td>
        <td><span style="background:<?= $c ?>22;color:<?= $c ?>;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px"><?= $delivery->statusLabel($o['status']) ?></span></td>
        <td><a href="<?= e($base) ?>/manage/delivery/orders/<?= (int)$o['id'] ?>" class="btn btn-ghost" style="font-size:12px">View</a></td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php endif ?>
</div>

<div class="card">
    <div class="card-header"><h3>Active Drivers</h3></div>
    <?php if (empty($drivers)): ?>
    <div style="padding:16px;color:var(--muted);font-size:13px;text-align:center">
        No drivers. <a href="<?= e($base) ?>/manage/delivery/drivers">Add one →</a>
    </div>
    <?php else: ?>
    <?php foreach ($drivers as $d): ?>
    <div style="padding:10px 16px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px">
        <div style="width:36px;height:36px;border-radius:50%;background:var(--accent)22;display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">🧑</div>
        <div>
            <div style="font-size:13px;font-weight:700"><?= e($d['name']) ?></div>
            <div style="font-size:11.5px;color:var(--muted)"><?= e($d['vehicle_type']) ?> · <?= e($d['phone']) ?></div>
        </div>
    </div>
    <?php endforeach ?>
    <?php endif ?>
    <div style="padding:12px 16px">
        <a href="<?= e($base) ?>/manage/delivery/drivers" class="btn btn-ghost" style="font-size:12px;width:100%;justify-content:center">Manage Drivers →</a>
    </div>
</div>
</div>
