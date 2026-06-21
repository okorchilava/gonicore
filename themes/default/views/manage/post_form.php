<?php
$isEdit    = !empty($post);
$pageTitle = $isEdit ? t('posts.edit') : t('posts.new');
$activeNav = 'posts';
$postType  = 'post';
ob_start(); ?>
<a href="<?= e($base) ?>/manage/posts" class="topbar-btn ghost">← <?= e(t('posts.title')) ?></a>
<?php if ($isEdit): ?>
<a href="<?= e($base) ?>/post/<?= e($post['slug'] ?? '') ?>" target="_blank" class="topbar-btn ghost" style="margin-left:4px">↗ View</a>
<?php endif ?>
<?php $topbarActions = ob_get_clean();

$formAction = $isEdit
    ? e($base) . '/manage/posts/' . (int)$post['id']
    : e($base) . '/manage/posts';
?>

<style>
.post-editor-wrap { display: grid; grid-template-columns: 1fr 320px; gap: 20px; align-items: start; }
.post-editor-main { min-width: 0; }
.post-editor-side { display: flex; flex-direction: column; gap: 14px; }
.slug-preview { font-size: 12px; color: var(--muted); margin-top: 5px; word-break: break-all; }
.slug-preview span { color: var(--accent); }
@media (max-width: 900px) {
    .post-editor-wrap { grid-template-columns: 1fr; }
    .post-editor-side { order: -1; }
}
</style>

<form method="POST" action="<?= $formAction ?>" id="postForm">

<div class="post-editor-wrap">

    <!-- Main editor -->
    <div class="post-editor-main">

        <!-- Title -->
        <div style="margin-bottom:14px">
            <input type="text" name="title" id="postTitle" class="form-input"
                   value="<?= e((string)($post['title'] ?? '')) ?>"
                   placeholder="<?= e(t('posts.post_title')) ?>"
                   style="font-size:22px;font-weight:700;padding:14px 16px;border-radius:10px"
                   oninput="autoSlug(this.value)" required>
        </div>

        <!-- Slug -->
        <div style="margin-bottom:14px">
            <div style="display:flex;align-items:center;gap:8px">
                <span style="font-size:12px;color:var(--muted);white-space:nowrap">Slug:</span>
                <input type="text" name="slug" id="postSlug" class="form-input"
                       value="<?= e((string)($post['slug'] ?? '')) ?>"
                       placeholder="auto-generated"
                       style="font-size:13px;padding:6px 10px;font-family:monospace;flex:1"
                       oninput="updateSlugPreview()">
                <?php if ($isEdit && !empty($post['slug'])): ?>
                <button type="button" id="slugEditBtn" title="Edit slug"
                        onclick="enableSlugEdit()"
                        style="padding:5px 10px;border:1.5px solid var(--border);border-radius:6px;background:var(--bg);color:var(--muted);font-size:12px;cursor:pointer;white-space:nowrap;flex-shrink:0;display:inline-flex;align-items:center;gap:4px">
                    <span class="material-symbols-outlined mi-sm">edit</span> <?= e(t('admin.edit')) ?>
                </button>
                <?php endif ?>
            </div>
            <div class="slug-preview" id="slugPreview">
                <?= e($base) ?>/post/<span id="slugVal"><?= e((string)($post['slug'] ?? '')) ?></span>
            </div>
        </div>

        <!-- Rich text editor -->
        <?php
        $editorName   = 'content';
        $editorId     = 'postContent';
        $editorValue  = (string)($post['content'] ?? '');
        $editorHeight = '460px';
        include __DIR__ . '/_editor.php';
        ?>

        <!-- Excerpt -->
        <div class="card" style="margin-top:14px">
            <div class="card-header"><h3><?= e(t('posts.excerpt')) ?></h3></div>
            <div class="card-body">
                <textarea name="excerpt" class="form-textarea" style="min-height:80px;resize:vertical"
                          placeholder="Brief summary shown in post listings. Leave empty to auto-generate."><?= e((string)($post['excerpt'] ?? '')) ?></textarea>
            </div>
        </div>

    </div>

    <!-- Sidebar -->
    <div class="post-editor-side">

        <!-- Publish -->
        <div class="card">
            <div class="card-header"><h3><?= e(t('admin.status')) ?></h3></div>
            <div class="card-body" style="padding:16px">
                <div style="margin-bottom:14px">
                    <label class="form-label" style="margin-bottom:8px"><?= e(t('admin.status')) ?></label>
                    <?php foreach (['draft'=>[t('posts.draft'),'#f59e0b'],'published'=>[t('posts.published'),'#10b981'],'archived'=>[t('posts.archived'),'#94a3b8']] as $val => [$label, $color]): ?>
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer;font-size:13.5px">
                        <input type="radio" name="status" value="<?= $val ?>"
                               <?= (($post['status'] ?? 'draft') === $val) ? 'checked' : '' ?>
                               style="accent-color:<?= $color ?>">
                        <span style="display:flex;align-items:center;gap:5px">
                            <span style="width:8px;height:8px;border-radius:50%;background:<?= $color ?>;display:inline-block"></span>
                            <?= e($label) ?>
                        </span>
                    </label>
                    <?php endforeach ?>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;font-size:14px;justify-content:center">
                    <?= e(t('admin.save')) ?>
                </button>
                <?php if ($isEdit): ?>
                <div style="text-align:center;margin-top:10px;font-size:12px;color:var(--muted)">
                    Updated <?= e(fmt_date((string)($post['updated_at'] ?? ''))) ?>
                </div>
                <?php endif ?>
            </div>
        </div>

        <!-- Category (Select2) -->
        <div class="card">
            <div class="card-header"><h3><?= e(t('posts.category')) ?></h3></div>
            <div class="card-body" style="padding:16px">
                <select name="category_id" id="catSelect" style="width:100%">
                    <option value=""><?= e(t('posts.select_category')) ?></option>
                    <?php foreach ($cats ?? [] as $c): ?>
                    <option value="<?= (int)$c['id'] ?>"
                        <?= ((int)($post['category_id'] ?? 0) === (int)$c['id']) ? 'selected' : '' ?>>
                        <?= e($c['name']) ?>
                    </option>
                    <?php endforeach ?>
                </select>
                <a href="<?= e($base) ?>/manage/categories" style="font-size:11px;color:var(--accent);margin-top:6px;display:block">
                    + Manage categories
                </a>
            </div>
        </div>

        <!-- Featured Image -->
        <div class="card">
            <div class="card-header">
                <h3><?= e(t('posts.featured_image')) ?></h3>
                <?php if (!empty($post['featured_image'])): ?>
                <button type="button" class="btn btn-ghost" style="font-size:11px;padding:2px 8px"
                    onclick="removeFeatured()"><?= e(t('admin.delete')) ?></button>
                <?php endif ?>
            </div>
            <div class="card-body" style="padding:12px">
                <input type="hidden" name="featured_image" id="featuredImageUrl"
                       value="<?= e((string)($post['featured_image'] ?? '')) ?>">
                <div id="featuredPreview" style="margin-bottom:10px;<?= empty($post['featured_image']) ? 'display:none' : '' ?>">
                    <img id="featuredImg"
                         src="<?= e((string)($post['featured_image'] ?? '')) ?>"
                         style="width:100%;border-radius:8px;border:1px solid var(--border)">
                </div>
                <button type="button" class="btn btn-ghost" style="width:100%;justify-content:center;font-size:12.5px"
                    onclick="gcEd.openGallery('__featured__','<?= e($base) ?>')">
                    <span class="material-symbols-outlined mi-sm">image</span> <?= empty($post['featured_image']) ? e(t('posts.featured_image')) : 'Change Image' ?>
                </button>
            </div>
        </div>

        <?php if ($isEdit && !empty($languages ?? [])): ?>
        <!-- Translations -->
        <div class="card">
            <div class="card-header"><h3><span class="material-symbols-outlined mi-sm">translate</span> Translations</h3></div>
            <div class="card-body" style="padding:12px;display:flex;flex-direction:column;gap:6px">
                <?php foreach (($languages ?? []) as $lang):
                    if ($lang['is_default']) continue;
                    $tr = ($translations ?? [])[$lang['code']] ?? null;
                ?>
                <a href="<?= e($base) ?>/manage/posts/<?= (int)$post['id'] ?>/translate/<?= e((string)$lang['code']) ?>"
                   style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border-radius:7px;border:1px solid var(--border);text-decoration:none;color:var(--text);transition:background .12s"
                   onmouseover="this.style.background='var(--bg)'" onmouseout="this.style.background=''">
                    <span style="display:flex;align-items:center;gap:8px">
                        <span style="font-size:15px;line-height:1"><?= e((string)($lang['flag'] ?? '🌐')) ?></span>
                        <span style="font-size:13px;font-weight:500"><?= e((string)$lang['name']) ?></span>
                    </span>
                    <?php if ($tr): ?>
                    <span class="badge <?= e((string)$tr['status']) ?>" style="font-size:10px"><?= e((string)$tr['status']) ?></span>
                    <?php else: ?>
                    <span style="font-size:11px;color:var(--muted)">+ Add</span>
                    <?php endif ?>
                </a>
                <?php endforeach ?>
            </div>
        </div>
        <?php endif ?>

    </div>
</div>
</form>

<!-- Select2 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
<style>
.select2-container--default .select2-selection--single {
    height: 40px; border: 1.5px solid var(--border); border-radius: 8px; outline: none;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px; padding-left: 12px; font-size: 14px; color: var(--text);
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 38px;
}
.select2-container--default.select2-container--focus .select2-selection--single,
.select2-container--default.select2-container--open .select2-selection--single {
    border-color: var(--accent); box-shadow: 0 0 0 3px rgba(16,178,124,.1);
}
.select2-dropdown { border: 1.5px solid var(--accent); border-radius: 8px; box-shadow: 0 4px 16px rgba(0,0,0,.1); }
.select2-search--dropdown .select2-search__field {
    border: 1.5px solid var(--border); border-radius: 6px; padding: 6px 10px; font-size: 13px;
}
.select2-results__option--highlighted { background: var(--accent) !important; }
</style>

<script>
// slugEdited=true only when the post already HAS a saved slug — prevents
// auto-overwriting an existing URL on title edits.
var slugEdited = <?= ($isEdit && !empty($post['slug'])) ? 'true' : 'false' ?>;

function slugify(s) {
    return s.toLowerCase().replace(/[^\w\s-]/g,'').replace(/[\s_-]+/g,'-').replace(/^-+|-+$/g,'');
}
function autoSlug(title) {
    if (slugEdited) return;
    document.getElementById('postSlug').value = slugify(title);
    updateSlugPreview(false); // don't lock — keep following the title
}
function updateSlugPreview(lock) {
    var v = document.getElementById('postSlug').value;
    document.getElementById('slugVal').textContent = v || '…';
    if (lock !== false) slugEdited = true; // lock only on direct user edit
}

// Warn on unsaved changes
var formDirty = false;
document.getElementById('postForm').addEventListener('input', function(){ formDirty = true; });
document.getElementById('postForm').addEventListener('submit', function(){ formDirty = false; });
window.addEventListener('beforeunload', function(e){
    if (formDirty) { e.preventDefault(); e.returnValue = ''; }
});
// Direct edits to the slug field lock auto-generation
document.getElementById('postSlug').addEventListener('input', function(){ slugEdited = true; updateSlugPreview(); });

// Slug edit unlock button
function enableSlugEdit() {
    var input = document.getElementById('postSlug');
    var btn   = document.getElementById('slugEditBtn');
    input.removeAttribute('readonly');
    input.focus();
    input.select();
    if (btn) btn.style.display = 'none';
    slugEdited = true;
}
<?php if ($isEdit && !empty($post['slug'])): ?>
// In edit mode with existing slug: show as readonly until Edit is clicked
document.getElementById('postSlug').setAttribute('readonly', 'readonly');
document.getElementById('postSlug').style.background = 'var(--bg)';
document.getElementById('postSlug').style.color = 'var(--muted)';
<?php endif ?>

// Select2 — category with search
$(document).ready(function(){
    $('#catSelect').select2({
        placeholder: '— None —',
        allowClear: true,
        width: '100%',
    });
});

// Featured image — override gcEd.openGallery for __featured__ id
(function(){
    var _origOpen = gcEd ? gcEd.openGallery.bind(gcEd) : null;
    if (!_origOpen) return;
    gcEd.openGallery = function(editorId, base) {
        if (editorId !== '__featured__') { return _origOpen(editorId, base); }
        // Gallery modal for featured image
        Swal.fire({
            title: 'Select Featured Image',
            width: 860,
            html: '<div id="gc-gal-wrap" style="text-align:left">' +
                '<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:8px">' +
                '<input type="text" id="gc-gal-search" placeholder="Search files..." oninput="gcEd._galFilter()" ' +
                'style="padding:7px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;width:220px;outline:none">' +
                '<label style="cursor:pointer;background:#10B27C;color:#fff;padding:7px 14px;border-radius:7px;font-size:12.5px;font-weight:600">' +
                '<span class="material-symbols-outlined" style="font-size:16px;vertical-align:middle">upload</span> Upload<input type="file" id="gc-gal-upload" multiple accept="image/*" style="display:none" ' +
                'onchange="gcEd._galUpload(this,\'' + base + '\')"></label></div>' +
                '<div id="gc-gal-grid" class="gc-gallery-grid"><div style="grid-column:1/-1;text-align:center;padding:40px;color:#64748b">Loading...</div></div>' +
                '</div>',
            showConfirmButton: false,
            showCloseButton: true,
            customClass: { popup: 'gc-swal-popup' },
            didOpen: function(){
                window._gcGalBase  = base;
                window._gcGalEdId  = '__featured__';
                gcEd._galLoad(base, '__featured__');
            }
        });
    };

    // Override _galPick for featured image
    var _origPick = gcEd._galPick.bind(gcEd);
    gcEd._galPick = function(ins) {
        if (window._gcGalEdId === '__featured__') {
            // Extract URL from markdown image syntax
            var m = ins.match(/!\[.*?\]\((.+?)\)/);
            var url = m ? m[1] : ins;
            document.getElementById('featuredImageUrl').value = url;
            document.getElementById('featuredImg').src = url;
            document.getElementById('featuredPreview').style.display = '';
            Swal.close();
        } else {
            _origPick(ins);
        }
    };
})();

function removeFeatured() {
    document.getElementById('featuredImageUrl').value = '';
    document.getElementById('featuredPreview').style.display = 'none';
}
</script>
