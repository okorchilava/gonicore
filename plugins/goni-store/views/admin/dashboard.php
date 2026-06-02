<?php
$pageTitle = 'GoniStore';
$activeNav = 'store';
$topbarActions = '';
?>
<style>
.gs-stat-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:16px;margin-bottom:24px}
.gs-stat{background:#fff;border:1px solid var(--border);border-radius:12px;padding:20px 22px}
.gs-stat-value{font-size:28px;font-weight:800;color:var(--text);margin-bottom:4px}
.gs-stat-label{font-size:13px;color:var(--muted)}
.gs-stat-icon{font-size:26px;margin-bottom:10px}
.gs-nav-bar{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.gs-nav-bar a{padding:7px 14px;background:#fff;border:1px solid var(--border);border-radius:7px;font-size:13px;font-weight:600;color:var(--muted);text-decoration:none;transition:all .15s}
.gs-nav-bar a:hover,.gs-nav-bar a.active{background:var(--accent);color:#fff;border-color:var(--accent)}
</style>

<div class="gs-nav-bar">
    <a href="<?= e($base) ?>/manage/store" class="active">Dashboard</a>
    <a href="<?= e($base) ?>/manage/store/products">Products</a>
    <a href="<?= e($base) ?>/manage/store/categories">Categories</a>
    <a href="<?= e($base) ?>/manage/store/orders">Orders</a>
    <a href="<?= e($base) ?>/manage/store/coupons">Coupons</a>
    <a href="<?= e($base) ?>/manage/store/settings">Settings</a>
</div>

<div class="gs-stat-grid">
    <div class="gs-stat">
        <div class="gs-stat-icon">📦</div>
        <div class="gs-stat-value"><?= number_format($totals['products']) ?></div>
        <div class="gs-stat-label">Total Products</div>
    </div>
    <div class="gs-stat">
        <div class="gs-stat-icon">🛒</div>
        <div class="gs-stat-value"><?= number_format($totals['orders']) ?></div>
        <div class="gs-stat-label">Total Orders</div>
    </div>
    <div class="gs-stat">
        <div class="gs-stat-icon">💰</div>
        <div class="gs-stat-value">–</div>
        <div class="gs-stat-label">Revenue (soon)</div>
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3>Recent Orders</h3>
        <a href="<?= e($base) ?>/manage/store/orders" class="btn btn-ghost" style="font-size:12px">View all →</a>
    </div>
    <div class="card-body" style="padding:0">
        <?php if (empty($recentOrders)): ?>
        <div style="padding:32px;text-align:center;color:var(--muted)">No orders yet.</div>
        <?php else: ?>
        <table style="width:100%;border-collapse:collapse">
            <thead><tr style="border-bottom:1px solid var(--border);font-size:12px;color:var(--muted);text-transform:uppercase">
                <th style="padding:10px 16px;text-align:left">Order</th>
                <th style="padding:10px 16px;text-align:left">Date</th>
                <th style="padding:10px 16px;text-align:left">Status</th>
                <th style="padding:10px 16px;text-align:right">Total</th>
            </tr></thead>
            <tbody>
            <?php foreach ($recentOrders as $o):
                $statuses = \GoniStore\StoreService::orderStatuses();
                $st = $statuses[$o['status']] ?? ['label'=>$o['status'],'color'=>'#94a3b8'];
            ?>
            <tr style="border-bottom:1px solid var(--border)">
                <td style="padding:10px 16px"><a href="<?= e($base) ?>/manage/store/orders/<?= (int)$o['id'] ?>">#<?= e($o['order_number']) ?></a></td>
                <td style="padding:10px 16px;color:var(--muted);font-size:13px"><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
                <td style="padding:10px 16px"><span style="background:<?= $st['color'] ?>22;color:<?= $st['color'] ?>;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600"><?= $st['label'] ?></span></td>
                <td style="padding:10px 16px;text-align:right;font-weight:700"><?= e($o['currency']) ?> <?= number_format((float)$o['total'],2) ?></td>
            </tr>
            <?php endforeach ?>
            </tbody>
        </table>
        <?php endif ?>
    </div>
</div>
