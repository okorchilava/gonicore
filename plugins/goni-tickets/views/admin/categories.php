<?php
$pageTitle     = 'Categories';
$activeNav     = 'tickets-categories';
$topbarActions = '<a href="' . e($base) . '/manage/tickets/categories/new" class="btn btn-primary" style="font-size:13px">+ New Category</a>';
?>
<?php if (!empty($saved)): ?>
<div id="gc-flash" data-msg="Category saved." data-icon="success" style="display:none"></div>
<?php endif ?>
<?php if (!empty($deleted)): ?>
<div id="gc-flash" data-msg="Category deleted." data-icon="success" style="display:none"></div>
<?php endif ?>

<div class="card">
    <div class="card-header"><h3>All Categories <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= count($categories) ?>)</span></h3></div>
    <?php if (empty($categories)): ?>
    <div class="empty"><div class="empty-icon">🏷</div><h3>No categories yet</h3></div>
    <?php else: ?>
    <table class="data-table"><thead><tr>
        <th style="width:48px"></th><th>Label</th><th>Slug</th><th>Colors</th><th>Order</th><th></th>
    </tr></thead><tbody>
    <?php foreach ($categories as $cat): ?>
    <tr>
        <td style="font-size:24px;text-align:center"><?= e($cat['icon']) ?></td>
        <td><strong><?= e($cat['label']) ?></strong></td>
        <td><code style="font-size:12px;color:var(--muted)"><?= e($cat['slug']) ?></code></td>
        <td>
            <div style="display:flex;align-items:center;gap:8px">
                <span style="display:inline-block;width:18px;height:18px;border-radius:50%;background:<?= e($cat['accent']) ?>;border:1px solid rgba(0,0,0,.1)"></span>
                <span style="display:inline-block;width:40px;height:18px;border-radius:4px;background:linear-gradient(90deg,<?= e($cat['grad_from']) ?>,<?= e($cat['grad_to']) ?>);border:1px solid rgba(0,0,0,.1)"></span>
            </div>
        </td>
        <td style="color:var(--muted);font-size:13px"><?= (int)$cat['sort_order'] ?></td>
        <td style="white-space:nowrap">
            <a href="<?= e($base) ?>/manage/tickets/categories/<?= (int)$cat['id'] ?>/edit" class="btn btn-ghost" style="font-size:12px">Edit</a>
            <form method="POST" action="<?= e($base) ?>/manage/tickets/categories/<?= (int)$cat['id'] ?>/delete" style="display:inline">
                <button type="button" class="btn btn-danger" style="font-size:12px"
                    onclick="gcConfirm(this,'Delete category?','Events using this category will show as Other.','Delete')">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php endif ?>
</div>
