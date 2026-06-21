<?php
$pageTitle  = t('roles.edit_title');
$activeNav  = 'roles';
$r          = $role ?? [];
$grouped    = $groupedPerms ?? [];
$assigned   = array_map('intval', $assigned ?? []);
$canManage  = $canManage ?? false;
$isSystem   = (int)($r['is_system'] ?? 0) === 1;
$topbarActions = '';
?>

<form method="POST" action="<?= e($base) ?>/manage/roles/<?= (int)($r['id'] ?? 0) ?>">

    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
        <a href="<?= e($base) ?>/manage/roles" class="btn btn-ghost" style="font-size:13px">← <?= e(t('roles.back')) ?></a>
        <?php if ($canManage): ?>
        <button type="submit" class="btn btn-primary"><?= e(t('admin.save')) ?></button>
        <?php endif ?>
    </div>

    <!-- Role details -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3><?= e((string)($r['label'] ?? '')) ?></h3></div>
        <div class="card-body" style="padding:18px;display:grid;grid-template-columns:1fr 1fr;gap:16px">
            <div class="form-group" style="margin:0">
                <label class="form-label"><?= e(t('roles.label')) ?></label>
                <input type="text" name="label" class="form-input" value="<?= e((string)($r['label'] ?? '')) ?>" <?= $canManage ? '' : 'disabled' ?>>
            </div>
            <div class="form-group" style="margin:0">
                <label class="form-label"><?= e(t('roles.name')) ?></label>
                <input type="text" class="form-input" value="<?= e((string)($r['name'] ?? '')) ?>" disabled
                       style="font-family:monospace;background:var(--border)">
            </div>
            <div class="form-group" style="margin:0;grid-column:1/-1">
                <label class="form-label"><?= e(t('roles.description')) ?></label>
                <input type="text" name="description" class="form-input" value="<?= e((string)($r['description'] ?? '')) ?>" <?= $canManage ? '' : 'disabled' ?>>
            </div>
        </div>
    </div>

    <!-- Permissions -->
    <div class="card">
        <div class="card-header">
            <h3><?= e(t('roles.permissions')) ?></h3>
            <?php if ($canManage): ?>
            <div style="display:flex;gap:8px">
                <button type="button" class="btn btn-ghost" style="font-size:11px;padding:3px 10px" onclick="permsAll(true)"><?= e(t('perms.select_all')) ?></button>
                <button type="button" class="btn btn-ghost" style="font-size:11px;padding:3px 10px" onclick="permsAll(false)"><?= e(t('perms.clear_all')) ?></button>
            </div>
            <?php endif ?>
        </div>
        <div class="card-body" style="padding:18px">
            <?php foreach ($grouped as $group => $perms): ?>
            <div style="margin-bottom:18px">
                <div style="font-size:12px;font-weight:700;text-transform:uppercase;letter-spacing:.04em;color:var(--muted);margin-bottom:8px;border-bottom:1px solid var(--border);padding-bottom:4px">
                    <?= e(t('perms.group.' . $group)) ?>
                </div>
                <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:8px">
                    <?php foreach ($perms as $p):
                        $pid     = (int)$p['id'];
                        $checked = in_array($pid, $assigned, true);
                    ?>
                    <label style="display:flex;align-items:center;gap:8px;font-size:13px;cursor:<?= $canManage ? 'pointer' : 'default' ?>">
                        <input type="checkbox" name="permissions[]" value="<?= $pid ?>" <?= $checked ? 'checked' : '' ?> <?= $canManage ? '' : 'disabled' ?>>
                        <span><?= e((string)$p['label']) ?></span>
                        <code style="font-size:10px;color:var(--muted)"><?= e((string)$p['name']) ?></code>
                    </label>
                    <?php endforeach ?>
                </div>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</form>

<script>
function permsAll(state) {
    document.querySelectorAll('input[name="permissions[]"]:not(:disabled)').forEach(function (c) { c.checked = state; });
}
</script>
