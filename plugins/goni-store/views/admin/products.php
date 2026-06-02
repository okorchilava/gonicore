<?php
$pageTitle = 'Products — GoniStore';
$activeNav = 'store';
ob_start(); ?>
<a href="<?= e($base) ?>/manage/store/products/new" class="topbar-btn">+ New Product</a>
<?php $topbarActions = ob_get_clean(); ?>

<style>
.gs-nav-bar{display:flex;gap:8px;margin-bottom:20px;flex-wrap:wrap}
.gs-nav-bar a{padding:7px 14px;background:#fff;border:1px solid var(--border);border-radius:7px;font-size:13px;font-weight:600;color:var(--muted);text-decoration:none;transition:all .15s}
.gs-nav-bar a:hover,.gs-nav-bar a.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.gs-status-badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600}
</style>

<div class="gs-nav-bar">
    <a href="<?= e($base) ?>/manage/store">Dashboard</a>
    <a href="<?= e($base) ?>/manage/store/products" class="active">Products</a>
    <a href="<?= e($base) ?>/manage/store/categories">Categories</a>
    <a href="<?= e($base) ?>/manage/store/orders">Orders</a>
    <a href="<?= e($base) ?>/manage/store/coupons">Coupons</a>
    <a href="<?= e($base) ?>/manage/store/settings">Settings</a>
</div>

<?php if ($success ?? null): ?>
<div class="alert alert-success" style="margin-bottom:16px"><?= e($success) ?></div>
<?php endif ?>

<!-- Filters -->
<div style="display:flex;gap:10px;margin-bottom:16px;flex-wrap:wrap">
    <form method="GET" style="display:flex;gap:8px;flex-wrap:wrap">
        <select name="status" class="form-select" style="width:auto" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <?php foreach (['published','draft','archived'] as $s): ?>
            <option value="<?= $s ?>" <?= ($status ?? '')===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
            <?php endforeach ?>
        </select>
        <select name="category" class="form-select" style="width:auto" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($cats ?? [] as $c): ?>
            <option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option>
            <?php endforeach ?>
        </select>
    </form>
    <div style="margin-left:auto;font-size:13px;color:var(--muted);align-self:center"><?= (int)($total??0) ?> products</div>
</div>

<div class="card" style="padding:0">
    <table style="width:100%;border-collapse:collapse">
        <thead><tr style="border-bottom:1px solid var(--border);font-size:12px;color:var(--muted);text-transform:uppercase">
            <th style="padding:12px 16px;text-align:left;width:48px"></th>
            <th style="padding:12px 16px;text-align:left">Product</th>
            <th style="padding:12px 16px;text-align:left">SKU</th>
            <th style="padding:12px 16px;text-align:right">Price</th>
            <th style="padding:12px 16px;text-align:center">Stock</th>
            <th style="padding:12px 16px;text-align:center">Status</th>
            <th style="padding:12px 16px;text-align:right">Actions</th>
        </tr></thead>
        <tbody>
        <?php if (empty($products)): ?>
        <tr><td colspan="7" style="padding:40px;text-align:center;color:var(--muted)">No products found. <a href="<?= e($base) ?>/manage/store/products/new">Create one</a></td></tr>
        <?php else: foreach ($products as $p):
            $imgs = json_decode((string)$p['images'],true) ?: [];
            $thumb = $imgs[0] ?? '';
            $statusColors = ['published'=>'#10b981','draft'=>'#f59e0b','archived'=>'#94a3b8'];
            $sc = $statusColors[$p['status']] ?? '#94a3b8';
        ?>
        <tr style="border-bottom:1px solid var(--border)">
            <td style="padding:10px 16px">
                <?php if ($thumb): ?>
                <img src="<?= e($thumb) ?>" style="width:40px;height:40px;object-fit:cover;border-radius:6px">
                <?php else: ?>
                <div style="width:40px;height:40px;background:var(--bg);border-radius:6px;display:flex;align-items:center;justify-content:center;color:var(--muted)">📦</div>
                <?php endif ?>
            </td>
            <td style="padding:10px 16px">
                <a href="<?= e($base) ?>/manage/store/products/<?= (int)$p['id'] ?>/edit" style="font-weight:600;color:var(--text)"><?= e($p['name']) ?></a>
                <div style="font-size:12px;color:var(--muted)"><?= e($p['type']) ?></div>
            </td>
            <td style="padding:10px 16px;color:var(--muted);font-size:13px"><?= e($p['sku'] ?: '–') ?></td>
            <td style="padding:10px 16px;text-align:right;font-weight:700">
                <?php if ($p['sale_price']): ?>
                <span style="text-decoration:line-through;color:var(--muted);font-weight:400;font-size:12px"><?= number_format((float)$p['price'],2) ?></span>
                <?= number_format((float)$p['sale_price'],2) ?>
                <?php else: ?>
                <?= number_format((float)$p['price'],2) ?>
                <?php endif ?>
            </td>
            <td style="padding:10px 16px;text-align:center;font-size:13px">
                <?php if (!$p['manage_stock']): ?>
                <span style="color:var(--muted)">∞</span>
                <?php elseif ((int)$p['stock'] <= 0): ?>
                <span style="color:var(--danger);font-weight:600">Out</span>
                <?php else: ?>
                <?= (int)$p['stock'] ?>
                <?php endif ?>
            </td>
            <td style="padding:10px 16px;text-align:center">
                <span class="gs-status-badge" style="background:<?= $sc ?>22;color:<?= $sc ?>"><?= ucfirst($p['status']) ?></span>
            </td>
            <td style="padding:10px 16px;text-align:right">
                <a href="<?= e($base) ?>/manage/store/products/<?= (int)$p['id'] ?>/edit" class="btn btn-ghost" style="font-size:12px;padding:5px 10px">Edit</a>
                <form method="POST" action="<?= e($base) ?>/manage/store/products/<?= (int)$p['id'] ?>/delete" style="display:inline" onsubmit="return confirm('Delete product?')">
                    <button class="btn btn-danger" style="font-size:12px;padding:5px 10px">✕</button>
                </form>
            </td>
        </tr>
        <?php endforeach; endif ?>
        </tbody>
    </table>
</div>

<?php if (($pages??1) > 1): ?>
<div style="display:flex;gap:6px;justify-content:center;margin-top:20px">
    <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a href="?page=<?= $i ?>&status=<?= e($status??'') ?>" style="padding:6px 12px;border-radius:6px;border:1px solid var(--border);font-size:13px;background:<?= $i===($page??1)?'var(--accent)':'#fff' ?>;color:<?= $i===($page??1)?'#fff':'var(--text)' ?>;text-decoration:none"><?= $i ?></a>
    <?php endfor ?>
</div>
<?php endif ?>
