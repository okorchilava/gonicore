<?php
$pageTitle = t('widgets.title');
$activeNav = 'widgets';
$topbarActions = '';

$allWidgets = $widgets ?? [];
$areas      = $widgetAreas ?? [];
$types      = $widgetTypes ?? [];

$byArea = [];
foreach ($areas as $area) { $byArea[$area['slug']] = []; }
foreach ($allWidgets as $w) {
    $slug = (string) $w['area'];
    if (!isset($byArea[$slug])) $byArea[$slug] = [];
    $byArea[$slug][] = $w;
}

$typeIcons = ['html' => 'code', 'text' => 'notes', 'recent-posts' => 'article'];
?>
<style>
.wp-wrap{display:grid;grid-template-columns:260px 1fr;gap:24px;align-items:start}
.wp-sticky{position:sticky;top:80px}
.wp-areas{display:flex;flex-direction:column;gap:16px}
.wp-avail-lbl{font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.8px;color:var(--muted);margin-bottom:10px;padding:0 2px}
.wp-type-item{display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--surface);border:1px solid var(--border);border-radius:8px;margin-bottom:6px;cursor:pointer;transition:border-color .15s,box-shadow .15s;user-select:none}
.wp-type-item:hover{border-color:var(--accent);box-shadow:0 0 0 3px rgba(16,178,124,.08)}
.wp-type-icon{width:34px;height:34px;border-radius:7px;background:var(--bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;color:var(--text);flex-shrink:0;font-family:monospace}
.wp-type-name{font-size:13px;font-weight:600;color:var(--text)}
.wp-type-hint{font-size:11px;color:var(--muted)}
.wp-type-add{font-size:20px;color:var(--accent);font-weight:700;line-height:1;flex-shrink:0}
.wp-area-panel{background:var(--surface);border:1px solid var(--border);border-radius:10px;overflow:hidden}
.wp-area-head{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;background:var(--bg);border-bottom:1px solid var(--border)}
.wp-area-title{font-size:13.5px;font-weight:700;color:var(--text)}
.wp-area-desc{font-size:11.5px;color:var(--muted);margin-top:2px}
.wp-area-cnt{font-size:11px;background:var(--border);color:var(--muted);border-radius:20px;padding:2px 8px;font-weight:600}
.wp-empty{padding:20px 18px;color:var(--muted);font-size:13px;text-align:center;border:2px dashed var(--border);margin:12px;border-radius:8px}
.wp-empty-hint{color:var(--accent);font-size:12px;margin-top:4px}
.wp-list{list-style:none;padding:6px}
.wp-row{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:7px;margin-bottom:4px;background:#fff;border:1px solid var(--border);transition:box-shadow .15s,border-color .15s;cursor:grab}
.wp-row:hover{border-color:#c7d2fe;box-shadow:0 1px 4px rgba(0,0,0,.06)}
.wp-row.dragging{opacity:.4;cursor:grabbing}
.wp-row.drag-over{border-color:var(--accent);box-shadow:0 0 0 2px rgba(16,178,124,.2)}
.wp-handle{color:#cbd5e1;font-size:16px;letter-spacing:-3px;flex-shrink:0;cursor:grab;line-height:1}
.wp-wicon{width:28px;height:28px;border-radius:5px;background:var(--bg);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:11px;font-weight:700;font-family:monospace;color:var(--muted);flex-shrink:0}
.wp-winfo{flex:1;min-width:0}
.wp-wtitle{font-size:13px;font-weight:600;color:var(--text);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.wp-wtype{font-size:11px;color:var(--muted)}
.wp-wactions{display:flex;gap:4px;flex-shrink:0}
.wp-inactive{opacity:.5}
.wp-editor{background:#f8fafc;border:1px solid var(--border);border-radius:8px;margin:2px 10px 6px;padding:14px 16px;display:none}
.wp-editor.open{display:block}
.wp-editor label{font-size:11.5px;font-weight:600;color:var(--muted);display:block;margin-bottom:4px;margin-top:10px}
.wp-editor label:first-child{margin-top:0}
.wp-einput{width:100%;padding:7px 10px;border:1.5px solid var(--border);border-radius:6px;font-size:13px;font-family:inherit;color:var(--text);background:#fff;outline:none;transition:border-color .15s;box-sizing:border-box}
.wp-einput:focus{border-color:var(--accent)}
.wp-etarea{min-height:90px;resize:vertical}
.wp-efooter{display:flex;gap:6px;justify-content:flex-end;margin-top:12px}
.wp-modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:900;align-items:center;justify-content:center}
.wp-modal-bg.open{display:flex}
.wp-modal{background:#fff;border-radius:14px;width:100%;max-width:380px;box-shadow:0 20px 60px rgba(0,0,0,.2);overflow:hidden}
.wp-modal-hdr{padding:18px 22px 14px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}
.wp-modal-hdr h3{font-size:15px;font-weight:700}
.wp-modal-x{background:none;border:none;font-size:20px;cursor:pointer;color:var(--muted);line-height:1}
.wp-modal-body{padding:16px 22px 18px}
.wp-area-opt{display:flex;align-items:center;gap:10px;padding:10px 12px;border:1.5px solid var(--border);border-radius:8px;margin-bottom:8px;cursor:pointer;transition:border-color .15s,background .15s;font-size:13px;font-weight:500}
.wp-area-opt:hover{border-color:var(--accent);background:#f0fdf4}
.wp-area-dot{width:8px;height:8px;border-radius:50%;background:var(--accent);flex-shrink:0}
</style>

<!-- Add-to-area modal -->
<div class="wp-modal-bg" id="addModal">
  <div class="wp-modal">
    <div class="wp-modal-hdr">
      <h3 id="modalTitle">Add Widget</h3>
      <button class="wp-modal-x" onclick="closeModal()">✕</button>
    </div>
    <div class="wp-modal-body">
      <p style="font-size:13px;color:var(--muted);margin-bottom:12px">Choose a widget area:</p>
      <?php foreach ($areas as $area): ?>
      <div class="wp-area-opt" onclick="submitAdd('<?= e($area['slug']) ?>')">
        <span class="wp-area-dot"></span>
        <span><?= e($area['name']) ?></span>
      </div>
      <?php endforeach ?>
    </div>
  </div>
</div>

<div class="wp-wrap">
  <!-- Available Widgets -->
  <div class="wp-sticky">
    <div class="card">
      <div class="card-header"><h3>Available Widgets</h3></div>
      <div class="card-body" style="padding:12px">
        <?php foreach ($types as $t): ?>
        <div class="wp-type-item" onclick="openModal('<?= e($t['slug']) ?>','<?= e(addslashes($t['name'])) ?>')">
          <div class="wp-type-icon"><span class="material-symbols-outlined"><?= htmlspecialchars($typeIcons[$t['slug']] ?? 'widgets', ENT_QUOTES, 'UTF-8') ?></span></div>
          <div style="flex:1;min-width:0">
            <div class="wp-type-name"><?= e($t['name']) ?></div>
            <?php if (!empty($t['fields'])): ?>
            <div class="wp-type-hint"><?= e(implode(', ', array_column($t['fields'], 'label'))) ?></div>
            <?php endif ?>
          </div>
          <span class="wp-type-add">+</span>
        </div>
        <?php endforeach ?>
        <?php if (empty($types)): ?>
        <p style="font-size:13px;color:var(--muted);text-align:center;padding:12px 0">No widget types registered.</p>
        <?php endif ?>
      </div>
    </div>
  </div>

  <!-- Widget Areas -->
  <div class="wp-areas">
    <?php if (empty($areas)): ?>
    <div class="card">
      <div class="card-body" style="text-align:center;padding:40px 24px;color:var(--muted)">
        <div style="font-size:32px;margin-bottom:12px">⊞</div>
        <h3 style="font-size:15px;color:var(--text);margin-bottom:8px">No widget areas registered</h3>
        <p style="font-size:13px">The active theme has not registered any widget areas.<br>Add <code style="background:var(--bg);padding:2px 6px;border-radius:4px;font-size:12px">WidgetService::registerArea()</code> calls to <code style="background:var(--bg);padding:2px 6px;border-radius:4px;font-size:12px">themes/default/functions.php</code>.</p>
      </div>
    </div>
    <?php endif ?>

    <?php foreach ($areas as $area):
      $aw    = $byArea[$area['slug']] ?? [];
      $count = count($aw);
    ?>
    <div class="wp-area-panel">
      <div class="wp-area-head">
        <div>
          <div class="wp-area-title"><?= e($area['name']) ?></div>
          <?php if (!empty($area['description'])): ?>
          <div class="wp-area-desc"><?= e($area['description']) ?></div>
          <?php endif ?>
        </div>
        <span class="wp-area-cnt"><?= $count ?> widget<?= $count !== 1 ? 's' : '' ?></span>
      </div>

      <?php if (empty($aw)): ?>
      <div class="wp-empty">
        No widgets yet.
        <div class="wp-empty-hint">← Click a widget type on the left to add it here.</div>
      </div>
      <?php else: ?>
      <ul class="wp-list" id="list-<?= e($area['slug']) ?>">
        <?php foreach ($aw as $w):
          $settings = is_string($w['settings']) ? (json_decode((string)$w['settings'], true) ?? []) : ($w['settings'] ?? []);
          $typeKey  = array_search($w['type'], array_column($types, 'slug'));
          $wType    = $typeKey !== false ? $types[$typeKey] : null;
          $wFields  = $wType['fields'] ?? [];
          $icon     = $typeIcons[$w['type']] ?? 'widgets';
          $active   = (bool)$w['is_active'];
        ?>
        <li class="wp-row <?= $active ? '' : 'wp-inactive' ?>"
            id="wrow-<?= (int)$w['id'] ?>"
            draggable="true"
            data-id="<?= (int)$w['id'] ?>"
            data-area="<?= e($area['slug']) ?>">
          <span class="wp-handle" title="Drag to reorder">⣿</span>
          <span class="wp-wicon"><span class="material-symbols-outlined mi-sm"><?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?></span></span>
          <div class="wp-winfo">
            <div class="wp-wtitle"><?= e((string)($w['title'] ?: 'Untitled')) ?></div>
            <div class="wp-wtype"><?= e((string)$w['type']) ?></div>
          </div>
          <div class="wp-wactions">
            <button type="button" class="btn btn-ghost" style="font-size:11px;padding:3px 8px"
              onclick="toggleEditor(<?= (int)$w['id'] ?>)">Edit</button>
            <form method="POST" action="<?= e($base) ?>/manage/widgets/<?= (int)$w['id'] ?>/toggle" style="display:inline">
              <button type="submit" class="btn btn-ghost" style="font-size:11px;padding:3px 8px"
                title="<?= $active ? 'Deactivate' : 'Activate' ?>"><?= $active ? '●' : '○' ?></button>
            </form>
            <form method="POST" action="<?= e($base) ?>/manage/widgets/<?= (int)$w['id'] ?>/delete" style="display:inline">
              <button type="button" class="btn btn-danger" style="font-size:11px;padding:3px 8px"
                onclick="gcConfirm(this, <?= e(json_encode(t('widgets.confirm_delete'), JSON_UNESCAPED_UNICODE)) ?>, '', gcI18n.yesDelete, '#ef4444')">✕</button>
            </form>
          </div>
        </li>
        <li id="editor-<?= (int)$w['id'] ?>" style="list-style:none">
          <form method="POST" action="<?= e($base) ?>/manage/widgets/<?= (int)$w['id'] ?>" class="wp-editor">
            <label>Title</label>
            <input type="text" name="title" class="wp-einput"
              value="<?= e((string)($w['title'] ?? '')) ?>" placeholder="Widget title (optional)">
            <?php foreach ($wFields as $f): ?>
            <label><?= e($f['label']) ?></label>
            <?php if ($f['type'] === 'textarea'): ?>
            <textarea name="settings[<?= e($f['name']) ?>]" class="wp-einput wp-etarea"><?= htmlspecialchars((string)($settings[$f['name']] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
            <?php else: ?>
            <input type="text" name="settings[<?= e($f['name']) ?>]" class="wp-einput"
              value="<?= e((string)($settings[$f['name']] ?? '')) ?>">
            <?php endif ?>
            <?php endforeach ?>
            <div class="wp-efooter">
              <button type="button" class="btn btn-ghost" style="font-size:12px"
                onclick="toggleEditor(<?= (int)$w['id'] ?>)">Cancel</button>
              <button type="submit" class="btn btn-primary" style="font-size:12px">Save</button>
            </div>
          </form>
        </li>
        <?php endforeach ?>
      </ul>
      <?php endif ?>
    </div>
    <?php endforeach ?>
  </div>
</div>

<!-- Hidden form for adding a new widget -->
<form method="POST" id="addForm" action="<?= e($base) ?>/manage/widgets" style="display:none">
  <input type="hidden" name="area" id="addArea">
  <input type="hidden" name="type" id="addType">
  <input type="hidden" name="title" value="">
</form>

<script>
var _pendingType = '', _pendingName = '';

function openModal(typeSlug, typeName) {
    _pendingType = typeSlug;
    _pendingName = typeName;
    document.getElementById('modalTitle').textContent = 'Add "' + typeName + '" to…';
    document.getElementById('addModal').classList.add('open');
}
function closeModal() { document.getElementById('addModal').classList.remove('open'); }
function submitAdd(areaSlug) {
    document.getElementById('addArea').value = areaSlug;
    document.getElementById('addType').value = _pendingType;
    document.getElementById('addForm').submit();
}
document.getElementById('addModal').addEventListener('click', function(e){ if(e.target===this) closeModal(); });

function toggleEditor(id) {
    var el = document.getElementById('editor-' + id);
    if (el) el.querySelector('.wp-editor').classList.toggle('open');
}

// Drag-to-reorder
(function(){
    var dragged = null;
    document.querySelectorAll('.wp-row').forEach(function(row){
        row.addEventListener('dragstart', function(e){
            dragged = row;
            setTimeout(function(){ row.classList.add('dragging'); }, 0);
            e.dataTransfer.effectAllowed = 'move';
        });
        row.addEventListener('dragend', function(){
            row.classList.remove('dragging');
            document.querySelectorAll('.wp-row').forEach(function(r){ r.classList.remove('drag-over'); });
            saveOrder(row.dataset.area);
        });
        row.addEventListener('dragover', function(e){
            e.preventDefault();
            if (!dragged || dragged === row || dragged.dataset.area !== row.dataset.area) return;
            row.classList.add('drag-over');
            var list = row.closest('ul');
            var rows = Array.from(list.querySelectorAll('.wp-row'));
            var di = rows.indexOf(dragged), ti = rows.indexOf(row);
            var de = document.getElementById('editor-' + dragged.dataset.id);
            if (di < ti) {
                var te = document.getElementById('editor-' + row.dataset.id);
                list.insertBefore(dragged, row.nextSibling);
                if (de && te) list.insertBefore(de, dragged.nextSibling);
            } else {
                list.insertBefore(dragged, row);
                if (de) list.insertBefore(de, dragged.nextSibling);
            }
        });
        row.addEventListener('dragleave', function(){ row.classList.remove('drag-over'); });
        row.addEventListener('drop', function(e){ e.preventDefault(); row.classList.remove('drag-over'); });
    });

    function saveOrder(areaSlug) {
        var list = document.getElementById('list-' + areaSlug);
        if (!list) return;
        var ids = Array.from(list.querySelectorAll('.wp-row')).map(function(r){ return parseInt(r.dataset.id); });
        fetch('<?= e($base) ?>/api/v1/widgets/reorder', {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ids: ids})
        });
    }
})();
</script>
