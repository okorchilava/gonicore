<?php
$pageTitle = 'Media Gallery';
$activeNav = 'gallery';
$mediaItems = $media ?? [];
$totalSize  = array_sum(array_column($mediaItems, 'size'));

ob_start(); ?>
<label for="galleryUploadInput" class="topbar-btn" style="cursor:pointer">⬆ Upload Files</label>
<?php $topbarActions = ob_get_clean(); ?>

<!-- Upload form -->
<form method="POST" action="<?= e($base) ?>/manage/gallery/upload"
      enctype="multipart/form-data" id="galleryUploadForm" style="display:none">
    <input type="file" id="galleryUploadInput" name="files[]" multiple accept="image/*,video/*,audio/*,application/pdf"
           onchange="document.getElementById('galleryUploadForm').submit()">
</form>

<?php if (!empty($uploadSuccess ?? null)): ?>
<div id="gc-flash" data-msg="<?= (int)$uploadSuccess ?> file(s) uploaded." data-icon="success" style="display:none"></div>
<?php endif ?>
<?php if (!empty($uploadError ?? null)): ?>
<div id="gc-flash" data-msg="<?= e($uploadError) ?>" data-icon="error" style="display:none"></div>
<?php endif ?>

<style>
.gallery-stats{display:flex;gap:20px;margin-bottom:18px}
.gallery-stat{background:var(--surface);border:1px solid var(--border);border-radius:8px;padding:12px 18px;font-size:13px}
.gallery-stat strong{display:block;font-size:20px;font-weight:800;color:var(--text)}
.gallery-stat span{color:var(--muted)}
.gal-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px}
.gal-item{background:var(--surface);border:1px solid var(--border);border-radius:10px;overflow:hidden;transition:box-shadow .15s,border-color .15s}
.gal-item:hover{box-shadow:0 4px 16px rgba(0,0,0,.1);border-color:#c7d2fe}
.gal-thumb{aspect-ratio:1;background:var(--bg);display:flex;align-items:center;justify-content:center;overflow:hidden;position:relative}
.gal-thumb img{width:100%;height:100%;object-fit:cover}
.gal-thumb .gal-mime{font-size:28px}
.gal-info{padding:8px 10px}
.gal-name{font-size:12px;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:var(--text)}
.gal-meta{font-size:11px;color:var(--muted);margin-top:2px}
.gal-actions{display:flex;gap:4px;padding:0 8px 8px}
.gal-drop-zone{border:2px dashed var(--border);border-radius:12px;padding:48px 24px;text-align:center;cursor:pointer;transition:border-color .15s,background .15s;margin-bottom:20px}
.gal-drop-zone:hover,.gal-drop-zone.dragover{border-color:var(--accent);background:#f0fdf4}
.gal-drop-zone h3{font-size:15px;margin-bottom:6px}
.gal-drop-zone p{font-size:13px;color:var(--muted)}
.gal-filter{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;align-items:center}
</style>

<!-- Stats -->
<div class="gallery-stats">
    <div class="gallery-stat"><strong><?= count($mediaItems) ?></strong><span>Total files</span></div>
    <div class="gallery-stat"><strong><?= count(array_filter($mediaItems, fn($m) => str_starts_with($m['mime_type'],'image/'))) ?></strong><span>Images</span></div>
    <div class="gallery-stat"><strong><?= $totalSize > 0 ? round($totalSize/1024/1024,1).'MB' : '0 MB' ?></strong><span>Total size</span></div>
</div>

<!-- Drop zone -->
<div class="gal-drop-zone" id="galDropZone" onclick="document.getElementById('galleryUploadInput').click()">
    <div style="font-size:36px;margin-bottom:8px">📁</div>
    <h3>Drop files here or click to upload</h3>
    <p>Images, PDF, video, audio — max 20 MB each</p>
</div>

<!-- Filter bar -->
<div class="gal-filter">
    <input type="text" id="galSearch" placeholder="Search by filename…"
           style="padding:7px 12px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;outline:none;width:240px"
           oninput="filterGallery()">
    <select id="galType" onchange="filterGallery()"
            style="padding:7px 12px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;outline:none">
        <option value="">All types</option>
        <option value="image/">Images</option>
        <option value="video/">Video</option>
        <option value="audio/">Audio</option>
        <option value="application/pdf">PDF</option>
    </select>
    <span id="galCount" style="font-size:13px;color:var(--muted);margin-left:4px"></span>
</div>

<!-- Grid -->
<div class="gal-grid" id="galGrid">
<?php if (empty($mediaItems)): ?>
<div style="grid-column:1/-1;text-align:center;padding:48px;color:var(--muted)">
    <div style="font-size:40px;margin-bottom:12px">🖼</div>
    <h3 style="font-size:15px;color:var(--text)">No media yet</h3>
    <p style="font-size:13px;margin-top:6px">Upload files using the button above.</p>
</div>
<?php endif ?>
<?php foreach ($mediaItems as $m):
    $isImg = str_starts_with($m['mime_type'], 'image/');
    $url   = e($base) . '/storage/media/' . e($m['path']);
    $sizeStr = $m['size'] > 1048576
        ? round($m['size']/1048576, 1) . ' MB'
        : round($m['size']/1024, 0) . ' KB';
    $mimeIcons = ['video/'=>'🎬','audio/'=>'🎵','application/pdf'=>'📕','text/'=>'📄'];
    $icon = '📄';
    foreach ($mimeIcons as $k => $v) { if (str_starts_with($m['mime_type'], $k)) { $icon = $v; break; } }
?>
<div class="gal-item" data-name="<?= e(strtolower($m['original_name'])) ?>" data-type="<?= e($m['mime_type']) ?>">
    <div class="gal-thumb">
        <?php if ($isImg): ?>
        <img src="<?= $url ?>" loading="lazy" alt="<?= e($m['original_name']) ?>"
             onerror="this.outerHTML='<div class=\'gal-mime\'><?= $icon ?></div>'">
        <?php else: ?>
        <div class="gal-mime"><?= $icon ?></div>
        <?php endif ?>
    </div>
    <div class="gal-info">
        <div class="gal-name" title="<?= e($m['original_name']) ?>"><?= e($m['original_name']) ?></div>
        <div class="gal-meta"><?= $sizeStr ?> · <?= e(strtoupper(pathinfo($m['original_name'], PATHINFO_EXTENSION))) ?></div>
    </div>
    <div class="gal-actions">
        <a href="<?= $url ?>" target="_blank" class="btn btn-ghost" style="font-size:11px;padding:3px 8px;flex:1;justify-content:center">View</a>
        <button type="button" class="btn btn-ghost" style="font-size:11px;padding:3px 8px"
            onclick="copyUrl('<?= $url ?>')" title="Copy URL">📋</button>
        <form method="POST" action="<?= e($base) ?>/manage/gallery/<?= (int)$m['id'] ?>/delete" style="display:inline">
            <button type="button" class="btn btn-danger" style="font-size:11px;padding:3px 8px"
                onclick="gcConfirm(this,'Delete file?','This cannot be undone.','Delete')">✕</button>
        </form>
    </div>
</div>
<?php endforeach ?>
</div>

<script>
function filterGallery() {
    var q    = document.getElementById('galSearch').value.toLowerCase();
    var type = document.getElementById('galType').value;
    var items = document.querySelectorAll('#galGrid .gal-item');
    var shown = 0;
    items.forEach(function(el) {
        var name    = el.dataset.name || '';
        var mime    = el.dataset.type || '';
        var matchQ  = !q || name.includes(q);
        var matchT  = !type || mime.startsWith(type);
        var visible = matchQ && matchT;
        el.style.display = visible ? '' : 'none';
        if (visible) shown++;
    });
    document.getElementById('galCount').textContent = shown + ' of <?= count($mediaItems) ?> files';
}

function copyUrl(url) {
    navigator.clipboard.writeText(url).then(function(){
        gcToast('URL copied to clipboard', 'success', 2000);
    });
}

// Drag & drop upload
(function(){
    var zone = document.getElementById('galDropZone');
    zone.addEventListener('dragover', function(e){ e.preventDefault(); zone.classList.add('dragover'); });
    zone.addEventListener('dragleave', function(){ zone.classList.remove('dragover'); });
    zone.addEventListener('drop', function(e){
        e.preventDefault(); zone.classList.remove('dragover');
        var files = e.dataTransfer.files;
        if (!files.length) return;
        var fd = new FormData();
        for (var i = 0; i < files.length; i++) fd.append('files[]', files[i]);
        zone.innerHTML = '<div style="font-size:24px;margin-bottom:8px">⏳</div><h3>Uploading ' + files.length + ' file(s)…</h3>';
        fetch('<?= e($base) ?>/manage/gallery/upload', {method:'POST',body:fd})
            .then(function(){ location.reload(); })
            .catch(function(){ location.reload(); });
    });
})();

filterGallery();
</script>
