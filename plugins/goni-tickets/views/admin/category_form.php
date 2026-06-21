<?php
$isNew     = $category === null;
$pageTitle = $isNew ? 'New Category' : 'Edit: ' . e($category['label']);
$activeNav = 'tickets-categories';
$topbarActions = '';
?>
<?php if (!empty($saved)): ?>
<div id="gc-flash" data-msg="Category saved." data-icon="success" style="display:none"></div>
<?php endif ?>

<div style="max-width:560px">
<form id="catForm" method="POST" action="<?= e($base) ?>/manage/tickets/categories/<?= $isNew ? 'new' : (int)$category['id'].'/edit' ?>">

    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h3>Category Details</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:14px">

            <div style="display:grid;grid-template-columns:64px 1fr;gap:14px;align-items:end">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Icon</label>
                    <input type="text" name="icon" class="form-input" value="<?= e((string)($category['icon']??'🎟')) ?>"
                           style="font-size:24px;text-align:center;padding:8px" maxlength="4">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Label <span style="color:#ef4444">*</span></label>
                    <input type="text" name="label" class="form-input" value="<?= e((string)($category['label']??'')) ?>" required placeholder="e.g. კონცერტი">
                </div>
            </div>

            <?php if ($isNew): ?>
            <div class="form-group" style="margin:0">
                <label class="form-label">Slug <span style="font-size:11px;color:var(--muted)">(auto-generated, immutable)</span></label>
                <input type="text" name="slug" class="form-input" value="" placeholder="auto" style="color:var(--muted)">
            </div>
            <?php else: ?>
            <div class="form-group" style="margin:0">
                <label class="form-label">Slug</label>
                <input type="text" class="form-input" value="<?= e($category['slug']) ?>" disabled style="opacity:.5">
            </div>
            <?php endif ?>

            <div class="form-group" style="margin:0">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-input" value="<?= (int)($category['sort_order']??0) ?>" min="0">
            </div>
        </div>
    </div>

    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h3>Colors</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:14px">

            <div class="form-group" style="margin:0">
                <label class="form-label">Accent Color <span style="font-size:11px;color:var(--muted)">(text, highlights)</span></label>
                <div style="display:flex;gap:8px;align-items:center">
                    <input type="color" name="accent" value="<?= e((string)($category['accent']??'#a78bfa')) ?>"
                           style="width:44px;height:36px;border:none;background:none;cursor:pointer;padding:0">
                    <input type="text" id="accentHex" class="form-input" value="<?= e((string)($category['accent']??'#a78bfa')) ?>"
                           style="font-family:monospace;max-width:110px" maxlength="7"
                           oninput="syncColor('accent',this.value)">
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Gradient From</label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="color" name="grad_from" value="<?= e((string)($category['grad_from']??'#0a0812')) ?>"
                               style="width:44px;height:36px;border:none;background:none;cursor:pointer;padding:0">
                        <input type="text" class="form-input" value="<?= e((string)($category['grad_from']??'#0a0812')) ?>"
                               style="font-family:monospace;flex:1" maxlength="7"
                               oninput="document.querySelector('[name=grad_from]').value=this.value;updatePreview()">
                    </div>
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label">Gradient To</label>
                    <div style="display:flex;gap:8px;align-items:center">
                        <input type="color" name="grad_to" value="<?= e((string)($category['grad_to']??'#4c1d95')) ?>"
                               style="width:44px;height:36px;border:none;background:none;cursor:pointer;padding:0">
                        <input type="text" class="form-input" value="<?= e((string)($category['grad_to']??'#4c1d95')) ?>"
                               style="font-family:monospace;flex:1" maxlength="7"
                               oninput="document.querySelector('[name=grad_to]').value=this.value;updatePreview()">
                    </div>
                </div>
            </div>

            <!-- Preview -->
            <div>
                <label class="form-label">Preview</label>
                <div id="colorPreview" style="height:80px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:32px;
                     background:linear-gradient(135deg,<?= e((string)($category['grad_from']??'#0a0812')) ?>,<?= e((string)($category['grad_to']??'#4c1d95')) ?>)">
                    <span id="previewIcon"><?= e((string)($category['icon']??'🎟')) ?></span>
                </div>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="font-size:14px;padding:10px 28px">
        <?= $isNew ? 'Create Category' : 'Save Changes' ?>
    </button>
    <a href="<?= e($base) ?>/manage/tickets/categories" class="btn btn-ghost" style="margin-left:8px;font-size:14px">Cancel</a>
</form>
</div>

<script>
function syncColor(field, val) {
    document.querySelector('[name=' + field + ']').value = val;
    updatePreview();
}
function updatePreview() {
    var gf = document.querySelector('[name=grad_from]').value;
    var gt = document.querySelector('[name=grad_to]').value;
    document.getElementById('colorPreview').style.background = 'linear-gradient(135deg,' + gf + ',' + gt + ')';
    document.getElementById('previewIcon').textContent = document.querySelector('[name=icon]').value || '🎟';
}
document.querySelector('[name=icon]').addEventListener('input', updatePreview);
document.querySelector('[name=accent]').addEventListener('input', function(){
    document.getElementById('accentHex').value = this.value;
});
document.querySelector('[name=grad_from]').addEventListener('input', function(){
    this.nextElementSibling.value = this.value; updatePreview();
});
document.querySelector('[name=grad_to]').addEventListener('input', function(){
    this.nextElementSibling.value = this.value; updatePreview();
});
</script>
