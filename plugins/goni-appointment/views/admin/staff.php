<?php
$pageTitle     = 'Staff';
$activeNav     = 'appointment';
$topbarActions = '<a href="' . e($base) . '/manage/appointment/staff/new" class="btn btn-primary" style="font-size:13px">+ New Staff Member</a>';
?>
<?php if ($deleted): ?>
<div id="gc-flash" data-msg="Staff member deleted." data-icon="success" style="display:none"></div>
<?php endif ?>

<div class="card">
    <div class="card-header"><h3>Staff Members <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= count($staff) ?>)</span></h3></div>
    <?php if (empty($staff)): ?>
    <div class="empty">
        <div class="empty-icon">👤</div>
        <h3>No staff members yet</h3>
        <p><a href="<?= e($base) ?>/manage/appointment/staff/new" class="btn btn-primary" style="margin-top:12px">Add first staff member</a></p>
    </div>
    <?php else: ?>
    <table class="data-table"><thead><tr>
        <th>Member</th><th>Title</th><th>Contact</th><th>Status</th><th></th>
    </tr></thead><tbody>
    <?php foreach ($staff as $s): ?>
    <tr>
        <td>
            <div style="display:flex;align-items:center;gap:12px">
                <?php if ($s['image']): ?>
                <img src="<?= e($s['image']) ?>" alt="" style="width:38px;height:38px;border-radius:50%;object-fit:cover;flex-shrink:0">
                <?php else: ?>
                <div style="width:38px;height:38px;border-radius:50%;background:var(--surface);display:flex;align-items:center;justify-content:center;font-size:18px;flex-shrink:0">👤</div>
                <?php endif ?>
                <div>
                    <div style="font-weight:700"><?= e($s['name']) ?></div>
                </div>
            </div>
        </td>
        <td style="font-size:13px;color:var(--muted)"><?= e($s['title'] ?: '—') ?></td>
        <td style="font-size:13px">
            <?php if ($s['email']): ?>
            <div><?= e($s['email']) ?></div>
            <?php endif ?>
            <?php if ($s['phone']): ?>
            <div style="color:var(--muted)"><?= e($s['phone']) ?></div>
            <?php endif ?>
            <?php if (!$s['email'] && !$s['phone']): ?>—<?php endif ?>
        </td>
        <td><?php
            $sc = ['active'=>'#10b981','inactive'=>'#94a3b8'][$s['status']] ?? '#94a3b8';
        ?><span style="background:<?= $sc ?>22;color:<?= $sc ?>;font-size:11px;font-weight:700;padding:2px 8px;border-radius:20px;text-transform:uppercase"><?= e($s['status']) ?></span></td>
        <td style="white-space:nowrap">
            <a href="<?= e($base) ?>/manage/appointment/staff/<?= (int)$s['id'] ?>/edit" class="btn btn-ghost" style="font-size:12px">Edit</a>
            <a href="<?= e($base) ?>/manage/appointment/staff/<?= (int)$s['id'] ?>/schedule" class="btn btn-ghost" style="font-size:12px">📅 Schedule</a>
            <form method="POST" action="<?= e($base) ?>/manage/appointment/staff/<?= (int)$s['id'] ?>/delete" style="display:inline">
                <button type="button" class="btn btn-danger" style="font-size:12px"
                    onclick="gcConfirm(this,'Delete staff member?','Their appointment history will remain.','Delete')">Delete</button>
            </form>
        </td>
    </tr>
    <?php endforeach ?>
    </tbody></table>
    <?php endif ?>
</div>
