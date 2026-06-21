<?php
$isEdit        = $service !== null;
$pageTitle     = $isEdit ? 'Edit Service' : 'New Service';
$activeNav     = 'appointment';
$topbarActions = '<a href="' . e($base) . '/manage/appointment/services" class="btn btn-ghost" style="font-size:13px">← Back to Services</a>';
?>
<?php if (!empty($saved)): ?>
<div id="gc-flash" data-msg="Service saved." data-icon="success" style="display:none"></div>
<?php endif ?>

<form method="POST" action="<?= e($base) ?>/manage/appointment/services/<?= $isEdit ? (int)$service['id'].'/edit' : 'new' ?>">
<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start">

<div class="card">
    <div class="card-header"><h3><?= $isEdit ? 'Edit Service' : 'New Service' ?></h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:16px">
        <div>
            <label class="form-label">Name <span style="color:#ef4444">*</span></label>
            <input type="text" name="name" class="form-input" required value="<?= e($service['name'] ?? '') ?>" placeholder="e.g. Haircut & Style">
        </div>
        <div>
            <label class="form-label">Description</label>
            <textarea name="description" class="form-input" rows="3" placeholder="Short description of this service…"><?= e($service['description'] ?? '') ?></textarea>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div>
                <label class="form-label">Duration (minutes)</label>
                <input type="number" name="duration_minutes" class="form-input" min="15" step="15" value="<?= (int)($service['duration_minutes'] ?? 60) ?>">
            </div>
            <div>
                <label class="form-label">Price</label>
                <input type="number" name="price" class="form-input" min="0" step="0.01" value="<?= number_format((float)($service['price'] ?? 0), 2, '.', '') ?>">
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div>
                <label class="form-label">Color</label>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="color" name="color" id="colorPicker" value="<?= e($service['color'] ?? '#4f46e5') ?>"
                           style="width:48px;height:36px;border:1px solid var(--border);border-radius:8px;cursor:pointer;padding:2px">
                    <input type="text" id="colorHex" class="form-input" value="<?= e($service['color'] ?? '#4f46e5') ?>"
                           style="font-family:monospace" maxlength="7">
                </div>
            </div>
            <div>
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-input" min="0" value="<?= (int)($service['sort_order'] ?? 0) ?>">
            </div>
        </div>
        <div>
            <label class="form-label">Image URL</label>
            <input type="url" name="image" class="form-input" value="<?= e($service['image'] ?? '') ?>" placeholder="https://…">
        </div>
    </div>
</div>

<div style="display:flex;flex-direction:column;gap:16px">
    <div class="card">
        <div class="card-header"><h3>Status</h3></div>
        <div class="card-body">
            <select name="status" class="form-input">
                <option value="active" <?= ($service['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                <option value="inactive" <?= ($service['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
            </select>
        </div>
    </div>
    <button type="submit" class="btn btn-primary" style="width:100%"><?= $isEdit ? 'Save Changes' : 'Create Service' ?></button>
    <?php if ($isEdit): ?>
    <a href="<?= e($base) ?>/manage/appointment/services" class="btn" style="width:100%;text-align:center;background:var(--surface);border:1px solid var(--border)">← Back to List</a>
    <?php endif ?>
</div>

</div>
</form>

<script>
var cp = document.getElementById('colorPicker');
var ch = document.getElementById('colorHex');
cp.addEventListener('input', function(){ ch.value = this.value; });
ch.addEventListener('change', function(){ cp.value = this.value; });
</script>
