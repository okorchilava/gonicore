<?php
/**
 * GC Lazy Loader — settings page (rendered inside manage layout)
 * Vars: $base, $values (array<string,mixed>)
 */
use GCLazyLoader\LazyLoaderAdmin;

/** @var callable $t  plugin translator (from LazyLoaderAdmin::renderPage) */
$pageTitle = $t('title');
$v = $values ?? [];

// type => spinner markup, for the live preview JS
$spinMap = [];
foreach (array_keys(LazyLoaderAdmin::SPINNERS) as $sk) {
    $spinMap[$sk] = LazyLoaderAdmin::spinnerHtml($sk);
}
?>
<style>
.ll-toggle{position:relative;display:inline-flex;align-items:center;cursor:pointer}
.ll-toggle input{position:absolute;opacity:0;width:0;height:0}
.ll-track{width:40px;height:22px;background:#cbd5e1;border-radius:11px;transition:background .2s;position:relative;flex-shrink:0}
.ll-toggle input:checked~.ll-track{background:var(--accent)}
.ll-thumb{position:absolute;top:3px;left:3px;width:16px;height:16px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.ll-toggle input:checked~.ll-track .ll-thumb{transform:translateX(18px)}
.ll-row{display:flex;align-items:center;justify-content:space-between;gap:16px;padding:16px 0;border-bottom:1px solid var(--border)}
.ll-row:last-child{border-bottom:none}
.ll-select{padding:8px 12px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;background:#fff;color:var(--text);outline:none;min-width:190px;cursor:pointer}
.ll-select:focus{border-color:var(--accent)}
.ll-color{width:46px;height:34px;border:1.5px solid var(--border);border-radius:8px;cursor:pointer;background:none;padding:2px}
<?= LazyLoaderAdmin::spinnerCss() ?>
</style>

<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="padding:20px;display:flex;align-items:center;gap:16px">
        <div style="width:52px;height:52px;border-radius:12px;background:rgba(16,178,124,.12);display:flex;align-items:center;justify-content:center;flex-shrink:0">
            <span class="material-symbols-outlined" style="font-size:28px;color:var(--accent)">bolt</span>
        </div>
        <div>
            <div style="font-size:16px;font-weight:700"><?= e($t('title')) ?></div>
            <div style="font-size:13px;color:var(--muted);margin-top:2px">
                <?= e($t('intro')) ?>
            </div>
        </div>
    </div>
</div>

<form method="POST" action="<?= e($base) ?>/manage/lazyloader">
    <div class="card">
        <div class="card-header"><h3><span class="material-symbols-outlined mi-sm">tune</span> <?= e($t('options')) ?></h3></div>
        <div class="card-body" style="padding:4px 20px 8px">

            <div class="ll-row">
                <div>
                    <div style="font-size:14px;font-weight:600"><?= e($t('images')) ?></div>
                    <div style="font-size:13px;color:var(--muted)"><?= e($t('images_hint')) ?></div>
                </div>
                <label class="ll-toggle">
                    <input type="checkbox" name="lazyload_images" value="1" <?= !empty($v['lazyload_images']) ? 'checked' : '' ?>>
                    <span class="ll-track"><span class="ll-thumb"></span></span>
                </label>
            </div>

            <div class="ll-row">
                <div>
                    <div style="font-size:14px;font-weight:600"><?= e($t('iframes')) ?></div>
                    <div style="font-size:13px;color:var(--muted)"><?= e($t('iframes_hint')) ?></div>
                </div>
                <label class="ll-toggle">
                    <input type="checkbox" name="lazyload_iframes" value="1" <?= !empty($v['lazyload_iframes']) ? 'checked' : '' ?>>
                    <span class="ll-track"><span class="ll-thumb"></span></span>
                </label>
            </div>

            <div class="ll-row">
                <div>
                    <div style="font-size:14px;font-weight:600"><?= e($t('fade')) ?></div>
                    <div style="font-size:13px;color:var(--muted)"><?= e($t('fade_hint')) ?></div>
                </div>
                <label class="ll-toggle">
                    <input type="checkbox" name="lazyload_fade" value="1" <?= !empty($v['lazyload_fade']) ? 'checked' : '' ?>>
                    <span class="ll-track"><span class="ll-thumb"></span></span>
                </label>
            </div>

            <div class="ll-row">
                <div>
                    <div style="font-size:14px;font-weight:600"><?= e($t('pageloader')) ?></div>
                    <div style="font-size:13px;color:var(--muted)"><?= e($t('pageloader_hint')) ?></div>
                </div>
                <label class="ll-toggle">
                    <input type="checkbox" name="lazyload_pageloader" value="1" <?= !empty($v['lazyload_pageloader']) ? 'checked' : '' ?>>
                    <span class="ll-track"><span class="ll-thumb"></span></span>
                </label>
            </div>

        </div>
    </div>

    <!-- Page transition loader appearance -->
    <div class="card" style="margin-top:16px">
        <div class="card-header"><h3><span class="material-symbols-outlined mi-sm">progress_activity</span> <?= e($t('appearance')) ?></h3></div>
        <div class="card-body" style="padding:4px 20px 8px">

            <div class="ll-row">
                <div>
                    <div style="font-size:14px;font-weight:600"><?= e($t('style')) ?></div>
                    <div style="font-size:13px;color:var(--muted)"><?= e($t('style_hint')) ?></div>
                </div>
                <select name="lazyload_loader_style" class="ll-select">
                    <?php foreach (array_keys(LazyLoaderAdmin::STYLES) as $sk): ?>
                    <option value="<?= e($sk) ?>" <?= (($v['lazyload_loader_style'] ?? 'bar') === $sk) ? 'selected' : '' ?>><?= e($t('style_' . $sk)) ?></option>
                    <?php endforeach ?>
                </select>
            </div>

            <div class="ll-row">
                <div>
                    <div style="font-size:14px;font-weight:600"><?= e($t('spinner')) ?></div>
                    <div style="font-size:13px;color:var(--muted)"><?= e($t('spinner_hint')) ?></div>
                </div>
                <div style="display:flex;align-items:center;gap:14px">
                    <span id="llPreview" style="display:inline-flex;align-items:center;justify-content:center;width:34px;height:34px"></span>
                    <select name="lazyload_spinner" id="llSpinner" class="ll-select" style="min-width:150px">
                        <?php foreach (array_keys(LazyLoaderAdmin::SPINNERS) as $pk): ?>
                        <option value="<?= e($pk) ?>" <?= (($v['lazyload_spinner'] ?? 'ring') === $pk) ? 'selected' : '' ?>><?= e($t('spin_' . $pk)) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>

            <div class="ll-row">
                <div>
                    <div style="font-size:14px;font-weight:600"><?= e($t('color')) ?></div>
                    <div style="font-size:13px;color:var(--muted)"><?= e($t('color_hint')) ?></div>
                </div>
                <input type="color" name="lazyload_color" id="llColor" class="ll-color"
                       value="<?= e($v['lazyload_color'] ?? '#10B27C') ?>">
            </div>

        </div>
    </div>

    <div style="margin-top:16px">
        <button type="submit" class="btn btn-primary" style="padding:10px 28px"><?= e($t('save')) ?></button>
    </div>
</form>

<script>
(function () {
    var spinMap = <?= json_encode($spinMap, JSON_UNESCAPED_SLASHES) ?>;
    var sel = document.getElementById('llSpinner');
    var col = document.getElementById('llColor');
    var box = document.getElementById('llPreview');
    if (!sel || !col || !box) return;
    function render() {
        box.innerHTML = spinMap[sel.value] || spinMap.ring || '';
        var sp = box.querySelector('.gcsp');
        if (sp) { sp.style.setProperty('--c', col.value); sp.style.setProperty('--s', '26px'); }
    }
    sel.addEventListener('change', render);
    col.addEventListener('input', render);
    render();
})();
</script>
