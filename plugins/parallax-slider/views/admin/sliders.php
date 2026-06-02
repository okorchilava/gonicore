<?php
/**
 * Parallax Slider — Admin List
 * Variables: $sliders, $base, $success, $error
 */
$pageTitle = 'Parallax Sliders';
$activeNav = 'sliders';
ob_start(); ?>
<button onclick="document.getElementById('createModal').style.display='flex'" class="topbar-btn">
    + New Slider
</button>
<?php $topbarActions = ob_get_clean(); ?>

<style>
.ps-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:20px;margin-top:4px}
.ps-card{background:#fff;border:1px solid var(--border);border-radius:12px;overflow:hidden;transition:box-shadow .2s}
.ps-card:hover{box-shadow:0 4px 20px rgba(0,0,0,.08)}
.ps-card-thumb{height:140px;background:linear-gradient(135deg,#1e293b,#334155);display:flex;align-items:center;justify-content:center;position:relative;overflow:hidden}
.ps-card-thumb .ps-slides-count{position:absolute;top:10px;right:10px;background:rgba(0,0,0,.5);color:#fff;font-size:11px;padding:3px 8px;border-radius:20px}
.ps-card-body{padding:14px 16px}
.ps-card-name{font-weight:700;font-size:15px;margin-bottom:4px;color:var(--text)}
.ps-card-meta{font-size:12px;color:var(--muted)}
.ps-card-actions{display:flex;gap:8px;margin-top:12px}
.ps-modal{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:1000;align-items:center;justify-content:center}
.ps-modal-box{background:#fff;border-radius:12px;padding:28px;width:420px;max-width:95vw}
</style>

<?php if ($success): ?>
<div class="alert alert-success" style="margin-bottom:16px"><?= e($success) ?></div>
<?php endif ?>
<?php if ($error): ?>
<div class="alert alert-danger" style="margin-bottom:16px"><?= e($error) ?></div>
<?php endif ?>

<?php if (empty($sliders)): ?>
<div style="text-align:center;padding:80px 20px;color:var(--muted)">
    <div style="font-size:52px;margin-bottom:16px;opacity:.3">🎞</div>
    <h3 style="color:var(--text);margin-bottom:8px">No sliders yet</h3>
    <p style="margin-bottom:20px">Create your first parallax slider with layers and effects.</p>
    <button onclick="document.getElementById('createModal').style.display='flex'" class="btn btn-primary">+ Create Slider</button>
</div>
<?php else: ?>
<div class="ps-grid">
<?php foreach ($sliders as $sl):
    $settings = json_decode((string)$sl['settings'], true) ?? [];
    $height   = $settings['height'] ?? '560px';
    $trans    = $settings['transition'] ?? 'fade';
?>
<div class="ps-card">
    <div class="ps-card-thumb" style="height:140px">
        <span style="font-size:36px;opacity:.2">🎞</span>
        <span class="ps-slides-count"><?= $trans ?></span>
    </div>
    <div class="ps-card-body">
        <div class="ps-card-name"><?= e($sl['name']) ?></div>
        <div class="ps-card-meta">Height: <?= e($height) ?> · <?= $sl['active'] ? '✓ Active' : '✗ Inactive' ?></div>
        <div class="ps-card-actions">
            <a href="<?= e($base) ?>/manage/sliders/<?= (int)$sl['id'] ?>/edit" class="btn btn-primary" style="flex:1;justify-content:center;font-size:13px;padding:7px">
                ✎ Edit
            </a>
            <button onclick="copyShortcode(<?= (int)$sl['id'] ?>)" class="btn btn-ghost" style="font-size:13px;padding:7px 10px" title="Copy shortcode">
                [&nbsp;]
            </button>
            <form method="POST" action="<?= e($base) ?>/manage/sliders/<?= (int)$sl['id'] ?>/delete" onsubmit="return confirm('Delete this slider?')">
                <button class="btn btn-danger" style="font-size:13px;padding:7px 10px">✕</button>
            </form>
        </div>
    </div>
</div>
<?php endforeach ?>
</div>
<?php endif ?>

<!-- Create modal -->
<div id="createModal" class="ps-modal" onclick="if(event.target===this)this.style.display='none'">
    <div class="ps-modal-box">
        <h3 style="margin-bottom:16px">New Slider</h3>
        <form method="POST" action="<?= e($base) ?>/manage/sliders/create">
            <div class="form-group" style="margin-bottom:16px">
                <label class="form-label">Slider Name</label>
                <input type="text" name="name" class="form-input" placeholder="e.g. Homepage Hero" autofocus required>
            </div>
            <div style="display:flex;gap:8px;justify-content:flex-end">
                <button type="button" onclick="document.getElementById('createModal').style.display='none'" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary">Create</button>
            </div>
        </form>
    </div>
</div>

<script>
function copyShortcode(id) {
    var sc = '[parallax_slider id="' + id + '"]';
    navigator.clipboard.writeText(sc).then(function(){
        Swal.fire({ icon:'success', title:'Copied!', text: sc, timer:2000, showConfirmButton:false });
    });
}
</script>
