<?php
/**
 * GoniBuilder — Full-screen page builder
 * Variables: $page, $base, $builderData, $elementTypes, $elementSchemas, $user, $cats
 */
$pageId      = (int)($page['id'] ?? 0);
$pageTitle   = (string)($page['title'] ?? 'Untitled Page');
$schemasJson = json_encode($elementSchemas ?? []);
$typesJson   = json_encode($elementTypes ?? []);
$initData    = $builderData ?: '{"version":"1.0","sections":[]}';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Goni Builder — <?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.min.css">
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --panel:#1e293b;--panel-d:#0f172a;--panel-border:rgba(255,255,255,.07);
  --accent:#10B27C;--accent-d:#0e9c6c;--danger:#ef4444;
  --text:#0f172a;--muted:#64748b;--border:#e2e8f0;--bg:#f1f5f9;--surface:#fff;
  --topbar:52px;--left:260px;--right:300px;
}
body{font-family:system-ui,-apple-system,'Segoe UI',sans-serif;background:var(--bg);overflow:hidden;height:100vh;display:flex;flex-direction:column}
a{text-decoration:none}

/* ── Top bar ───────────────────────────────────────────────── */
#gb-topbar{
  height:var(--topbar);background:var(--panel-d);border-bottom:1px solid var(--panel-border);
  display:flex;align-items:center;padding:0 16px;gap:12px;flex-shrink:0;z-index:200;
}
#gb-topbar .gb-logo{color:#fff;font-weight:800;font-size:15px;letter-spacing:-.3px;white-space:nowrap}
#gb-topbar .gb-logo span{color:var(--accent)}
#gb-page-title{color:#94a3b8;font-size:13px;margin-left:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;flex:1}
.gb-btn{display:inline-flex;align-items:center;gap:6px;padding:7px 16px;border-radius:7px;font-size:13px;font-weight:600;cursor:pointer;border:none;font-family:inherit;transition:all .15s}
.gb-btn-primary{background:var(--accent);color:#fff}.gb-btn-primary:hover{background:var(--accent-d)}
.gb-btn-ghost{background:rgba(255,255,255,.08);color:#cbd5e1;border:1px solid rgba(255,255,255,.12)}.gb-btn-ghost:hover{background:rgba(255,255,255,.14);color:#fff}
.gb-btn-danger{background:rgba(239,68,68,.15);color:#fca5a5;border:1px solid rgba(239,68,68,.2)}.gb-btn-danger:hover{background:rgba(239,68,68,.25)}
#gb-save-status{font-size:12px;color:#64748b}
#gb-device-btns{display:flex;gap:4px}
.gb-device-btn{padding:5px 10px;border-radius:5px;border:none;background:rgba(255,255,255,.08);color:#94a3b8;cursor:pointer;font-size:14px;transition:all .12s}
.gb-device-btn.active,.gb-device-btn:hover{background:rgba(255,255,255,.16);color:#fff}

/* ── Main layout ───────────────────────────────────────────── */
#gb-main{flex:1;display:flex;overflow:hidden}

/* ── Left panel ────────────────────────────────────────────── */
#gb-left{
  width:var(--left);background:var(--panel);border-right:1px solid var(--panel-border);
  display:flex;flex-direction:column;overflow:hidden;flex-shrink:0;
}
.gb-left-tabs{display:flex;border-bottom:1px solid var(--panel-border)}
.gb-left-tab{flex:1;padding:10px;text-align:center;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.7px;color:#64748b;cursor:pointer;transition:all .12s;border-bottom:2px solid transparent}
.gb-left-tab.active{color:var(--accent);border-color:var(--accent)}
.gb-left-content{flex:1;overflow-y:auto;padding:10px}
.gb-left-content::-webkit-scrollbar{width:4px}.gb-left-content::-webkit-scrollbar-thumb{background:#2d3748;border-radius:4px}

/* Element types */
.gb-cat-label{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:#475569;padding:8px 4px 4px;margin-top:4px}
.gb-elements-grid{display:grid;grid-template-columns:1fr 1fr;gap:6px;margin-bottom:4px}
.gb-el-btn{
  display:flex;flex-direction:column;align-items:center;justify-content:center;gap:4px;
  padding:10px 6px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);
  border-radius:8px;cursor:grab;transition:all .15s;text-align:center;
}
.gb-el-btn:hover{background:rgba(16,178,124,.15);border-color:var(--accent);transform:scale(1.02)}
.gb-el-btn:active{cursor:grabbing}
.gb-el-icon{font-size:20px;line-height:1}
.gb-el-label{font-size:10.5px;font-weight:600;color:#94a3b8}

/* Structure picker */
.gb-structure-grid{display:grid;grid-template-columns:1fr;gap:6px}
.gb-struct-btn{
  display:flex;align-items:center;gap:10px;padding:10px 12px;
  background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.07);
  border-radius:8px;cursor:pointer;transition:all .15s;
}
.gb-struct-btn:hover{background:rgba(16,178,124,.1);border-color:var(--accent)}
.gb-struct-icon{display:flex;gap:3px;flex-shrink:0}
.gb-struct-icon span{background:rgba(255,255,255,.2);border-radius:2px;height:24px}
.gb-struct-label{font-size:12.5px;font-weight:600;color:#94a3b8}

/* ── Canvas ────────────────────────────────────────────────── */
#gb-canvas-wrap{
  flex:1;background:#e2e8f0;overflow:auto;display:flex;justify-content:center;
  align-items:flex-start;       /* let the canvas grow with its content (not stretch to wrap height) */
  padding:20px;transition:background .2s;
}
#gb-canvas{
  background:#fff;width:100%;max-width:1200px;min-height:calc(100vh - var(--topbar) - 40px);
  box-shadow:0 4px 32px rgba(0,0,0,.12);border-radius:6px;transition:max-width .3s;
  position:relative;
}
#gb-canvas.tablet{max-width:768px}
#gb-canvas.mobile{max-width:390px}
#gb-canvas.dark-preview{background:#0f172a}

/* Canvas empty state */
#gb-empty{display:flex;flex-direction:column;align-items:center;justify-content:center;
  padding:80px 24px;text-align:center;color:#94a3b8;min-height:400px}
#gb-empty .gb-empty-icon{font-size:52px;margin-bottom:16px;opacity:.4}
#gb-empty h3{font-size:18px;font-weight:700;color:#cbd5e1;margin-bottom:8px}
#gb-empty p{font-size:13.5px;line-height:1.6;max-width:340px}

/* Section */
.gb-section-wrap{position:relative;border:2px dashed transparent;transition:border-color .15s}
.gb-section-wrap:hover{border-color:rgba(16,178,124,.3)}
.gb-section-wrap.selected{border-color:var(--accent)}
.gb-section-toolbar{
  position:absolute;top:-1px;right:-1px;display:none;flex-direction:row;gap:2px;
  background:var(--accent);border-radius:0 0 0 6px;padding:3px 6px;z-index:10;
}
.gb-section-wrap:hover .gb-section-toolbar,.gb-section-wrap.selected .gb-section-toolbar{display:flex}
.gb-section-action{background:none;border:none;color:#fff;cursor:pointer;font-size:13px;padding:2px 5px;border-radius:3px;transition:background .1s}
.gb-section-action:hover{background:rgba(0,0,0,.2)}

/* Column */
.gb-column-wrap{
  position:relative;border:1px dashed transparent;border-radius:4px;min-height:60px;
  transition:border-color .15s;flex:1;padding:8px;
}
.gb-column-wrap:hover{border-color:rgba(99,102,241,.4)}
.gb-column-wrap.selected{border-color:#6366f1}
.gb-column-wrap.drop-target{background:rgba(16,178,124,.06);border-color:var(--accent)}

/* Element */
.gb-element-wrap{
  position:relative;border:1px dashed transparent;border-radius:4px;margin-bottom:6px;
  transition:border-color .15s;cursor:pointer;
}
.gb-element-wrap:hover{border-color:rgba(16,178,124,.4)}
.gb-element-wrap.selected{border-color:var(--accent);background:rgba(16,178,124,.03)}
.gb-el-toolbar{
  position:absolute;top:-14px;right:4px;display:none;flex-direction:row;gap:1px;
  background:#10B27C;border-radius:4px 4px 0 0;padding:2px 5px;z-index:20;
}
.gb-element-wrap:hover .gb-el-toolbar,.gb-element-wrap.selected .gb-el-toolbar{display:flex}
.gb-el-action{background:none;border:none;color:#fff;cursor:pointer;font-size:11px;padding:1px 4px;border-radius:2px}
.gb-el-action:hover{background:rgba(0,0,0,.2)}

/* Add element placeholder */
.gb-add-el-zone{
  border:2px dashed rgba(16,178,124,.25);border-radius:6px;padding:12px;
  text-align:center;cursor:pointer;transition:all .15s;color:#64748b;font-size:12.5px;
  display:flex;align-items:center;justify-content:center;gap:6px;min-height:44px;
}
.gb-add-el-zone:hover,.gb-add-el-zone.drag-over{border-color:var(--accent);background:rgba(16,178,124,.05);color:var(--accent)}
.gb-add-section-zone{
  border:2px dashed rgba(16,178,124,.2);border-radius:8px;padding:16px;margin:12px;
  text-align:center;cursor:pointer;transition:all .15s;color:#64748b;font-size:13px;
}
.gb-add-section-zone:hover{border-color:var(--accent);background:rgba(16,178,124,.04);color:var(--accent)}

/* ── Right panel ───────────────────────────────────────────── */
#gb-right{
  width:var(--right);background:var(--surface);border-left:1px solid var(--border);
  display:flex;flex-direction:column;overflow:hidden;flex-shrink:0;
}
#gb-right-header{
  padding:12px 16px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;flex-shrink:0;
}
#gb-right-title{font-size:13px;font-weight:700;color:var(--text)}
#gb-right-body{flex:1;overflow-y:auto;padding:14px}
#gb-right-body::-webkit-scrollbar{width:4px}
#gb-right-body::-webkit-scrollbar-thumb{background:#e2e8f0;border-radius:4px}

/* Settings fields */
.gb-field{margin-bottom:14px}
.gb-field label{display:block;font-size:11.5px;font-weight:600;color:var(--muted);margin-bottom:4px;text-transform:uppercase;letter-spacing:.5px}
.gb-field input[type=text],.gb-field input[type=color],.gb-field select,.gb-field textarea{
  width:100%;padding:7px 10px;border:1.5px solid var(--border);border-radius:6px;
  font-size:13px;font-family:inherit;color:var(--text);background:#fff;outline:none;
  transition:border-color .15s;
}
.gb-field input:focus,.gb-field select:focus,.gb-field textarea:focus{border-color:var(--accent)}
.gb-field textarea{min-height:80px;resize:vertical}
.gb-field input[type=color]{height:36px;padding:2px 4px;cursor:pointer}
.gb-field-row{display:grid;grid-template-columns:1fr 1fr;gap:8px}
.gb-toggle{display:flex;align-items:center;justify-content:space-between;padding:6px 0}
.gb-toggle-slider{position:relative;width:36px;height:20px;cursor:pointer}
.gb-toggle-slider input{opacity:0;width:0;height:0;position:absolute}
.gb-toggle-track{position:absolute;inset:0;background:#cbd5e1;border-radius:20px;transition:background .2s}
.gb-toggle-thumb{position:absolute;top:2px;left:2px;width:16px;height:16px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
.gb-toggle-slider input:checked ~ .gb-toggle-track{background:var(--accent)}
.gb-toggle-slider input:checked ~ .gb-toggle-thumb{transform:translateX(16px)}
.gb-section-divider{height:1px;background:var(--border);margin:14px -14px}
.gb-section-group-title{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:10px;padding-top:4px}

/* Empty right panel */
#gb-right-empty{padding:40px 20px;text-align:center;color:var(--muted)}
#gb-right-empty .icon{font-size:32px;margin-bottom:12px}
#gb-right-empty p{font-size:13px;line-height:1.6}

/* Section settings tabs */
.gb-stabs{display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:12px}
.gb-stab{padding:7px 12px;font-size:12px;font-weight:600;color:var(--muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px}
.gb-stab.active{color:var(--accent);border-color:var(--accent)}
.gb-stab-panel{display:none}.gb-stab-panel.active{display:block}

/* Image picker */
.gb-img-picker{border:2px dashed var(--border);border-radius:8px;cursor:pointer;overflow:hidden;transition:border-color .15s;background:var(--bg)}
.gb-img-picker:hover{border-color:var(--accent)}
.gb-img-picker img{width:100%;height:120px;object-fit:cover;display:block}
.gb-img-picker-empty{padding:20px;text-align:center;color:var(--muted);font-size:12.5px}

/* Drag indicator */
.gb-drag-indicator{height:3px;background:var(--accent);border-radius:2px;margin:2px 0;display:none}
.gb-drag-indicator.show{display:block}

/* Builder CSS output */
.gb-page .gb-section{width:100%}
.gb-page .gb-section-inner{display:flex;flex-wrap:wrap;max-width:1200px;margin:0 auto;padding:0 20px}
.gb-page .gb-column{padding:0 12px;box-sizing:border-box}
.gb-page .gb-heading{margin-bottom:.5em}
.gb-page .gb-text{line-height:1.75}
.gb-page .gb-button{margin:8px 0}
.gb-page .gb-image{margin:8px 0}
.gb-page .gb-spacer,.gb-page .gb-divider{margin:4px 0}
.gb-page .gb-icon-box,.gb-page .gb-counter,.gb-page .gb-alert{margin:8px 0}
</style>
</head>
<body>

<!-- TOP BAR -->
<div id="gb-topbar">
  <div class="gb-logo">Goni<span>Builder</span></div>
  <div id="gb-page-title">/ <?= htmlspecialchars($pageTitle, ENT_QUOTES) ?></div>
  <div id="gb-device-btns">
    <button class="gb-device-btn active" onclick="setDevice('desktop')" title="Desktop">🖥</button>
    <button class="gb-device-btn" onclick="setDevice('tablet')" title="Tablet">⬜</button>
    <button class="gb-device-btn" onclick="setDevice('mobile')" title="Mobile">📱</button>
  </div>
  <span id="gb-save-status"></span>
  <button class="gb-btn gb-btn-ghost" onclick="window.open('<?= e($base) ?>/page/<?= e($page['slug'] ?? '') ?>','_blank')">↗ Preview</button>
  <a href="<?= e($base) ?>/manage/pages/<?= $pageId ?>" class="gb-btn gb-btn-ghost">✕ Exit</a>
  <button class="gb-btn gb-btn-primary" onclick="gbSave()">💾 Save</button>
</div>

<!-- MAIN -->
<div id="gb-main">

  <!-- LEFT PANEL -->
  <div id="gb-left">
    <div class="gb-left-tabs">
      <div class="gb-left-tab active" onclick="showLeftTab('elements',this)">Elements</div>
      <div class="gb-left-tab" onclick="showLeftTab('structure',this)">Structure</div>
      <div class="gb-left-tab" onclick="showLeftTab('pages',this)">Navigator</div>
    </div>
    <div class="gb-left-content" id="gb-left-elements">
      <?php
      $grouped = [];
      foreach ($elementTypes as $et) { $grouped[$et['category']][] = $et; }
      foreach ($grouped as $cat => $els): ?>
      <div class="gb-cat-label"><?= htmlspecialchars($cat, ENT_QUOTES) ?></div>
      <div class="gb-elements-grid">
        <?php foreach ($els as $el): ?>
        <div class="gb-el-btn" draggable="true"
             data-type="<?= htmlspecialchars($el['type'], ENT_QUOTES) ?>"
             ondragstart="gbDragStart(event,'element','<?= htmlspecialchars($el['type'], ENT_QUOTES) ?>')"
             onclick="gbAddElementClick('<?= htmlspecialchars($el['type'], ENT_QUOTES) ?>')">
          <div class="gb-el-icon"><?= htmlspecialchars($el['icon'], ENT_QUOTES) ?></div>
          <div class="gb-el-label"><?= htmlspecialchars($el['label'], ENT_QUOTES) ?></div>
        </div>
        <?php endforeach ?>
      </div>
      <?php endforeach ?>
    </div>
    <div class="gb-left-content" id="gb-left-structure" style="display:none">
      <div class="gb-cat-label">Add Section</div>
      <div class="gb-structure-grid">
        <?php
        $structs = [
          ['label'=>'Full Width','cols'=>[100]],
          ['label'=>'Two Columns','cols'=>[50,50]],
          ['label'=>'Three Columns','cols'=>[33,33,34]],
          ['label'=>'Four Columns','cols'=>[25,25,25,25]],
          ['label'=>'70 / 30','cols'=>[70,30]],
          ['label'=>'30 / 70','cols'=>[30,70]],
          ['label'=>'25 / 50 / 25','cols'=>[25,50,25]],
        ];
        foreach ($structs as $st): ?>
        <div class="gb-struct-btn" onclick="gbAddSection(<?= htmlspecialchars(json_encode($st['cols']), ENT_QUOTES) ?>)">
          <div class="gb-struct-icon">
            <?php foreach ($st['cols'] as $w): ?>
            <span style="width:<?= round($w/6) ?>px"></span>
            <?php endforeach ?>
          </div>
          <div class="gb-struct-label"><?= htmlspecialchars($st['label'], ENT_QUOTES) ?></div>
        </div>
        <?php endforeach ?>
      </div>
    </div>
    <div class="gb-left-content" id="gb-left-pages" style="display:none">
      <div class="gb-cat-label">Page Structure</div>
      <div id="gb-navigator" style="font-size:12.5px;color:#94a3b8">No sections yet.</div>
    </div>
  </div>

  <!-- CANVAS -->
  <div id="gb-canvas-wrap">
    <div id="gb-canvas">
      <div id="gb-sections-root"></div>
      <div class="gb-add-section-zone" onclick="gbAddSection([100])">+ Add Section</div>
    </div>
  </div>

  <!-- RIGHT PANEL -->
  <div id="gb-right">
    <div id="gb-right-header">
      <div id="gb-right-title">Properties</div>
      <button id="gb-right-close" style="background:none;border:none;cursor:pointer;color:var(--muted);font-size:18px;line-height:1" onclick="gbDeselect()">✕</button>
    </div>
    <div id="gb-right-body">
      <div id="gb-right-empty">
        <div class="icon">🖱</div>
        <p>Click an element, section or column to edit its properties here.</p>
      </div>
    </div>
  </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.all.min.js"></script>
<script>
// ─────────────────────────────────────────────────────────────────────────────
// GoniBuilder Core
// ─────────────────────────────────────────────────────────────────────────────

var GB = {
  pageId:      <?= $pageId ?>,
  base:        '<?= e($base) ?>',
  csrf:        <?= json_encode((string)($csrfToken ?? '')) ?>,
  schemas:     <?= $schemasJson ?>,
  state:       null,
  selected:    null,   // { type:'element'|'section'|'column', sectionId, colId, elId }
  dragType:    null,
  dragData:    null,
  saveTimer:   null,
  dirty:       false,
};

// HTML-escape a value before injecting into innerHTML / attributes.
function esc(v) {
  return String(v == null ? '' : v)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
    .replace(/"/g,'&quot;').replace(/'/g,'&#39;');
}

// SweetAlert2 toast (bottom-right, auto-dismiss)
function gbToast(message, icon, duration) {
  Swal.fire({
    toast: true, position: 'bottom-end',
    icon: icon || 'success', title: message,
    showConfirmButton: false, timer: duration || 2500,
    timerProgressBar: true, customClass: { popup: 'gc-swal-popup' },
  });
}

// SweetAlert2 confirm — resolves true when confirmed
function gbConfirm(title, text) {
  return Swal.fire({
    title: title, text: text || '',
    icon: 'warning', showCancelButton: true,
    confirmButtonText: 'Delete', cancelButtonText: 'Cancel',
    confirmButtonColor: '#ef4444', cancelButtonColor: '#94a3b8',
    reverseButtons: true, focusCancel: true,
    customClass: { popup: 'gc-swal-popup' },
  }).then(function(r){ return r.isConfirmed; });
}

// ── Init ────────────────────────────────────────────────────────────────────
(function init() {
  try { GB.state = JSON.parse(<?= json_encode($initData) ?>); } catch(e) { GB.state = {version:'1.0',sections:[]}; }
  if (!GB.state.sections) GB.state.sections = [];
  gbRender();
  updateNavigator();
})();

// ── ID generator ─────────────────────────────────────────────────────────────
function gbId(prefix) {
  return prefix + '_' + Math.random().toString(36).substr(2,8);
}

// ── State helpers ─────────────────────────────────────────────────────────────
function findSection(sId)       { return GB.state.sections.find(function(s){ return s.id===sId; }); }
function findColumn(sId,cId)    { var s=findSection(sId); return s ? s.columns.find(function(c){ return c.id===cId; }) : null; }
function findElement(sId,cId,eId){var c=findColumn(sId,cId); return c ? c.elements.find(function(e){ return e.id===eId; }) : null; }

// ── Render ────────────────────────────────────────────────────────────────────
function gbRender() {
  var root = document.getElementById('gb-sections-root');
  root.innerHTML = '';
  var sections = GB.state.sections;

  sections.forEach(function(section, si) {
    root.appendChild(buildSectionEl(section, si));
  });
  updateNavigator();
  updateEmpty();
}

function updateEmpty() {
  var empty = document.getElementById('gb-empty');
  var hasEmpty = !GB.state.sections.length;
  if (!empty && hasEmpty) {
    var e = document.createElement('div');
    e.id = 'gb-empty';
    e.innerHTML = '<div class="gb-empty-icon">🎨</div><h3>Start Building</h3><p>Add a section from the left panel, or click below to start with a full-width section.</p>';
    document.getElementById('gb-canvas').insertBefore(e, document.querySelector('.gb-add-section-zone'));
  } else if (empty && !hasEmpty) {
    empty.remove();
  }
}

function buildSectionEl(section, si) {
  var wrap = document.createElement('div');
  wrap.className = 'gb-section-wrap';
  wrap.dataset.sectionId = section.id;
  if (GB.selected && GB.selected.sectionId === section.id && !GB.selected.elId && !GB.selected.colId) {
    wrap.classList.add('selected');
  }

  // Section background
  var st = section.settings || {};
  var sStyle = '';
  if (st.bg_color) sStyle += 'background-color:' + st.bg_color + ';';
  if (st.bg_image) sStyle += 'background-image:url(\'' + st.bg_image + '\');background-size:cover;background-position:center;';
  sStyle += 'padding:' + (st.padding || '60px 0') + ';';

  // Toolbar
  wrap.innerHTML = '<div class="gb-section-toolbar">' +
    '<button class="gb-section-action" onclick="gbSelectSection(\'' + section.id + '\')" title="Settings">⚙</button>' +
    '<button class="gb-section-action" onclick="gbMoveSection(\'' + section.id + '\',-1)" title="Move Up">↑</button>' +
    '<button class="gb-section-action" onclick="gbMoveSection(\'' + section.id + '\',1)" title="Move Down">↓</button>' +
    '<button class="gb-section-action" onclick="gbDuplicateSection(\'' + section.id + '\')" title="Duplicate">⧉</button>' +
    '<button class="gb-section-action" onclick="gbDeleteSection(\'' + section.id + '\')" title="Delete">✕</button>' +
    '</div>';

  var inner = document.createElement('div');
  inner.style.cssText = sStyle + 'display:flex;flex-wrap:wrap;';
  inner.className = 'gb-section-inner-canvas';

  (section.columns || []).forEach(function(col) {
    inner.appendChild(buildColumnEl(section.id, col));
  });

  wrap.appendChild(inner);

  // Drop zone between sections
  var dropZone = document.createElement('div');
  dropZone.className = 'gb-drag-indicator';
  dropZone.dataset.afterSection = section.id;
  wrap.appendChild(dropZone);

  wrap.addEventListener('click', function(e) {
    if (e.target.closest('.gb-section-action,.gb-element-wrap,.gb-column-wrap')) return;
    gbSelectSection(section.id);
  });

  return wrap;
}

function buildColumnEl(sectionId, col) {
  var w = col.width || 100;
  var wrap = document.createElement('div');
  wrap.className = 'gb-column-wrap';
  wrap.dataset.colId = col.id;
  wrap.dataset.sectionId = sectionId;
  wrap.style.cssText = 'flex:0 0 ' + w + '%;max-width:' + w + '%;';

  if (GB.selected && GB.selected.colId === col.id && !GB.selected.elId) wrap.classList.add('selected');

  (col.elements || []).forEach(function(el) {
    wrap.appendChild(buildElementEl(sectionId, col.id, el));
  });

  // Drop zone — accepts new elements from the panel AND moves of existing ones
  var addZone = document.createElement('div');
  addZone.className = 'gb-add-el-zone';
  addZone.innerHTML = '+ Add Element';
  addZone.addEventListener('click', function() { gbAddElementToCol(sectionId, col.id); });
  addZone.addEventListener('dragover', function(e){ e.preventDefault(); addZone.classList.add('drag-over'); });
  addZone.addEventListener('dragleave', function(){ addZone.classList.remove('drag-over'); });
  addZone.addEventListener('drop', function(e){
    e.preventDefault(); e.stopPropagation(); addZone.classList.remove('drag-over');
    if (GB.dragType === 'element') {
      gbAddElementToColDrop(sectionId, col.id, GB.dragData);
    } else if (GB.dragType === 'move-element') {
      gbMoveElementTo(GB.dragData, sectionId, col.id, null);
    }
    GB.dragType = null; GB.dragData = null;
  });
  wrap.appendChild(addZone);

  wrap.addEventListener('click', function(e){
    if (e.target.closest('.gb-element-wrap,.gb-add-el-zone')) return;
    gbSelectColumn(sectionId, col.id);
  });

  return wrap;
}

function buildElementEl(sectionId, colId, el) {
  var wrap = document.createElement('div');
  wrap.className = 'gb-element-wrap';
  wrap.dataset.elId = el.id;
  wrap.draggable = true;

  if (GB.selected && GB.selected.elId === el.id) wrap.classList.add('selected');

  // Toolbar
  var toolbar = document.createElement('div');
  toolbar.className = 'gb-el-toolbar';
  toolbar.innerHTML =
    '<button class="gb-el-action" onclick="gbMoveEl(\'' + sectionId + '\',\'' + colId + '\',\'' + el.id + '\',-1)" title="Up">↑</button>' +
    '<button class="gb-el-action" onclick="gbMoveEl(\'' + sectionId + '\',\'' + colId + '\',\'' + el.id + '\',1)" title="Down">↓</button>' +
    '<button class="gb-el-action" onclick="gbDuplicateEl(\'' + sectionId + '\',\'' + colId + '\',\'' + el.id + '\')" title="Copy">⧉</button>' +
    '<button class="gb-el-action" onclick="gbDeleteEl(\'' + sectionId + '\',\'' + colId + '\',\'' + el.id + '\')" title="Delete">✕</button>';

  wrap.appendChild(toolbar);

  // Preview content
  var preview = document.createElement('div');
  preview.style.cssText = 'padding:4px;pointer-events:none;';
  preview.innerHTML = gbPreviewElement(el);
  wrap.appendChild(preview);

  wrap.addEventListener('click', function(e){
    e.stopPropagation();
    gbSelectElement(sectionId, colId, el.id);
  });
  wrap.addEventListener('dragstart', function(e){
    e.stopPropagation();
    gbDragStart(e, 'move-element', { sectionId:sectionId, colId:colId, elId:el.id });
  });
  // Dropping ON an element inserts before it (new or moved element)
  wrap.addEventListener('dragover', function(e){
    if (!GB.dragType) return;
    e.preventDefault(); e.stopPropagation();
    wrap.style.boxShadow = '0 -3px 0 0 var(--accent)';
  });
  wrap.addEventListener('dragleave', function(){ wrap.style.boxShadow = ''; });
  wrap.addEventListener('drop', function(e){
    e.preventDefault(); e.stopPropagation();
    wrap.style.boxShadow = '';
    if (GB.dragType === 'element') {
      gbAddElementToColDrop(sectionId, colId, GB.dragData, el.id);
    } else if (GB.dragType === 'move-element') {
      gbMoveElementTo(GB.dragData, sectionId, colId, el.id);
    }
    GB.dragType = null; GB.dragData = null;
  });
  return wrap;
}

// Move an existing element to another column / position.
// beforeElId === null appends to the end of the target column.
function gbMoveElementTo(src, targetSectionId, targetColId, beforeElId) {
  if (!src || src.elId === beforeElId) return;
  var fromCol = findColumn(src.sectionId, src.colId);
  var toCol   = findColumn(targetSectionId, targetColId);
  if (!fromCol || !toCol) return;

  var i = fromCol.elements.findIndex(function(e){ return e.id === src.elId; });
  if (i === -1) return;
  var moved = fromCol.elements.splice(i, 1)[0];

  if (beforeElId) {
    var j = toCol.elements.findIndex(function(e){ return e.id === beforeElId; });
    if (j === -1) toCol.elements.push(moved);
    else toCol.elements.splice(j, 0, moved);
  } else {
    toCol.elements.push(moved);
  }

  GB.selected = { type:'element', sectionId:targetSectionId, colId:targetColId, elId:moved.id };
  markDirty(); gbRender();
}

// Simple preview in canvas — all user values pass through esc()
function gbPreviewElement(el) {
  var s = el.settings || {};
  switch(el.type) {
    case 'heading':
      var tag = /^h[1-6]$/.test(s.tag||'') ? s.tag : 'h2';
      var css = 'text-align:' + esc(s.align||'left') + ';' + (s.color?'color:'+esc(s.color)+';':'') + (s.size?'font-size:'+esc(s.size)+';':'');
      return '<' + tag + ' style="' + css + 'margin:0">' + esc(s.text||'Heading') + '</' + tag + '>';
    case 'text':
      return '<div style="text-align:' + esc(s.align||'left') + ';color:' + esc(s.color||'inherit') + ';font-size:' + esc(s.size||'inherit') + '">' + esc(s.content||'Text').replace(/\n/g,'<br>') + '</div>';
    case 'image':
      if (!s.src) return '<div style="background:#f1f5f9;height:80px;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:12px">🖼 Image</div>';
      return '<div style="text-align:' + esc(s.align||'center') + '"><img src="' + esc(s.src) + '" style="max-width:' + esc(s.width||'100%') + ';border-radius:' + esc(s.radius||'0') + ';max-height:200px;object-fit:cover"></div>';
    case 'button':
      var bColors = s.style==='outline'?'background:transparent;border:2px solid #10B27C;color:#10B27C':s.style==='danger'?'background:#ef4444;color:#fff;border:none':'background:#10B27C;color:#fff;border:none';
      return '<div style="text-align:' + esc(s.align||'left') + '"><span style="' + bColors + ';padding:8px 20px;border-radius:' + esc(s.radius||'8px') + ';display:inline-block;font-weight:600;font-size:14px">' + esc(s.text||'Button') + '</span></div>';
    case 'spacer':
      return '<div style="height:' + esc(s.height||'40px') + ';background:repeating-linear-gradient(45deg,#f8fafc,#f8fafc 4px,#e2e8f0 4px,#e2e8f0 8px);border-radius:4px;display:flex;align-items:center;justify-content:center;color:#94a3b8;font-size:11px">↕ ' + esc(s.height||'40px') + '</div>';
    case 'divider':
      return '<hr style="border:none;border-top:' + esc(s.thickness||'1px') + ' solid ' + esc(s.color||'#e2e8f0') + ';width:' + esc(s.width||'100%') + '">';
    case 'video':
      return '<div style="background:#0f172a;height:100px;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:28px">▶</div>';
    case 'html':
      return '<div style="background:#f8fafc;padding:8px;border-radius:4px;font-size:11.5px;font-family:monospace;color:#475569;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">' + esc(s.code||'HTML') + '</div>';
    case 'icon_box':
      return '<div style="text-align:' + esc(s.align||'left') + '"><div style="font-size:' + esc(s.icon_size||'28px') + '">' + esc(s.icon||'⚡') + '</div><strong style="font-size:14px">' + esc(s.title||'Icon Box') + '</strong></div>';
    case 'counter':
      return '<div style="text-align:' + esc(s.align||'center') + '"><div style="font-size:36px;font-weight:900;color:' + esc(s.color||'#10B27C') + '">' + esc(s.number||'0') + esc(s.suffix||'') + '</div><div style="font-size:12px;color:#64748b">' + esc(s.label||'') + '</div></div>';
    case 'alert':
      var ac = s.style==='success'?'#dcfce7':s.style==='warning'?'#fef9c3':s.style==='danger'?'#fee2e2':'#dbeafe';
      return '<div style="background:' + ac + ';padding:10px 12px;border-radius:6px;font-size:13px">' + esc(s.icon||'ℹ') + ' ' + esc(s.text||'Alert') + '</div>';
    case 'gallery':
      return '<div style="background:#f1f5f9;padding:12px;border-radius:6px;text-align:center;color:#64748b;font-size:12px">🖼 Gallery (' + ((s.images||'').split('\n').filter(function(l){return l.trim()}).length) + ' images)</div>';
    case 'posts_grid':
      return '<div style="background:#f1f5f9;padding:12px;border-radius:6px;text-align:center;color:#64748b;font-size:12px">⊟ Posts Grid (' + esc(s.count||3) + ' posts)</div>';
    case 'slider':
      return '<div style="background:linear-gradient(135deg,#1e293b,#334155);padding:24px;border-radius:8px;text-align:center;color:#94a3b8;font-size:13px"><div style="font-size:28px;margin-bottom:6px">🎞</div>Parallax Slider' + (s.slider_id ? ' #'+esc(s.slider_id) : ' (set ID)') + '</div>';
    case 'ad_zone':
      return '<div style="background:#fef3c7;border:2px dashed #f59e0b;padding:16px;border-radius:8px;text-align:center;color:#92400e;font-size:12.5px"><div style="font-size:24px;margin-bottom:4px">📢</div><strong>Ad Zone</strong>' + (s.slug ? ': <code style="background:#fde68a;padding:2px 6px;border-radius:4px">' + esc(s.slug) + '</code>' : ' <span style="opacity:.6">(zone not set)</span>') + '</div>';
    case 'gccounter':
      return '<div style="background:#eef2ff;border:2px dashed #a5b4fc;padding:16px;border-radius:8px;text-align:center;color:#4338ca;font-size:12.5px"><div style="font-size:24px;margin-bottom:4px">🔢</div><strong>GC Counter</strong>' + (s.group_id ? ' <span style="background:#c7d2fe;padding:2px 8px;border-radius:4px;font-size:11px">group #' + esc(s.group_id) + '</span>' : ' <span style="opacity:.6">(group not set)</span>') + '</div>';
    default:
      return '<div style="padding:8px;color:#64748b;font-size:12px">' + esc(el.type) + '</div>';
  }
}

// ── Selection ─────────────────────────────────────────────────────────────────
function gbSelectSection(sId) {
  GB.selected = { type:'section', sectionId:sId, colId:null, elId:null };
  gbRender();
  showSectionSettings(sId);
}
function gbSelectColumn(sId, cId) {
  GB.selected = { type:'column', sectionId:sId, colId:cId, elId:null };
  gbRender();
  showColumnSettings(sId, cId);
}
function gbSelectElement(sId, cId, eId) {
  GB.selected = { type:'element', sectionId:sId, colId:cId, elId:eId };
  gbRender();
  var el = findElement(sId, cId, eId);
  if (el) showElementSettings(sId, cId, el);
}
function gbDeselect() {
  GB.selected = null;
  gbRender();
  document.getElementById('gb-right-body').innerHTML = '<div id="gb-right-empty"><div class="icon">🖱</div><p>Click an element to edit properties.</p></div>';
  document.getElementById('gb-right-title').textContent = 'Properties';
}

// ── Add Section ───────────────────────────────────────────────────────────────
function gbAddSection(cols) {
  var columns = cols.map(function(w){ return { id:gbId('col'), width:w, elements:[], settings:{} }; });
  var section = { id:gbId('sec'), settings:{ padding:'60px 0', bg_color:'', bg_image:'', full_width:false }, columns:columns };
  GB.state.sections.push(section);
  markDirty();
  gbRender();
  gbSelectSection(section.id);
  // Scroll to new section
  setTimeout(function(){ document.querySelector('[data-section-id="'+section.id+'"]')?.scrollIntoView({behavior:'smooth',block:'center'}); }, 100);
}

// ── Add Element ───────────────────────────────────────────────────────────────
var _pendingAddCol = null;
function gbAddElementToCol(sId, cId) {
  _pendingAddCol = { sId:sId, cId:cId };
  // Show element picker overlay
  Swal.fire({
    title: 'Choose Element',
    html: buildElementPicker(),
    showConfirmButton: false,
    showCloseButton: true,
    customClass: { popup: 'gc-swal-popup' },
    width: 560,
  });
}
function buildElementPicker() {
  var html = '<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:8px;padding:4px">';
  var schemas = GB.schemas;
  Object.keys(schemas).forEach(function(type) {
    html += '<div onclick="gbPickElement(\'' + type + '\')" style="padding:12px;background:#f8fafc;border:1.5px solid #e2e8f0;border-radius:8px;cursor:pointer;text-align:center;transition:all .12s" onmouseover="this.style.borderColor=\'#10B27C\'" onmouseout="this.style.borderColor=\'#e2e8f0\'">' +
      '<div style="font-size:22px;margin-bottom:4px">' + (getElIcon(type)) + '</div>' +
      '<div style="font-size:12px;font-weight:600;color:#475569">' + schemas[type].label + '</div>' +
      '</div>';
  });
  html += '</div>';
  return html;
}
function getElIcon(type) {
  var icons = {heading:'H',text:'T',image:'🖼',button:'⬡',spacer:'↕',divider:'─',video:'▶',gallery:'⊞',icon_box:'⚡',counter:'123',alert:'!',html:'</>',posts_grid:'⊟',slider:'🎞',ad_zone:'📢',gccounter:'🔢'};
  return icons[type] || '?';
}
function gbPickElement(type) {
  Swal.close();
  if (!_pendingAddCol) return;
  gbAddElementToColDrop(_pendingAddCol.sId, _pendingAddCol.cId, type);
  _pendingAddCol = null;
}
function gbAddElementClick(type) {
  // Add to last selected column or first section's first column
  if (GB.selected && GB.selected.colId) {
    gbAddElementToColDrop(GB.selected.sectionId, GB.selected.colId, type);
  } else if (GB.selected && GB.selected.sectionId) {
    var sec = findSection(GB.selected.sectionId);
    if (sec && sec.columns.length) gbAddElementToColDrop(sec.id, sec.columns[0].id, type);
  } else if (GB.state.sections.length) {
    var s = GB.state.sections[0];
    gbAddElementToColDrop(s.id, s.columns[0].id, type);
  } else {
    gbAddSection([100]);
    setTimeout(function() {
      var s = GB.state.sections[0];
      gbAddElementToColDrop(s.id, s.columns[0].id, type);
    }, 50);
  }
}
function gbAddElementToColDrop(sId, cId, type, beforeElId) {
  var col = findColumn(sId, cId);
  if (!col) return;
  var schema = GB.schemas[type];
  var settings = {};
  if (schema) schema.fields.forEach(function(f){ settings[f.name] = f.default; });
  var el = { id:gbId('el'), type:type, settings:settings };
  if (beforeElId) {
    var j = col.elements.findIndex(function(e){ return e.id === beforeElId; });
    if (j === -1) col.elements.push(el); else col.elements.splice(j, 0, el);
  } else {
    col.elements.push(el);
  }
  markDirty();
  gbRender();
  gbSelectElement(sId, cId, el.id);
}

// ── Move / Delete ─────────────────────────────────────────────────────────────
function gbMoveSection(sId, dir) {
  var arr = GB.state.sections;
  var i   = arr.findIndex(function(s){ return s.id===sId; });
  if (i+dir < 0 || i+dir >= arr.length) return;
  arr.splice(i+dir, 0, arr.splice(i, 1)[0]);
  markDirty(); gbRender();
}
function gbDeleteSection(sId) {
  gbConfirm('Delete section?', 'The section and all its content will be removed.').then(function(ok){
    if (!ok) return;
    GB.state.sections = GB.state.sections.filter(function(s){ return s.id!==sId; });
    if (GB.selected && GB.selected.sectionId===sId) GB.selected = null;
    markDirty(); gbRender(); gbDeselect();
  });
}
function gbDuplicateSection(sId) {
  var src = findSection(sId);
  if (!src) return;
  var copy = JSON.parse(JSON.stringify(src));
  copy.id = gbId('sec');
  copy.columns.forEach(function(c){ c.id=gbId('col'); c.elements.forEach(function(e){ e.id=gbId('el'); }); });
  var i = GB.state.sections.findIndex(function(s){ return s.id===sId; });
  GB.state.sections.splice(i+1, 0, copy);
  markDirty(); gbRender();
}
function gbMoveEl(sId, cId, eId, dir) {
  var col = findColumn(sId, cId);
  if (!col) return;
  var i = col.elements.findIndex(function(e){ return e.id===eId; });
  if (i+dir < 0 || i+dir >= col.elements.length) return;
  col.elements.splice(i+dir, 0, col.elements.splice(i, 1)[0]);
  markDirty(); gbRender();
}
function gbDeleteEl(sId, cId, eId) {
  gbConfirm('Delete element?').then(function(ok){
    if (!ok) return;
    var col = findColumn(sId, cId);
    if (!col) return;
    col.elements = col.elements.filter(function(e){ return e.id!==eId; });
    if (GB.selected && GB.selected.elId===eId) GB.selected = null;
    markDirty(); gbRender(); gbDeselect();
  });
}
function gbDuplicateEl(sId, cId, eId) {
  var col = findColumn(sId, cId);
  if (!col) return;
  var i   = col.elements.findIndex(function(e){ return e.id===eId; });
  var copy = JSON.parse(JSON.stringify(col.elements[i]));
  copy.id  = gbId('el');
  col.elements.splice(i+1, 0, copy);
  markDirty(); gbRender();
}

// ── Settings panels ────────────────────────────────────────────────────────────
function showSectionSettings(sId) {
  var sec = findSection(sId);
  if (!sec) return;
  var st  = sec.settings || {};
  document.getElementById('gb-right-title').textContent = 'Section';
  var html = '<div class="gb-stabs"><div class="gb-stab active" onclick="gbStab(this,\'layout\')">Layout</div><div class="gb-stab" onclick="gbStab(this,\'style\')">Style</div></div>' +
    '<div class="gb-stab-panel active" id="gbstab-layout">' +
    field('Padding', 'text', 'sec_' + sId + '_padding', st.padding||'60px 0') +
    '<div class="gb-field"><label>Full Width</label>' + toggleField('sec_' + sId + '_full_width', !!st.full_width) + '</div>' +
    '</div>' +
    '<div class="gb-stab-panel" id="gbstab-style">' +
    field('Background Color', 'color', 'sec_' + sId + '_bg_color', st.bg_color||'#ffffff') +
    '<div class="gb-field"><label>Background Image URL</label><div class="gb-img-picker" onclick="gbOpenImgPicker(\'sec_\'+\'' + sId + '\'+ \'_bg_image\')">' +
    (st.bg_image ? '<img src="' + st.bg_image + '">' : '<div class="gb-img-picker-empty">🖼 Click to select image</div>') +
    '</div><input type="hidden" id="sec_' + sId + '_bg_image" value="' + (st.bg_image||'') + '"></div>' +
    '</div>';

  document.getElementById('gb-right-body').innerHTML = html;
  document.getElementById('gb-right-body').querySelectorAll('input,select,textarea').forEach(function(el){
    el.addEventListener('input',  function(){ gbSectionSettingChanged(sId); });
    el.addEventListener('change', function(){ gbSectionSettingChanged(sId); });
  });
}

function showColumnSettings(sId, cId) {
  var col = findColumn(sId, cId);
  if (!col) return;
  var st = col.settings || {};
  document.getElementById('gb-right-title').textContent = 'Column';
  document.getElementById('gb-right-body').innerHTML =
    field('Width (%)', 'text', 'col_width', col.width || 100) +
    field('Custom Padding', 'text', 'col_padding', st.padding||'') +
    '<p style="font-size:11.5px;color:var(--muted);margin-top:4px">Width is a percentage of the section (1–100). Sibling columns are not adjusted automatically.</p>';

  var wEl = document.getElementById('col_width');
  var pEl = document.getElementById('col_padding');
  function apply() {
    var w = parseInt(wEl.value, 10);
    if (!isNaN(w)) col.width = Math.max(1, Math.min(100, w));
    col.settings = col.settings || {};
    col.settings.padding = pEl.value;
    markDirty(); gbRender();
  }
  wEl.addEventListener('change', apply);
  pEl.addEventListener('change', apply);
}

function showElementSettings(sId, cId, el) {
  var schema = GB.schemas[el.type];
  if (!schema) return;
  document.getElementById('gb-right-title').textContent = schema.label;
  var html = '';
  schema.fields.forEach(function(f) {
    var val = el.settings[f.name] !== undefined ? el.settings[f.name] : f.default;
    if (f.type === 'toggle') {
      html += '<div class="gb-field"><label>' + esc(f.label) + '</label>' + toggleField('el_' + el.id + '_' + f.name, val === '1' || val === true) + '</div>';
    } else if (f.type === 'image') {
      html += '<div class="gb-field"><label>' + esc(f.label) + '</label>' +
        '<div class="gb-img-picker" onclick="gbOpenImgPicker(\'el_' + el.id + '_' + f.name + '\')">' +
        (val ? '<img src="' + esc(val) + '">' : '<div class="gb-img-picker-empty">🖼 Click to select image</div>') +
        '</div>' +
        (val ? '<button type="button" onclick="gbClearImg(\'el_' + el.id + '_' + f.name + '\')" style="margin-top:6px;background:none;border:1px solid var(--border);border-radius:6px;padding:4px 10px;font-size:11.5px;color:var(--muted);cursor:pointer;font-family:inherit">✕ Remove image</button>' : '') +
        '<input type="hidden" id="el_' + el.id + '_' + f.name + '" value="' + esc(val) + '"></div>';
    } else if (f.type === 'select') {
      var opts = Object.entries(f.options||{}).map(function(e){ return '<option value="' + esc(e[0]) + '"' + (val==e[0]?' selected':'') + '>' + esc(e[1]) + '</option>'; }).join('');
      html += '<div class="gb-field"><label>' + esc(f.label) + '</label><select id="el_' + el.id + '_' + f.name + '">' + opts + '</select></div>';
    } else if (f.type === 'code' || f.type === 'textarea') {
      html += '<div class="gb-field"><label>' + esc(f.label) + '</label><textarea id="el_' + el.id + '_' + f.name + '" rows="4">' + esc(val) + '</textarea></div>';
    } else {
      html += field(f.label, f.type === 'color' ? 'color' : 'text', 'el_' + el.id + '_' + f.name, val);
    }
  });
  document.getElementById('gb-right-body').innerHTML = html;
  document.getElementById('gb-right-body').querySelectorAll('input,select,textarea').forEach(function(el2){
    el2.addEventListener('input',  function(){ gbElSettingChanged(sId, cId, el.id); });
    el2.addEventListener('change', function(){ gbElSettingChanged(sId, cId, el.id); });
  });
}

function field(label, type, id, value) {
  var v = (value||'').toString().replace(/"/g,'&quot;');
  return '<div class="gb-field"><label>' + label + '</label><input type="' + type + '" id="' + id + '" value="' + v + '"></div>';
}
function toggleField(id, checked) {
  return '<label class="gb-toggle-slider"><input type="checkbox" id="' + id + '"' + (checked?' checked':'') + '><div class="gb-toggle-track"></div><div class="gb-toggle-thumb"></div></label>';
}

function gbStab(el, name) {
  el.closest('.gb-stabs').querySelectorAll('.gb-stab').forEach(function(t){ t.classList.remove('active'); });
  document.querySelectorAll('.gb-stab-panel').forEach(function(p){ p.classList.remove('active'); });
  el.classList.add('active');
  document.getElementById('gbstab-' + name)?.classList.add('active');
}

function gbSectionSettingChanged(sId) {
  var sec = findSection(sId);
  if (!sec) return;
  sec.settings = sec.settings || {};
  var pEl = document.getElementById('sec_' + sId + '_padding');
  var bgEl = document.getElementById('sec_' + sId + '_bg_color');
  var bgImgEl = document.getElementById('sec_' + sId + '_bg_image');
  var fwEl = document.getElementById('sec_' + sId + '_full_width');
  if (pEl)    sec.settings.padding   = pEl.value;
  if (bgEl)   sec.settings.bg_color  = bgEl.value === '#ffffff' ? '' : bgEl.value;
  if (bgImgEl) sec.settings.bg_image = bgImgEl.value;
  if (fwEl)   sec.settings.full_width = fwEl.checked;
  markDirty(); gbRender();
}

function gbElSettingChanged(sId, cId, eId) {
  var el = findElement(sId, cId, eId);
  if (!el) return;
  var schema = GB.schemas[el.type];
  if (!schema) return;
  schema.fields.forEach(function(f) {
    var inputEl = document.getElementById('el_' + eId + '_' + f.name);
    if (!inputEl) return;
    if (f.type === 'toggle') {
      el.settings[f.name] = inputEl.checked ? '1' : '0';
    } else {
      el.settings[f.name] = inputEl.value;
    }
  });
  markDirty(); gbRender();
}

// Image picker via gallery
function gbOpenImgPicker(fieldId) {
  window._gbImgField = fieldId;
  Swal.fire({
    title: 'Select Image', width: 860,
    html: '<div id="gc-gal-wrap"><div style="margin-bottom:12px"><input type="text" id="gc-gal-search" placeholder="Search..." oninput="gbGalFilter()" style="padding:7px 12px;border:1.5px solid #e2e8f0;border-radius:7px;font-size:13px;width:100%;outline:none"></div><div id="gc-gal-grid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(100px,1fr));gap:8px;max-height:55vh;overflow-y:auto;padding:2px"><div style="grid-column:1/-1;text-align:center;padding:40px;color:#64748b">Loading...</div></div></div>',
    showConfirmButton:false,showCloseButton:true,customClass:{popup:'gc-swal-popup'},
    didOpen:function(){ gbLoadGallery(); }
  });
}
function gbLoadGallery() {
  fetch(GB.base + '/manage/gallery/json').then(function(r){return r.json();}).then(function(data){
    window._gbGalItems = data.media || [];
    gbGalRender(data.media || []);
  }).catch(function(){ document.getElementById('gc-gal-grid').innerHTML='<div style="grid-column:1/-1;text-align:center;color:#ef4444;padding:24px">Failed to load.</div>'; });
}
function gbGalRender(items) {
  var grid = document.getElementById('gc-gal-grid');
  if (!grid) return;
  if (!items.length) { grid.innerHTML = '<div style="grid-column:1/-1;text-align:center;padding:40px;color:#64748b">No media found.</div>'; return; }
  grid.innerHTML = items.map(function(m) {
    var url = GB.base + '/storage/media/' + m.path;
    var isImg = (m.mime_type||'').startsWith('image/');
    if (!isImg) return '';
    return '<div onclick="gbPickGalImg(\'' + url + '\')" style="aspect-ratio:1;border-radius:6px;overflow:hidden;border:2px solid transparent;cursor:pointer;transition:all .12s" onmouseover="this.style.borderColor=\'#10B27C\'" onmouseout="this.style.borderColor=\'transparent\'"><img src="' + url + '" style="width:100%;height:100%;object-fit:cover" loading="lazy"></div>';
  }).join('');
}
function gbGalFilter() {
  var q = (document.getElementById('gc-gal-search').value||'').toLowerCase();
  gbGalRender((window._gbGalItems||[]).filter(function(m){ return !q||m.original_name.toLowerCase().includes(q); }));
}
function gbPickGalImg(url) {
  var fieldId = window._gbImgField;
  if (!fieldId) return;
  var el = document.getElementById(fieldId);
  if (el) {
    el.value = url;
    el.dispatchEvent(new Event('input'));
    // Update picker preview
    var picker = el.parentElement?.querySelector('.gb-img-picker');
    if (picker) picker.innerHTML = '<img src="' + url + '">';
  }
  // If it's a section bg
  if (GB.selected && GB.selected.sectionId && fieldId.startsWith('sec_')) {
    var sId = GB.selected.sectionId;
    gbSectionSettingChanged(sId);
  } else if (GB.selected && GB.selected.elId) {
    if (GB.selected.elId) gbElSettingChanged(GB.selected.sectionId, GB.selected.colId, GB.selected.elId);
  }
  Swal.close();
}

// ── Drag & Drop ───────────────────────────────────────────────────────────────
function gbDragStart(e, type, data) {
  GB.dragType = type;
  GB.dragData = data;
  e.dataTransfer.effectAllowed = 'move';
}

// ── Navigator ─────────────────────────────────────────────────────────────────
function updateNavigator() {
  var nav = document.getElementById('gb-navigator');
  if (!nav) return;
  if (!GB.state.sections.length) { nav.innerHTML = '<div style="color:#475569;font-size:12px">No sections.</div>'; return; }
  nav.innerHTML = GB.state.sections.map(function(s, si) {
    var cols = (s.columns||[]).map(function(c, ci) {
      var els = (c.elements||[]).map(function(e) {
        return '<div onclick="gbSelectElement(\'' + s.id + '\',\'' + c.id + '\',\'' + e.id + '\')" style="padding:3px 6px 3px 20px;cursor:pointer;border-radius:4px;color:#64748b;font-size:11.5px" onmouseover="this.style.background=\'#f1f5f9\'" onmouseout="this.style.background=\'\'">▸ ' + e.type + '</div>';
      }).join('');
      return '<div style="padding-left:12px">' + els + '</div>';
    }).join('');
    return '<div onclick="gbSelectSection(\'' + s.id + '\')" style="padding:5px 6px;cursor:pointer;border-radius:4px;font-weight:600;color:#cbd5e1;font-size:12px;margin-bottom:2px" onmouseover="this.style.background=\'#1e293b\'" onmouseout="this.style.background=\'\'">▸ Section ' + (si+1) + '</div>' + cols;
  }).join('');
}

// ── Device preview ────────────────────────────────────────────────────────────
function setDevice(d) {
  document.querySelectorAll('.gb-device-btn').forEach(function(b){ b.classList.remove('active'); });
  event.target.classList.add('active');
  var canvas = document.getElementById('gb-canvas');
  canvas.classList.remove('tablet','mobile');
  if (d==='tablet') canvas.classList.add('tablet');
  if (d==='mobile') canvas.classList.add('mobile');
}

// ── Left tabs ─────────────────────────────────────────────────────────────────
function showLeftTab(name, el) {
  document.querySelectorAll('.gb-left-tab').forEach(function(t){ t.classList.remove('active'); });
  document.querySelectorAll('.gb-left-content').forEach(function(c){ c.style.display='none'; });
  el.classList.add('active');
  document.getElementById('gb-left-' + name).style.display = '';
}

// ── Dirty / Save ───────────────────────────────────────────────────────────────
function markDirty() {
  GB.dirty = true;
  document.getElementById('gb-save-status').textContent = 'Unsaved changes';
  document.getElementById('gb-save-status').style.color = '#f59e0b';
  clearTimeout(GB.saveTimer);
  GB.saveTimer = setTimeout(gbAutoSave, 10000);
}
function gbAutoSave() { if (GB.dirty) gbSave(); }

function gbSave() {
  var btn = document.querySelector('.gb-btn-primary');
  var statusEl = document.getElementById('gb-save-status');
  if (btn) { btn.textContent = '⏳ Saving…'; btn.disabled = true; }

  fetch(GB.base + '/goni-builder/' + GB.pageId + '/save', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': GB.csrf },
    body: JSON.stringify({ use_builder: 1, builder_data: GB.state, _csrf: GB.csrf })
  })
  .then(function(r){ return r.json().catch(function(){ return { ok: r.ok }; }); })
  .then(function(data) {
    if (btn) { btn.textContent = '💾 Save'; btn.disabled = false; }
    if (data && data.ok) {
      GB.dirty = false;
      statusEl.textContent = 'Saved ✓';
      statusEl.style.color = '#10B27C';
      setTimeout(function(){ statusEl.textContent=''; }, 3000);
    } else {
      statusEl.textContent = data && data.error === 'csrf' ? 'Session expired — reload' : 'Save failed!';
      statusEl.style.color = '#ef4444';
    }
  })
  .catch(function() {
    if (btn) { btn.textContent = '💾 Save'; btn.disabled = false; }
    statusEl.textContent = 'Save failed!';
    statusEl.style.color = '#ef4444';
  });
}

window.addEventListener('beforeunload', function(e){ if (GB.dirty){ e.preventDefault(); e.returnValue=''; } });
window.addEventListener('keydown', function(e){ if ((e.ctrlKey||e.metaKey) && e.key==='s'){ e.preventDefault(); gbSave(); } });
</script>
</body>
</html>
