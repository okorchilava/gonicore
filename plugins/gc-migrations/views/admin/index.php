<?php
/**
 * GC Migrations — admin page (universal importer).
 *
 * In scope (from GCMigrationsController::renderPage):
 *   $t       callable   plugin translator
 *   $base    string
 *   $form    array      last-submitted config (password blanked)
 *   $engine  string     auto|gonicore|wordpress|custom (detected/selected)
 *   $schema  ?array     source DB schema [table => [col,…]] after a preview
 *   $preview ?array     engine-aware counts, or null
 *   $report  ?array     import result counts, or null
 *   $message ?string
 *   $ok      ?bool
 */
$f   = $form ?? [];
$val = static fn(string $k, $d = '') => e((string) ($f[$k] ?? $d));
$eng = $engine ?? ($f['engine'] ?? 'auto');
$sch = is_array($schema ?? null) ? $schema : [];
?>
<style>
.gcm { max-width: 960px; }
.gcm .lead { color: var(--muted); font-size: 13.5px; line-height: 1.6; margin-bottom: 18px; }
.gcm .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.gcm .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 14px; }
.gcm .form-group { margin-bottom: 14px; }
.gcm .hint { font-size: 12px; color: var(--muted); margin-top: 5px; }
.gcm .alert { padding: 12px 16px; border-radius: 8px; font-size: 13.5px; margin-bottom: 18px; border: 1px solid var(--border); }
.gcm .alert.ok  { background: #f0fdf4; border-color: #bbf7d0; color: #15803d; }
.gcm .alert.err { background: #fef2f2; border-color: #fecaca; color: #b91c1c; }
.gcm .count-tile { background: var(--bg); border: 1px solid var(--border); border-radius: 10px; padding: 14px 16px; text-align: center; }
.gcm .count-tile .n { font-size: 26px; font-weight: 900; letter-spacing: -1px; color: var(--text); line-height: 1; }
.gcm .count-tile .l { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .6px; color: var(--muted); margin-top: 6px; }
.gcm .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 12px; }
.gcm .check-row { display: flex; align-items: flex-start; gap: 10px; padding: 9px 0; border-bottom: 1px solid var(--border); }
.gcm .check-row:last-child { border-bottom: none; }
.gcm .check-row input { width: 16px; height: 16px; accent-color: var(--accent); margin-top: 2px; flex-shrink: 0; }
.gcm .check-row label { font-size: 13.5px; font-weight: 500; cursor: pointer; }
.gcm .card + .card { margin-top: 18px; }
.gcm .actions { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 20px; }
.gcm .safety { font-size: 12px; color: var(--muted); margin-top: 14px; display: flex; gap: 8px; align-items: flex-start; }
.gcm .engine-pills { display:flex; gap:8px; flex-wrap:wrap; }
.gcm .engine-pill { flex:1; min-width:150px; border:1.5px solid var(--border); border-radius:10px; padding:12px 14px; cursor:pointer; transition:border-color .15s, background .15s; }
.gcm .engine-pill:hover { border-color:var(--accent); }
.gcm .engine-pill input { position:absolute; opacity:0; }
.gcm .engine-pill.sel { border-color:var(--accent); background:#f0fdf4; }
.gcm .engine-pill .et { font-size:13.5px; font-weight:700; color:var(--text); }
.gcm .engine-pill .ed { font-size:11.5px; color:var(--muted); margin-top:3px; }
@media (max-width: 720px){ .gcm .grid-2, .gcm .grid-3, .gcm .grid-4 { grid-template-columns: 1fr; } }
</style>

<div class="gcm">
    <p class="lead"><?= e($t('intro')) ?></p>

    <?php if (!empty($message)): ?>
        <div class="alert <?= $ok === true ? 'ok' : ($ok === false ? 'err' : '') ?>"><?= e((string) $message) ?></div>
    <?php endif ?>

    <?php if (!empty($report)): ?>
        <div class="card">
            <div class="card-header"><h3><span class="material-symbols-outlined mi-sm" style="vertical-align:-3px;color:var(--accent)">task_alt</span> <?= e($t('report_title')) ?></h3></div>
            <div class="card-body">
                <div class="grid-4">
                    <div class="count-tile"><div class="n"><?= (int) ($report['posts'] ?? 0) ?></div><div class="l"><?= e($t('r_posts')) ?></div></div>
                    <div class="count-tile"><div class="n"><?= (int) ($report['pages'] ?? 0) ?></div><div class="l"><?= e($t('r_pages')) ?></div></div>
                    <div class="count-tile"><div class="n"><?= (int) ($report['categories'] ?? 0) ?></div><div class="l"><?= e($t('r_categories')) ?></div></div>
                    <div class="count-tile"><div class="n"><?= (int) ($report['translations'] ?? 0) ?></div><div class="l"><?= e($t('r_translations')) ?></div></div>
                </div>
                <?php if ((int) ($report['skipped'] ?? 0) > 0): ?>
                    <p class="hint" style="margin-top:12px"><?= e($t('r_skipped')) ?>: <strong><?= (int) $report['skipped'] ?></strong></p>
                <?php endif ?>
            </div>
        </div>
    <?php endif ?>

    <form method="POST" action="<?= e($base) ?>/manage/migrations/preview" id="gcmForm">

        <!-- Source engine -->
        <div class="card">
            <div class="card-header"><h3><span class="material-symbols-outlined mi-sm" style="vertical-align:-3px;color:var(--accent)">hub</span> <?= e($t('engine')) ?></h3></div>
            <div class="card-body">
                <div class="engine-pills">
                    <?php foreach ([
                        'auto'      => [$t('engine_auto'),      $t('engine_auto_d')],
                        'gonicore'  => [$t('engine_gonicore'),  $t('engine_gonicore_d')],
                        'wordpress' => [$t('engine_wordpress'), $t('engine_wordpress_d')],
                        'custom'    => [$t('engine_custom'),    $t('engine_custom_d')],
                    ] as $ev => [$et, $ed]): ?>
                    <label class="engine-pill <?= $eng === $ev ? 'sel' : '' ?>" data-engine="<?= e($ev) ?>">
                        <input type="radio" name="engine" value="<?= e($ev) ?>" <?= $eng === $ev ? 'checked' : '' ?>>
                        <div class="et"><?= e($et) ?></div>
                        <div class="ed"><?= e($ed) ?></div>
                    </label>
                    <?php endforeach ?>
                </div>
            </div>
        </div>

        <!-- Source connection -->
        <div class="card">
            <div class="card-header"><h3><span class="material-symbols-outlined mi-sm" style="vertical-align:-3px;color:var(--accent)">database</span> <?= e($t('source')) ?></h3></div>
            <div class="card-body">
                <p class="hint" style="margin-top:0;margin-bottom:14px"><?= e($t('source_hint')) ?></p>
                <div class="grid-2">
                    <div class="form-group">
                        <label class="form-label"><?= e($t('host')) ?></label>
                        <input class="form-input" type="text" name="host" value="<?= $val('host', '127.0.0.1') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e($t('port')) ?></label>
                        <input class="form-input" type="number" name="port" value="<?= $val('port', '3306') ?>" min="1" max="65535">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e($t('dbname')) ?></label>
                        <input class="form-input" type="text" name="dbname" value="<?= $val('dbname') ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e($t('username')) ?></label>
                        <input class="form-input" type="text" name="username" value="<?= $val('username') ?>" autocomplete="off" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e($t('password')) ?></label>
                        <input class="form-input" type="password" name="password" value="" autocomplete="new-password">
                        <div class="hint"><?= e($t('password_hint')) ?></div>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><?= e($t('prefix')) ?></label>
                        <input class="form-input" type="text" name="prefix" value="<?= $val('prefix') ?>" placeholder="wp_">
                        <div class="hint"><?= e($t('prefix_hint')) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Preview counts -->
        <?php if (!empty($preview)): ?>
        <div class="card">
            <div class="card-header"><h3><span class="material-symbols-outlined mi-sm" style="vertical-align:-3px;color:var(--info)">search</span> <?= e($t('preview_title')) ?></h3></div>
            <div class="card-body">
                <?php if ($eng === 'custom'): ?>
                <div class="grid-4">
                    <div class="count-tile"><div class="n"><?= (int) ($preview['posts'] ?? 0) ?></div><div class="l"><?= e($t('c_rows')) ?></div></div>
                </div>
                <?php else: ?>
                <div class="grid-4">
                    <div class="count-tile"><div class="n"><?= (int) ($preview['posts'] ?? 0) ?></div><div class="l"><?= e($t('c_posts')) ?></div></div>
                    <div class="count-tile"><div class="n"><?= (int) ($preview['pages'] ?? 0) ?></div><div class="l"><?= e($t('c_pages')) ?></div></div>
                    <div class="count-tile"><div class="n"><?= (int) ($preview['categories'] ?? 0) ?></div><div class="l"><?= e($t('c_categories')) ?></div></div>
                    <div class="count-tile"><div class="n"><?= (int) ($preview['translations'] ?? 0) ?></div><div class="l"><?= e($t('c_translations')) ?></div></div>
                </div>
                <?php endif ?>
            </div>
        </div>
        <?php endif ?>

        <!-- Custom column mapping (any/unknown engine) -->
        <div class="card" id="customCard" style="<?= $eng === 'custom' ? '' : 'display:none' ?>">
            <div class="card-header"><h3><span class="material-symbols-outlined mi-sm" style="vertical-align:-3px;color:var(--accent)">table_rows</span> <?= e($t('map_title_h')) ?></h3></div>
            <div class="card-body">
                <?php if ($sch === []): ?>
                    <p class="hint" style="margin-top:0"><?= e($t('map_connect_first')) ?></p>
                <?php endif ?>
                <div class="form-group">
                    <label class="form-label"><?= e($t('map_table')) ?></label>
                    <select class="form-select" name="src_table" id="srcTable">
                        <option value="">— <?= e($t('map_table')) ?> —</option>
                        <?php foreach (array_keys($sch) as $tbl): ?>
                        <option value="<?= e($tbl) ?>" <?= (string)($f['src_table'] ?? '') === $tbl ? 'selected' : '' ?>><?= e($tbl) ?></option>
                        <?php endforeach ?>
                    </select>
                </div>
                <div class="grid-3">
                    <?php
                    $mapFields = [
                        'map_title'   => [$t('map_f_title'),   true],
                        'map_content' => [$t('map_f_content'), false],
                        'map_slug'    => [$t('map_f_slug'),    false],
                        'map_excerpt' => [$t('map_f_excerpt'), false],
                        'map_status'  => [$t('map_f_status'),  false],
                        'map_type'    => [$t('map_f_type'),    false],
                        'map_created' => [$t('map_f_created'), false],
                    ];
                    foreach ($mapFields as $name => [$label, $req]): ?>
                    <div class="form-group">
                        <label class="form-label"><?= e($label) ?><?= $req ? ' *' : '' ?></label>
                        <select class="form-select map-col" name="<?= e($name) ?>" data-val="<?= e((string)($f[$name] ?? '')) ?>"></select>
                    </div>
                    <?php endforeach ?>
                </div>
                <div class="grid-2">
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label"><?= e($t('map_published_value')) ?></label>
                        <input class="form-input" type="text" name="status_published" value="<?= $val('status_published') ?>" placeholder="publish / 1 / active">
                        <div class="hint"><?= e($t('map_published_hint')) ?></div>
                    </div>
                    <div class="form-group" style="margin-bottom:0">
                        <label class="form-label"><?= e($t('map_default_type')) ?></label>
                        <select class="form-select" name="default_type">
                            <option value="post" <?= ($f['default_type'] ?? 'post') !== 'page' ? 'selected' : '' ?>><?= e($t('opt_posts')) ?></option>
                            <option value="page" <?= ($f['default_type'] ?? 'post') === 'page' ? 'selected' : '' ?>><?= e($t('opt_pages')) ?></option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Import options -->
        <div class="card">
            <div class="card-header"><h3><span class="material-symbols-outlined mi-sm" style="vertical-align:-3px;color:var(--accent)">checklist</span> <?= e($t('import_title')) ?></h3></div>
            <div class="card-body">
                <div class="grid-2">
                    <div>
                        <div class="check-row"><input type="checkbox" id="o_posts" name="import_posts" value="1" checked><label for="o_posts"><?= e($t('opt_posts')) ?></label></div>
                        <div class="check-row"><input type="checkbox" id="o_pages" name="import_pages" value="1" checked><label for="o_pages"><?= e($t('opt_pages')) ?></label></div>
                        <div class="check-row"><input type="checkbox" id="o_cats" name="import_categories" value="1" checked><label for="o_cats"><?= e($t('opt_categories')) ?> <span class="hint" style="margin:0">(<?= e($t('opt_cats_note')) ?>)</span></label></div>
                        <div class="check-row"><input type="checkbox" id="o_trans" name="import_translations" value="1" checked><label for="o_trans"><?= e($t('opt_translations')) ?> <span class="hint" style="margin:0">(<?= e($t('opt_trans_note')) ?>)</span></label></div>
                    </div>
                    <div>
                        <label class="form-label" style="margin-bottom:8px"><?= e($t('dup_title')) ?></label>
                        <div class="check-row"><input type="radio" id="d_skip" name="duplicate_mode" value="skip" checked><label for="d_skip"><?= e($t('dup_skip')) ?></label></div>
                        <div class="check-row"><input type="radio" id="d_rename" name="duplicate_mode" value="rename"><label for="d_rename"><?= e($t('dup_rename')) ?></label></div>
                    </div>
                </div>

                <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--border)">
                    <div class="check-row" style="border:none;padding-bottom:0">
                        <input type="checkbox" id="trust" name="confirm_trust" value="1">
                        <label for="trust"><strong><?= e($t('trust_title')) ?>.</strong> <?= e($t('trust_label')) ?></label>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit" class="btn btn-ghost">
                        <span class="material-symbols-outlined mi-sm">cable</span> <?= e($t('test')) ?>
                    </button>
                    <button type="submit" formaction="<?= e($base) ?>/manage/migrations/import" class="btn btn-primary">
                        <span class="material-symbols-outlined mi-sm">cloud_download</span> <?= e($t('import')) ?>
                    </button>
                </div>

                <div class="safety">
                    <span class="material-symbols-outlined mi-sm" style="color:var(--accent)">shield</span>
                    <span><?= e($t('safety_note')) ?></span>
                </div>
            </div>
        </div>

    </form>
</div>

<script>
window.GCM_SCHEMA = <?= json_encode($sch ?: new stdClass, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;
(function () {
    // Engine pill selection toggles the custom-mapping card.
    var pills = document.querySelectorAll('.engine-pill');
    var custom = document.getElementById('customCard');
    function syncEngine() {
        var sel = document.querySelector('input[name="engine"]:checked');
        var eng = sel ? sel.value : 'auto';
        pills.forEach(function (p) { p.classList.toggle('sel', p.dataset.engine === eng); });
        if (custom) custom.style.display = (eng === 'custom') ? '' : 'none';
    }
    pills.forEach(function (p) {
        var inp = p.querySelector('input');
        p.addEventListener('click', function () { inp.checked = true; syncEngine(); });
    });
    syncEngine();

    // Custom mapping: fill column <select>s from the chosen source table.
    var SCHEMA = window.GCM_SCHEMA || {};
    var srcTable = document.getElementById('srcTable');
    function fillColumns(table) {
        var cols = SCHEMA[table] || [];
        document.querySelectorAll('.map-col').forEach(function (sel) {
            var want = sel.value || sel.getAttribute('data-val') || '';
            var html = '<option value="">— none —</option>';
            cols.forEach(function (c) { html += '<option value="' + c + '">' + c + '</option>'; });
            sel.innerHTML = html;
            if (want && cols.indexOf(want) >= 0) sel.value = want;
        });
    }
    if (srcTable) {
        srcTable.addEventListener('change', function () { fillColumns(this.value); });
        if (srcTable.value) fillColumns(srcTable.value);
    }
})();
</script>
