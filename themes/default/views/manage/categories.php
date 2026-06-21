<?php
$pageTitle  = t('categories.title');
$activeNav  = 'categories';
$catList    = $cats ?? [];
$topbarActions = '';

// Build parent map
$catById = [];
foreach ($catList as $c) { $catById[(int)$c['id']] = $c; }
?>

<div style="display:grid;grid-template-columns:340px 1fr;gap:24px;align-items:start">

    <!-- Add category form -->
    <div class="card" style="position:sticky;top:80px">
        <div class="card-header"><h3 id="formTitle"><?= e(t('categories.new')) ?></h3></div>
        <div class="card-body" style="padding:18px">
            <form method="POST" id="catForm" action="<?= e($base) ?>/manage/categories/create">
                <input type="hidden" name="_edit_id" id="editId" value="">

                <div class="form-group">
                    <label class="form-label"><?= e(t('categories.name')) ?> *</label>
                    <input type="text" name="name" id="catName" class="form-input"
                           required oninput="previewSlug(this.value)">
                </div>

                <div class="form-group">
                    <label class="form-label"><?= e(t('categories.slug')) ?></label>
                    <div id="slugPreview" style="font-family:monospace;font-size:12px;color:var(--muted);padding:4px 0"></div>
                </div>

                <div class="form-group">
                    <label class="form-label"><?= e(t('categories.parent')) ?></label>
                    <select name="parent_id" id="catParent" class="form-select">
                        <option value=""><?= e(t('categories.no_parent')) ?></option>
                        <?php foreach ($catList as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= e((string)$c['name']) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div style="display:flex;gap:8px">
                    <button type="submit" class="btn btn-primary" style="flex:1;justify-content:center">
                        <span id="formBtnLabel"><?= e(t('categories.new')) ?></span>
                    </button>
                    <button type="button" class="btn btn-ghost" id="cancelEdit" style="display:none" onclick="resetForm()"><?= e(t('admin.cancel')) ?></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Category list -->
    <div class="card">
        <div class="card-header">
            <h3><?= count($catList) ?> <?= e(t('categories.title')) ?></h3>
            <input type="text" id="catSearch" placeholder="<?= e(t('admin.search')) ?>…"
                   style="padding:6px 12px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;outline:none;width:190px"
                   oninput="filterCats()">
        </div>
        <?php if (empty($catList)): ?>
        <div class="empty" style="padding:40px">
            <div class="empty-icon"><span class="material-symbols-outlined" style="font-size:36px">folder</span></div>
            <h3><?= e(t('categories.no_categories')) ?></h3>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table id="catsTable">
                <thead>
                    <tr>
                        <th style="width:40%"><?= e(t('categories.name')) ?></th>
                        <th><?= e(t('categories.slug')) ?></th>
                        <th><?= e(t('categories.parent')) ?></th>
                        <th style="text-align:center"><?= e(t('categories.posts_count')) ?></th>
                        <th style="text-align:right"><?= e(t('admin.actions')) ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($catList as $c):
                        $parent    = $c['parent_id'] ? ($catById[(int)$c['parent_id']] ?? null) : null;
                        $postCount = $postCounts[(int)$c['id']] ?? 0;
                    ?>
                    <tr data-search="<?= e(strtolower($c['name'].' '.($c['slug']??''))) ?>">
                        <td>
                            <div style="display:flex;align-items:center;gap:8px">
                                <?php if ($parent): ?>
                                <span style="color:var(--muted);font-size:13px">└</span>
                                <?php endif ?>
                                <span style="font-weight:600"><?= e((string)$c['name']) ?></span>
                            </div>
                        </td>
                        <td style="font-family:monospace;font-size:12px;color:var(--muted)"><?= e((string)($c['slug'] ?? '')) ?></td>
                        <td style="font-size:13px;color:var(--muted)"><?= $parent ? e((string)$parent['name']) : '<span style="opacity:.4">—</span>' ?></td>
                        <td style="text-align:center">
                            <span style="font-size:13px;<?= $postCount > 0 ? 'font-weight:600;color:var(--text)' : 'color:var(--muted)' ?>">
                                <?= $postCount ?>
                            </span>
                        </td>
                        <td style="text-align:right;white-space:nowrap">
                            <button type="button" class="btn btn-ghost" style="font-size:11px;padding:3px 10px;margin-right:4px"
                                onclick="editCat(<?= (int)$c['id'] ?>, <?= htmlspecialchars(json_encode($c), ENT_QUOTES) ?>)"><?= e(t('admin.edit')) ?></button>
                            <form method="POST" action="<?= e($base) ?>/manage/categories/<?= (int)$c['id'] ?>/delete" style="display:inline">
                                <button type="button" class="btn btn-danger" style="font-size:11px;padding:3px 10px"
                                    onclick="gcConfirm(this, <?= e(json_encode(t('categories.confirm_delete'), JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode('«' . $c['name'] . '» — ' . t('admin.cannot_undo'), JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode(t('admin.yes_delete'), JSON_UNESCAPED_UNICODE)) ?>)"><?= e(t('admin.delete')) ?></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
        <?php endif ?>
    </div>
</div>

<script>
function slugify(s) {
    return s.toLowerCase().replace(/[^\w\s-]/g,'').replace(/[\s_-]+/g,'-').replace(/^-+|-+$/g,'');
}
function previewSlug(v) {
    document.getElementById('slugPreview').textContent = slugify(v) || '—';
}

var catI18n = {
    editTitle: <?= json_encode(t('categories.title') . ' — ' . t('admin.edit'), JSON_UNESCAPED_UNICODE) ?>,
    saveLabel: <?= json_encode(t('admin.save'), JSON_UNESCAPED_UNICODE) ?>,
    addTitle:  <?= json_encode(t('categories.new'), JSON_UNESCAPED_UNICODE) ?>
};

function editCat(id, data) {
    document.getElementById('formTitle').textContent   = catI18n.editTitle;
    document.getElementById('formBtnLabel').textContent = catI18n.saveLabel;
    document.getElementById('cancelEdit').style.display = '';
    document.getElementById('editId').value            = id;
    document.getElementById('catForm').action          = '<?= e($base) ?>/manage/categories/' + id + '/update';
    document.getElementById('catName').value           = data.name   || '';
    document.getElementById('slugPreview').textContent = data.slug   || '';
    var sel = document.getElementById('catParent');
    sel.value = data.parent_id || '';
    window.scrollTo({top: 0, behavior: 'smooth'});
}

function resetForm() {
    document.getElementById('formTitle').textContent    = catI18n.addTitle;
    document.getElementById('formBtnLabel').textContent  = catI18n.addTitle;
    document.getElementById('cancelEdit').style.display = 'none';
    document.getElementById('editId').value             = '';
    document.getElementById('catForm').action           = '<?= e($base) ?>/manage/categories/create';
    document.getElementById('catForm').reset();
    document.getElementById('slugPreview').textContent  = '';
}

function filterCats() {
    var q = document.getElementById('catSearch').value.toLowerCase();
    document.querySelectorAll('#catsTable tbody tr').forEach(function(row) {
        row.style.display = (!q || (row.dataset.search || '').includes(q)) ? '' : 'none';
    });
}
</script>
