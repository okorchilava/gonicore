<?php
$isEdit        = $member !== null;
$pageTitle     = $isEdit ? 'Edit Staff: ' . $member['name'] : 'New Staff Member';
$activeNav     = 'appointment';
$topbarActions = '<a href="' . e($base) . '/manage/appointment/staff" class="btn btn-ghost" style="font-size:13px">← Back to Staff</a>';
?>
<?php if (!empty($saved)): ?>
<div id="gc-flash" data-msg="Staff member saved." data-icon="success" style="display:none"></div>
<?php endif ?>

<form method="POST" action="<?= e($base) ?>/manage/appointment/staff/<?= $isEdit ? (int)$member['id'].'/edit' : 'new' ?>">
<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">

<div class="card">
    <div class="card-header"><h3><?= $isEdit ? 'Edit Staff Member' : 'New Staff Member' ?></h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:16px">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div>
                <label class="form-label">Name <span style="color:#ef4444">*</span></label>
                <input type="text" name="name" class="form-input" required value="<?= e($member['name'] ?? '') ?>" placeholder="Full name">
            </div>
            <div>
                <label class="form-label">Title / Role</label>
                <input type="text" name="title" class="form-input" value="<?= e($member['title'] ?? '') ?>" placeholder="e.g. Senior Stylist">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div>
                <label class="form-label">Email</label>
                <input type="email" name="email" class="form-input" value="<?= e($member['email'] ?? '') ?>">
            </div>
            <div>
                <label class="form-label">Phone</label>
                <input type="text" name="phone" class="form-input" value="<?= e($member['phone'] ?? '') ?>">
            </div>
        </div>
        <div>
            <label class="form-label">Bio</label>
            <textarea name="bio" class="form-input" rows="4" placeholder="Short bio visible to customers…"><?= e($member['bio'] ?? '') ?></textarea>
        </div>
        <div>
            <label class="form-label">Photo URL</label>
            <input type="url" name="image" class="form-input" value="<?= e($member['image'] ?? '') ?>" placeholder="https://…">
            <?php if (!empty($member['image'])): ?>
            <div style="margin-top:8px"><img src="<?= e($member['image']) ?>" alt="" style="width:56px;height:56px;border-radius:50%;object-fit:cover;border:2px solid var(--border)"></div>
            <?php endif ?>
        </div>
        <div>
            <label class="form-label">Sort Order</label>
            <input type="number" name="sort_order" class="form-input" min="0" value="<?= (int)($member['sort_order'] ?? 0) ?>" style="max-width:120px">
        </div>
    </div>
</div>

<div style="display:flex;flex-direction:column;gap:16px">
    <div class="card">
        <div class="card-header"><h3>Status</h3></div>
        <div class="card-body">
            <select name="status" class="form-input">
                <option value="active" <?= ($member['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($member['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
    </div>

    <?php if (!empty($services)): ?>
    <div class="card">
        <div class="card-header"><h3>Assigned Services</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:8px">
            <?php foreach ($services as $svc): ?>
            <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:14px;padding:4px 0">
                <input type="checkbox" name="service_ids[]" value="<?= (int)$svc['id'] ?>"
                    <?= in_array((int)$svc['id'], $assignedIds, true) ? 'checked' : '' ?>
                    style="width:16px;height:16px">
                <span style="display:inline-block;width:10px;height:10px;border-radius:50%;flex-shrink:0;background:<?= e($svc['color']) ?>"></span>
                <span><?= e($svc['name']) ?></span>
                <span style="font-size:12px;color:var(--muted)"><?= (int)$svc['duration_minutes'] ?>min</span>
            </label>
            <?php endforeach ?>
        </div>
    </div>
    <?php else: ?>
    <div class="card">
        <div class="card-body" style="font-size:13px;color:var(--muted)">
            <a href="<?= e($base) ?>/manage/appointment/services/new">Add services</a> first to assign them here.
        </div>
    </div>
    <?php endif ?>

    <button type="submit" class="btn btn-primary" style="width:100%"><?= $isEdit ? 'Save Changes' : 'Create Staff Member' ?></button>
    <?php if ($isEdit): ?>
    <a href="<?= e($base) ?>/manage/appointment/staff/<?= (int)$member['id'] ?>/schedule" class="btn" style="width:100%;text-align:center;background:var(--surface);border:1px solid var(--border)">📅 Edit Schedule</a>
    <?php endif ?>
</div>

</div>
</form>
