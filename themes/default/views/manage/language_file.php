<?php
/**
 * Language file translation editor.
 * Variables: $lang, $code, $enKeys (array), $translations (array), $success, $error
 */
$pageTitle     = 'Translate: ' . ($lang['name'] ?? strtoupper($code));
$activeNav     = 'languages';
$topbarActions = '<a href="' . e($base) . '/manage/languages" class="topbar-btn">← Languages</a>';

// Group keys by category (first segment before the dot)
$grouped = [];
foreach ($enKeys as $key => $enValue) {
    $cat = explode('.', (string)$key)[0];
    $grouped[$cat][] = ['key' => $key, 'en' => $enValue];
}
?>

<style>
.lf-grid{display:grid;gap:0}
.lf-cat-head{
    padding:10px 16px;background:var(--bg);
    border:1px solid var(--border);border-bottom:none;
    font-size:11px;font-weight:700;text-transform:uppercase;
    letter-spacing:.8px;color:var(--muted);
    display:flex;align-items:center;gap:8px;
    position:sticky;top:60px;z-index:10;
    margin-top:12px;border-radius:var(--radius) var(--radius) 0 0;
}
.lf-cat-head:first-child{margin-top:0}
.lf-row{
    display:grid;grid-template-columns:220px 1fr 1fr;
    border:1px solid var(--border);border-top:none;
    transition:background .12s;
}
.lf-row:last-child{border-radius:0 0 var(--radius) var(--radius)}
.lf-row:hover{background:#fafbff}
.lf-cell{padding:10px 14px;font-size:13px;border-right:1px solid var(--border)}
.lf-cell:last-child{border-right:none}
.lf-key{font-family:'Fira Code',monospace;font-size:11.5px;color:var(--muted);word-break:break-all;line-height:1.5}
.lf-en{color:var(--text);line-height:1.5}
.lf-input{
    width:100%;border:none;background:transparent;
    font-family:inherit;font-size:13px;color:var(--text);
    line-height:1.5;outline:none;resize:vertical;
    padding:0;min-height:24px;
}
.lf-input:focus{outline:2px solid var(--accent);outline-offset:2px;border-radius:3px;background:#f0fdf4}
.lf-input.empty{color:var(--muted);background:#fffbeb}
.lf-header-row{
    display:grid;grid-template-columns:220px 1fr 1fr;
    border:1px solid var(--border);border-bottom:none;
    background:var(--surface);border-radius:var(--radius) var(--radius) 0 0;
}
.lf-header-row .lf-cell{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.6px;color:var(--muted)}
.lf-sticky-bar{
    position:sticky;bottom:0;z-index:20;
    background:var(--surface);border-top:1px solid var(--border);
    padding:14px 20px;display:flex;align-items:center;justify-content:space-between;
    box-shadow:0 -4px 16px rgba(0,0,0,.06);
    margin:0 -20px;
}
.lf-progress{font-size:13px;color:var(--muted)}
.lf-progress strong{color:var(--accent)}
</style>

<!-- Header info -->
<div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;flex-wrap:wrap">
  <div style="font-size:36px"><?= e((string)($lang['flag'] ?? '🌐')) ?></div>
  <div>
    <div style="font-size:20px;font-weight:800"><?= e((string)($lang['name'] ?? $code)) ?></div>
    <div style="font-size:13px;color:var(--muted)"><?= e((string)($lang['native'] ?? '')) ?> · <code><?= e($code) ?></code>
      · Translating <code>lang/<?= e($code) ?>.php</code>
    </div>
  </div>
  <div style="margin-left:auto;text-align:right;font-size:13px;color:var(--muted)">
    <?php
    $total   = count($enKeys);
    $filled  = 0;
    foreach (array_keys($enKeys) as $k) {
        if (!empty($translations[$k])) $filled++;
    }
    $pct = $total > 0 ? round($filled / $total * 100) : 0;
    ?>
    <div style="font-size:22px;font-weight:900;color:var(--accent)"><?= $pct ?>%</div>
    <div><?= $filled ?> / <?= $total ?> translated</div>
  </div>
</div>

<?php if (empty($enKeys)): ?>
<div class="card"><div class="card-body"><p style="color:var(--muted)">No keys found in <code>lang/en.php</code>. Add keys there first.</p></div></div>
<?php else: ?>

<form method="POST" action="<?= e($base) ?>/manage/languages/<?= e($code) ?>/file" id="langFileForm">

  <!-- Sticky header row -->
  <div class="lf-header-row" style="position:sticky;top:60px;z-index:11">
    <div class="lf-cell">Key</div>
    <div class="lf-cell">English (source)</div>
    <div class="lf-cell"><?= e((string)($lang['native'] ?? $lang['name'] ?? $code)) ?> translation</div>
  </div>

  <?php foreach ($grouped as $cat => $rows): ?>
  <div class="lf-cat-head">
    <span class="material-symbols-outlined mi-sm">folder</span> <?= e(ucfirst($cat)) ?>
    <span style="font-weight:400;margin-left:auto"><?= count($rows) ?> key<?= count($rows) !== 1 ? 's' : '' ?></span>
  </div>
  <div class="lf-grid">
    <?php foreach ($rows as $row):
      $key     = $row['key'];
      $enVal   = (string)$row['en'];
      $current = (string)($translations[$key] ?? '');
      $isEmpty = ($current === '');
      $inputId = 'tr_' . preg_replace('/[^a-z0-9]/i', '_', $key);
    ?>
    <div class="lf-row">
      <div class="lf-cell lf-key" title="<?= e($key) ?>"><?= e($key) ?></div>
      <div class="lf-cell lf-en"><?= e($enVal) ?></div>
      <div class="lf-cell" style="padding:8px 10px">
        <textarea
          id="<?= $inputId ?>"
          name="translations[<?= e($key) ?>]"
          class="lf-input<?= $isEmpty ? ' empty' : '' ?>"
          rows="1"
          placeholder="<?= e($enVal) ?>"
          oninput="autoResize(this);updateProgress()"
        ><?= e($current) ?></textarea>
      </div>
    </div>
    <?php endforeach ?>
  </div>
  <?php endforeach ?>

  <!-- Sticky save bar -->
  <div class="lf-sticky-bar">
    <div class="lf-progress">
      <strong id="js-filled"><?= $filled ?></strong> / <?= $total ?> keys translated
      &nbsp;·&nbsp; <span id="js-pct"><?= $pct ?></span>% complete
    </div>
    <div style="display:flex;gap:10px">
      <button type="button" class="btn btn-ghost" onclick="fillFromEnglish()"><span class="material-symbols-outlined mi-sm">bolt</span> Copy English defaults</button>
      <button type="submit" class="btn btn-primary" style="padding:9px 24px"><span class="material-symbols-outlined mi-sm">save</span> Save translations</button>
    </div>
  </div>

</form>

<script>
// Auto-resize textareas
function autoResize(el){
  el.style.height='auto';
  el.style.height=el.scrollHeight+'px';
}
document.querySelectorAll('.lf-input').forEach(function(el){
  el.classList.toggle('empty', el.value.trim()==='');
  autoResize(el);
  el.addEventListener('input',function(){ el.classList.toggle('empty',el.value.trim()===''); });
});

// Progress counter
var totalKeys = <?= $total ?>;
function updateProgress(){
  var filled=0;
  document.querySelectorAll('.lf-input').forEach(function(el){ if(el.value.trim()!=='') filled++; });
  document.getElementById('js-filled').textContent=filled;
  document.getElementById('js-pct').textContent=totalKeys>0?Math.round(filled/totalKeys*100):0;
}

// Fill empty fields with English source
function fillFromEnglish(){
  if(!confirm('Copy English text into all empty fields?')) return;
  document.querySelectorAll('.lf-row').forEach(function(row){
    var ta=row.querySelector('.lf-input');
    var en=row.querySelector('.lf-en');
    if(ta && en && ta.value.trim()===''){
      ta.value=en.textContent.trim();
      ta.classList.remove('empty');
      autoResize(ta);
    }
  });
  updateProgress();
}

// Warn on unsaved changes
var _saved=false;
document.getElementById('langFileForm').addEventListener('submit',function(){ _saved=true; });
window.addEventListener('beforeunload',function(e){
  if(!_saved){
    var changed=false;
    document.querySelectorAll('.lf-input').forEach(function(el){
      if(el.value!==el.defaultValue) changed=true;
    });
    if(changed){ e.preventDefault(); e.returnValue=''; }
  }
});
</script>

<?php endif ?>
