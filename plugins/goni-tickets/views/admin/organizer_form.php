<?php
$isNew     = $organizer === null;
$pageTitle = $isNew ? 'New Organizer' : 'Edit: ' . e($organizer['name']);
$activeNav = 'tickets-organizers';
$topbarActions = '';
?>
<?php if (!empty($saved)): ?>
<div id="gc-flash" data-msg="Organizer saved." data-icon="success" style="display:none"></div>
<?php endif ?>

<div style="max-width:600px">
<form id="orgForm" method="POST" action="<?= e($base) ?>/manage/tickets/organizers/<?= $isNew ? 'new' : (int)$organizer['id'].'/edit' ?>">

    <!-- ── Details ────────────────────────────────────────────────────── -->
    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h3>Organizer Details</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:14px">

            <div class="form-group" style="margin:0">
                <label class="form-label">Name <span style="color:#ef4444">*</span></label>
                <input type="text" name="name" class="form-input"
                       value="<?= e((string)($organizer['name']??'')) ?>" required
                       placeholder="e.g. Batumi Culture Center">
            </div>

            <?php if ($isNew): ?>
            <div class="form-group" style="margin:0">
                <label class="form-label">Slug <span style="font-size:11px;color:var(--muted)">(auto-generated, used in URL)</span></label>
                <input type="text" name="slug" class="form-input" value="" placeholder="auto" style="color:var(--muted)">
            </div>
            <?php else: ?>
            <div class="form-group" style="margin:0">
                <label class="form-label">Slug <span style="font-size:11px;color:var(--muted)">(immutable)</span></label>
                <input type="text" class="form-input" value="<?= e($organizer['slug']) ?>" disabled style="opacity:.5">
            </div>
            <?php endif ?>

            <div class="form-group" style="margin:0">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="3" style="resize:vertical"
                          placeholder="Short bio or description"><?= e((string)($organizer['description']??'')) ?></textarea>
            </div>

            <div class="form-group" style="margin:0">
                <label class="form-label">Website</label>
                <input type="url" name="website" class="form-input"
                       value="<?= e((string)($organizer['website']??'')) ?>"
                       placeholder="https://example.com">
            </div>

            <div class="form-group" style="margin:0">
                <label class="form-label">Sort Order</label>
                <input type="number" name="sort_order" class="form-input"
                       value="<?= (int)($organizer['sort_order']??0) ?>" min="0">
            </div>
        </div>
    </div>

    <!-- ── Photos ─────────────────────────────────────────────────────── -->
    <div class="card" style="margin-bottom:16px">
        <div class="card-header"><h3>Photos</h3></div>
        <div class="card-body" style="display:flex;flex-direction:column;gap:20px">

            <!-- Profile / Logo -->
            <div class="form-group" style="margin:0">
                <label class="form-label">Profile Photo (Logo) URL</label>
                <input type="text" name="logo" id="logoInput" class="form-input"
                       value="<?= e((string)($organizer['logo']??'')) ?>"
                       placeholder="https://…" oninput="previewImg('logo',this.value)">
                <div id="logoPreview" style="margin-top:10px;<?= empty($organizer['logo']) ? 'display:none' : '' ?>">
                    <img id="logoImg" src="<?= e((string)($organizer['logo']??'')) ?>"
                         style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:2px solid var(--border)">
                </div>
            </div>

            <!-- Cover -->
            <div class="form-group" style="margin:0">
                <label class="form-label">Cover Photo URL <span style="font-size:11px;color:var(--muted)">(wide banner image)</span></label>
                <input type="text" name="cover" id="coverInput" class="form-input"
                       value="<?= e((string)($organizer['cover']??'')) ?>"
                       placeholder="https://…" oninput="previewImg('cover',this.value)">
                <div id="coverPreview" style="margin-top:10px;<?= empty($organizer['cover']) ? 'display:none' : '' ?>">
                    <img id="coverImg" src="<?= e((string)($organizer['cover']??'')) ?>"
                         style="width:100%;max-height:160px;object-fit:cover;border-radius:10px;border:1px solid var(--border)">
                </div>
            </div>

        </div>
    </div>

    <button type="submit" class="btn btn-primary" style="font-size:14px;padding:10px 28px">
        <?= $isNew ? 'Create Organizer' : 'Save Changes' ?>
    </button>
    <a href="<?= e($base) ?>/manage/tickets/organizers" class="btn btn-ghost" style="margin-left:8px;font-size:14px">Cancel</a>
</form>
</div>

<script>
function previewImg(type, url) {
    var preview = document.getElementById(type + 'Preview');
    var img     = document.getElementById(type + 'Img');
    if (!preview || !img) return;
    if (url) { img.src = url; preview.style.display = ''; }
    else      { preview.style.display = 'none'; }
}
</script>
