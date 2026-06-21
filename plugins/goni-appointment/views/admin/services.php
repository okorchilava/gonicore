<?php
$pageTitle     = 'Services';
$activeNav     = 'appointment';
$topbarActions = '<a href="' . e($base) . '/manage/appointment/services/new" class="btn btn-primary" style="font-size:13px">+ New Service</a>';
?>
<?php if ($deleted): ?>
<div id="gc-flash" data-msg="Service deleted." data-icon="success" style="display:none"></div>
<?php endif ?>

<div class="card">
    <div class="card-header"><h3>Services <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= count($services) ?>)</span></h3></div>
    <?php if (empty($services)): ?>
    <div class="empty">
        <div class="empty-icon">💆</div>
        <h3>No services yet</h3>
        <p><a href="<?= e($base) ?>/manage/appointment/services/new" class="btn btn-primary" style="margin-top:12px">Add your first service</a></p>
    </div>
    <?php else: ?>
    <table class="data-table"><thead><tr>
        <th>Service</th><th>Duration</th><th>Price</th><th>Status</th><th>Order</th><th></th>
    </tr></thead><tbody>
    <?php foreach ($services as $s): ?>
    <tr>
        <td>
            <div style="display:flex;align-items:center;gap:10px">
                <span style="display:inline-block;width:14px;height:14px;border-radius:50%;flex-shrink:0;background:<?= e($s['color']) ?>"></span>
                <div>
                    <div style="font-weight:700"><?= e($s['name']) ?></div>
                    <?php if ($s['description']): ?>
                    <div style="font-size:12px;color:var(--muted)"><?= e(mb_strimwidth((string)$s['description'], 0, 80, '…')) ?></div>
                    <?php endif ?>
                </div>
            </div>
        </td>
        <td style="font-size:13px"><?= (int)$s['duration_minutes'] ?> min</td>
        <td style="font-size:13px"><?= number_format((float)$s['price'], 2) ?></td>
        <td><?php
            $sc = ['active'=>'#10b981','inactive'=>'#94a3b8'][$s['status']] ?? '#94a3b8';
        ?><span style="background:<?= $sc ?>22;color:<?= $sc ?>;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase"><?= e($s['status']) ?></span></td>
        <td style="font-size:13px;color:var(--muted)"><?= (int)$s['sort_order'] ?></td>
        <td style="white-space:nowrap">
            <a href="<?= e($base) ?>/manage/appointment/services/<?= (int)$s['id'] ?>/edit" class="btn btn-ghost" style="font-size:12px">Edit</a>
            <form method="POST" action="<?= e($base) ?>/manage/appointment/services/<?= (int)$s['id'] ?>/delete" style="display:inline">
                <button type="button" class="btn btn-danger" style="font-size:12px"
                    onclick="gcConfirm(this,'Delete service?','All staff assignments for this service will also be removed.','Delete')">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php endif ?>
</div>
