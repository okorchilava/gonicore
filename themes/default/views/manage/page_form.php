<?php
$isEdit    = !empty($post);
$pageTitle = $isEdit ? 'Edit Page' : 'New Page';
$activeNav = 'pages';
ob_start(); ?>
<a href="<?= e($base) ?>/manage/pages" class="topbar-btn ghost">← Pages</a>
<?php if ($isEdit): ?>
<?php
// Plugins can inject buttons here via manage.page_form.topbar hook
if (isset($hooks) && $hooks instanceof \GoniCore\Core\Hooks\HookManager) {
    $hooks->doAction('manage.page_form.topbar', $post ?? [], $base);
}
?>
<a href="<?= e($base) ?>/page/<?= e($post['slug'] ?? '') ?>" target="_blank" class="topbar-btn ghost" style="margin-left:4px">↗ View</a>
<?php endif ?>
<?php $topbarActions = ob_get_clean();

$formAction = $isEdit
    ? e($base) . '/manage/pages/' . (int)$post['id']
    : e($base) . '/manage/pages';
?>

<style>
.page-editor-wrap { display: grid; grid-template-columns: 1fr 280px; gap: 20px; align-items: start; max-width: 1100px; }
.slug-preview { font-size: 12px; color: var(--muted); margin-top: 5px; word-break: break-all; }
.slug-preview span { color: var(--accent); }
@media (max-width: 860px) { .page-editor-wrap { grid-template-columns: 1fr; } }
</style>

<form method="POST" action="<?= $formAction ?>" id="pageForm">

<div class="page-editor-wrap">

    <!-- Main -->
    <div>
        <div style="margin-bottom:14px">
            <input type="text" name="title" id="pageTitle" class="form-input"
                   value="<?= e((string)($post['title'] ?? '')) ?>"
                   placeholder="Page title"
                   style="font-size:22px;font-weight:700;padding:14px 16px;border-radius:10px"
                   oninput="autoSlug(this.value)" required>
        </div>

        <div style="margin-bottom:14px">
            <div style="display:flex;align-items:center;gap:8px">
                <span style="font-size:12px;color:var(--muted);white-space:nowrap">Slug:</span>
                <input type="text" name="slug" id="pageSlug" class="form-input"
                       value="<?= e((string)($post['slug'] ?? '')) ?>"
                       placeholder="auto-generated"
                       style="font-size:13px;padding:6px 10px;font-family:monospace;flex:1"
                       oninput="updateSlugPreview()">
                <?php if ($isEdit && !empty($post['slug'])): ?>
                <button type="button" id="slugEditBtn" title="Edit slug"
                        onclick="enableSlugEdit()"
                        style="padding:5px 10px;border:1.5px solid var(--border);border-radius:6px;background:var(--bg);color:var(--muted);font-size:12px;cursor:pointer;white-space:nowrap;flex-shrink:0">
                    ✏️ Edit
                </button>
                <?php endif ?>
            </div>
            <div class="slug-preview">
                <?= e($base) ?>/page/<span id="slugVal"><?= e((string)($post['slug'] ?? '')) ?></span>
            </div>
        </div>

        <!-- Rich text editor -->
        <?php
        $editorName   = 'content';
        $editorId     = 'pageContent';
        $editorValue  = (string)($post['content'] ?? '');
        $editorHeight = '500px';
        include __DIR__ . '/_editor.php';
        ?>
    </div>

    <!-- Sidebar -->
    <div style="display:flex;flex-direction:column;gap:14px">

        <div class="card">
            <div class="card-header"><h3>Publish</h3></div>
            <div class="card-body" style="padding:16px">
                <div style="margin-bottom:14px">
                    <label class="form-label" style="margin-bottom:8px">Status</label>
                    <?php foreach (['draft'=>['Draft','#f59e0b'],'published'=>['Published','#10b981'],'archived'=>['Archived','#94a3b8']] as $val => [$label, $color]): ?>
                    <label style="display:flex;align-items:center;gap:8px;margin-bottom:8px;cursor:pointer;font-size:13.5px">
                        <input type="radio" name="status" value="<?= $val ?>"
                               <?= (($post['status'] ?? 'draft') === $val) ? 'checked' : '' ?>
                               style="accent-color:<?= $color ?>">
                        <span style="display:flex;align-items:center;gap:5px">
                            <span style="width:8px;height:8px;border-radius:50%;background:<?= $color ?>;display:inline-block"></span>
                            <?= $label ?>
                        </span>
                    </label>
                    <?php endforeach ?>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;padding:10px;font-size:14px;justify-content:center">
                    <?= $isEdit ? 'Save Changes' : 'Create Page' ?>
                </button>
                <?php if ($isEdit): ?>
                <div style="text-align:center;margin-top:10px;font-size:12px;color:var(--muted)">
                    Updated <?= e(fmt_date((string)($post['updated_at'] ?? ''))) ?>
                </div>
                <?php endif ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Template</h3></div>
            <div class="card-body" style="padding:16px">
                <select name="template" id="tplSelect" class="form-select">
                    <?php foreach ($templates ?? [] as $tpl): ?>
                    <option value="<?= e($tpl['slug']) ?>"
                        <?= (($post['template'] ?? 'default') === $tpl['slug']) ? 'selected' : '' ?>>
                        <?= e($tpl['name']) ?>
                    </option>
                    <?php endforeach ?>
                </select>
                <div id="tplDesc" style="font-size:12px;color:var(--muted);margin-top:6px"></div>
            </div>
        </div>

        <!-- Featured Image -->
        <div class="card">
            <div class="card-header">
                <h3>Featured Image</h3>
                <?php if (!empty($post['featured_image'])): ?>
                <button type="button" class="btn btn-ghost" style="font-size:11px;padding:2px 8px"
                    onclick="document.getElementById('pageFeaturedUrl').value='';document.getElementById('pageFeaturedPreview').style.display='none'">Remove</button>
                <?php endif ?>
            </div>
            <div class="card-body" style="padding:12px">
                <input type="hidden" name="featured_image" id="pageFeaturedUrl"
                       value="<?= e((string)($post['featured_image'] ?? '')) ?>">
                <div id="pageFeaturedPreview" style="margin-bottom:10px;<?= empty($post['featured_image']) ? 'display:none' : '' ?>">
                    <img id="pageFeaturedImg"
                         src="<?= e((string)($post['featured_image'] ?? '')) ?>"
                         style="width:100%;border-radius:8px;border:1px solid var(--border)">
                </div>
                <button type="button" class="btn btn-ghost" style="width:100%;justify-content:center;font-size:12.5px"
                    onclick="gcEd.openGallery('__page_featured__','<?= e($base) ?>')">
                    🖼 <?= empty($post['featured_image']) ? 'Set Featured Image' : 'Change Image' ?>
                </button>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3>Parent</h3></div>
            <div class="card-body" style="padding:16px">
                <select name="parent_id" class="form-select">
                    <option value="">No parent</option>
                    <?php foreach ($allPages ?? [] as $pg):
                        if ($isEdit && (int)$pg['id'] === (int)($post['id'] ?? 0)) continue;
                    ?>
                    <option value="<?= (int)$pg['id'] ?>"
                        <?= ((int)($post['parent_id'] ?? 0) === (int)$pg['id']) ? 'selected' : '' ?>>
                        <?= e($pg['title']) ?>
                    </option>
                    <?php endforeach ?>
                </select>
                <div style="font-size:12px;color:var(--muted);margin-top:6px">Makes this a sub-page.</div>
            </div>
        </div>

    </div>
</div>
</form>

<!-- Gallery CSS for page editor -->
<style>
.gc-gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(110px,1fr));gap:8px;max-height:55vh;overflow-y:auto;padding:2px}
.gc-gallery-item{position:relative;border-radius:8px;overflow:hidden;border:2px solid transparent;cursor:pointer;transition:border-color .15s,transform .12s;aspect-ratio:1;background:var(--bg)}
.gc-gallery-item:hover{border-color:var(--accent);transform:scale(1.03)}
.gc-gallery-item img{width:100%;height:100%;object-fit:cover;display:block}
.gc-gallery-item .gc-gi-name{position:absolute;bottom:0;left:0;right:0;padding:3px 5px;background:rgba(0,0,0,.6);color:#fff;font-size:9px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
</style>
<script>
var slugEdited = <?= ($isEdit && !empty($post['slug'])) ? 'true' : 'false' ?>;
function slugify(s){ return s.toLowerCase().replace(/[^\w\s-]/g,'').replace(/[\s_-]+/g,'-').replace(/^-+|-+$/g,''); }
function autoSlug(t){ if(slugEdited) return; document.getElementById('pageSlug').value=slugify(t); updateSlugPreview(false); }
function updateSlugPreview(lock){ document.getElementById('slugVal').textContent=document.getElementById('pageSlug').value||'...'; if(lock!==false) slugEdited=true; }
var tplMeta = <?= json_encode(array_column($templates ?? [], null, 'slug')) ?>;
var tplSel  = document.getElementById('tplSelect');
function updateTplDesc(){ var t=tplMeta[tplSel.value]; document.getElementById('tplDesc').textContent=t?(t.description||''):''; }
if(tplSel){ tplSel.addEventListener('change',updateTplDesc); updateTplDesc(); }
var dirty=false;
document.getElementById('pageForm').addEventListener('input',function(){dirty=true;});
document.getElementById('pageForm').addEventListener('submit',function(){dirty=false;});
window.addEventListener('beforeunload',function(e){if(dirty){e.preventDefault();e.returnValue='';}});
document.getElementById('pageSlug').addEventListener('input',function(){slugEdited=true;updateSlugPreview();});

function enableSlugEdit(){
    var input=document.getElementById('pageSlug');
    var btn=document.getElementById('slugEditBtn');
    input.removeAttribute('readonly');
    input.focus();input.select();
    if(btn) btn.style.display='none';
    slugEdited=true;
}
<?php if($isEdit && !empty($post['slug'])): ?>
document.getElementById('pageSlug').setAttribute('readonly','readonly');
document.getElementById('pageSlug').style.background='var(--bg)';
document.getElementById('pageSlug').style.color='var(--muted)';
<?php endif ?>

// Featured image for page
(function(){
    if (!window.gcEd) return;
    var _origOpen = gcEd.openGallery.bind(gcEd);
    var _origPick = gcEd._galPick.bind(gcEd);
    gcEd.openGallery = function(editorId, base) {
        if (editorId === '__page_featured__') {
            window._gcGalEdId = '__page_featured__';
            window._gcGalBase = base;
            Swal.fire({
                title: 'Select Featured Image', width: 860,
                html: '<div id="gc-gal-wrap"><div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;gap:8px"><input type="text" id="gc-gal-search" placeholder="Search..." oninput="gcEd._galFilter()" style="padding:7px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;width:220px;outline:none"><label style="cursor:pointer;background:#10B27C;color:#fff;padding:7px 14px;border-radius:7px;font-size:12.5px;font-weight:600">⬆ Upload<input type="file" id="gc-gal-upload" multiple accept="image/*" style="display:none" onchange="gcEd._galUpload(this,\''+base+'\')"></label></div><div id="gc-gal-grid" class="gc-gallery-grid"><div style="grid-column:1/-1;text-align:center;padding:40px;color:#64748b">Loading...</div></div></div>',
                showConfirmButton: false, showCloseButton: true,
                customClass: { popup: 'gc-swal-popup' },
                didOpen: function(){ gcEd._galLoad(base, '__page_featured__'); }
            });
        } else { _origOpen(editorId, base); }
    };
    gcEd._galPick = function(ins) {
        if (window._gcGalEdId === '__page_featured__') {
            var m = ins.match(/!\[.*?\]\((.+?)\)/);
            var url = m ? m[1] : ins;
            document.getElementById('pageFeaturedUrl').value = url;
            document.getElementById('pageFeaturedImg').src = url;
            document.getElementById('pageFeaturedPreview').style.display = '';
            Swal.close();
        } else { _origPick(ins); }
    };
})();
</script>
