<?php
$pageTitle = 'Orders — GoniStore';
$activeNav = 'store';
$topbarActions = '';
?>
<div class="gs-nav-bar" style="display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap">
    <a href="<?= e($base) ?>/manage/store" style="padding:7px 14px;background:#fff;border:1px solid var(--border);border-radius:7px;font-size:13px;font-weight:600;color:var(--muted);text-decoration:none">Dashboard</a>
    <a href="<?= e($base) ?>/manage/store/products" style="padding:7px 14px;background:#fff;border:1px solid var(--border);border-radius:7px;font-size:13px;font-weight:600;color:var(--muted);text-decoration:none">Products</a>
    <a href="<?= e($base) ?>/manage/store/orders" style="padding:7px 14px;background:var(--accent);color:#fff;border:1px solid var(--accent);border-radius:7px;font-size:13px;font-weight:600;text-decoration:none">Orders</a>
    <a href="<?= e($base) ?>/manage/store/coupons" style="padding:7px 14px;background:#fff;border:1px solid var(--border);border-radius:7px;font-size:13px;font-weight:600;color:var(--muted);text-decoration:none">Coupons</a>
    <a href="<?= e($base) ?>/manage/store/settings" style="padding:7px 14px;background:#fff;border:1px solid var(--border);border-radius:7px;font-size:13px;font-weight:600;color:var(--muted);text-decoration:none">Settings</a>
</div>

<!-- Status filter tabs -->
<div style="display:flex;gap:6px;margin-bottom:16px;flex-wrap:wrap">
    <a href="?status=" style="padding:6px 12px;border-radius:6px;font-size:12.5px;font-weight:600;text-decoration:none;border:1px solid var(--border);background:<?= ($status??'')?'#fff':'var(--accent)' ?>;color:<?= ($status??'')?'var(--muted)':'#fff' ?>">All</a>
    <?php foreach ($statuses as $sk => $sv): ?>
    <a href="?status=<?= $sk ?>" style="padding:6px 12px;border-radius:6px;font-size:12.5px;font-weight:600;text-decoration:none;border:1px solid <?= $sv['color'] ?>33;background:<?= ($status??'')===$sk?$sv['color'].'22':'#fff' ?>;color:<?= $sv['color'] ?>"><?= $sv['label'] ?></a>
    <?php endforeach ?>
</div>

<div class="card" style="padding:0">
    <table style="width:100%;border-collapse:collapse">
        <thead><tr style="border-bottom:1px solid var(--border);font-size:12px;color:var(--muted);text-transform:uppercase">
            <th style="padding:12px 16px;text-align:left">Order #</th>
            <th style="padding:12px 16px;text-align:left">Customer</th>
            <th style="padding:12px 16px;text-align:left">Date</th>
            <th style="padding:12px 16px;text-align:center">Status</th>
            <th style="padding:12px 16px;text-align:right">Total</th>
            <th style="padding:12px 16px;text-align:right">Actions</th>
        </tr></thead>
        <tbody>
        <?php if (empty($orders)): ?>
        <tr><td colspan="6" style="padding:40px;text-align:center;color:var(--muted)">No orders found.</td></tr>
        <?php else: foreach ($orders as $o):
            $st = $statuses[$o['status']] ?? ['label'=>$o['status'],'color'=>'#94a3b8'];
            $billing = json_decode((string)$o['billing'],true) ?: [];
            $customerName = trim(($billing['first_name']??'').' '.($billing['last_name']??'')) ?: 'Guest';
        ?>
        <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:10px 16px"><a href="<?= e($base) ?>/manage/store/orders/<?= (int)$o['id'] ?>" style="font-weight:700">#<?= e($o['order_number']) ?></a></td>
            <td style="padding:10px 16px">
                <div style="font-weight:600;font-size:13.5px"><?= e($customerName) ?></div>
                <div style="font-size:12px;color:var(--muted)"><?= e($billing['email']??'') ?></div>
            </td>
            <td style="padding:10px 16px;color:var(--muted);font-size:13px"><?= date('M j, Y H:i', strtotime($o['created_at'])) ?></td>
            <td style="padding:10px 16px;text-align:center">
                <span style="background:<?= $st['color'] ?>22;color:<?= $st['color'] ?>;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600"><?= $st['label'] ?></span>
            </td>
            <td style="padding:10px 16px;text-align:right;font-weight:700"><?= e($o['currency']) ?> <?= number_format((float)$o['total'],2) ?></td>
            <td style="padding:10px 16px;text-align:right">
                <a href="<?= e($base) ?>/manage/store/orders/<?= (int)$o['id'] ?>" class="btn btn-ghost" style="font-size:12px;padding:5px 10px">View</a>
            </td>
        </tr>
        <?php endforeach; endif ?>
        </tbody>
    </table>
</div>
