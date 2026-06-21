<?php
$pageTitle    = t('menus.title');
$activeNav    = 'menus';
$topbarActions = '';
$menuList     = $menus ?? [];
$locDefs      = $locations ?? [];   // slug => label
$locAssigned  = $assigned  ?? [];   // slug => menu_id|null
$activeId     = (int)($activeMenuId ?? 0);
$curMenu      = $activeMenu ?? null;
$items        = $activeItems ?? [];

// Build item tree (flat → nested for display)
$itemMap = [];
foreach ($items as $it) { $itemMap[(int)$it['id']] = $it; }

// Pages/posts/cats for "add items" panel
$pageList = $pages ?? [];
$postList = $posts ?? [];
$catList  = $cats  ?? [];
?>

<style>
.menu-layout{display:grid;grid-template-columns:300px 1fr;gap:20px;align-items:start}
.menu-tab-bar{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;align-items:center}
.menu-tab{padding:7px 16px;border-radius:7px;border:1.5px solid var(--border);font-size:13px;font-weight:500;color:var(--text);text-decoration:none;transition:all .15s;cursor:pointer;background:var(--surface)}
.menu-tab:hover{border-color:var(--accent);color:var(--accent);text-decoration:none}
.menu-tab.active{background:var(--accent);color:#fff;border-color:var(--accent)}
.menu-item-row{display:flex;align-items:center;gap:8px;padding:10px 12px;border:1px solid var(--border);border-radius:8px;margin-bottom:6px;background:#fff;transition:border-color .15s;cursor:grab}
.menu-item-row:hover{border-color:#c7d2fe}
.menu-item-row.dragging{opacity:.4}
.menu-item-row.drag-over{border-color:var(--accent);box-shadow:0 0 0 2px rgba(16,178,124,.15)}
.menu-item-drag{color:#cbd5e1;font-size:16px;letter-spacing:-3px;flex-shrink:0;line-height:1}
.menu-item-info{flex:1;min-width:0}
.menu-item-label{font-size:13.5px;font-weight:600;color:var(--text)}
.menu-item-meta{font-size:11px;color:var(--muted);margin-top:1px}
.menu-item-actions{display:flex;gap:4px;flex-shrink:0}
.menu-item-editor{background:#f8fafc;border:1px solid var(--border);border-radius:8px;padding:14px;margin:-2px 0 8px 28px;display:none}
.menu-item-editor.open{display:block}
.menu-indent{margin-left:28px;border-left:2px solid var(--border);padding-left:10px}
.add-items-tabs{display:flex;gap:0;border-bottom:1px solid var(--border);margin-bottom:12px}
.add-tab{padding:7px 14px;font-size:12.5px;font-weight:600;color:var(--muted);cursor:pointer;border-bottom:2px solid transparent;margin-bottom:-1px;transition:color .12s,border-color .12s}
.add-tab.active{color:var(--accent);border-color:var(--accent)}
.add-panel{display:none}.add-panel.active{display:block}
.add-check-list{max-height:180px;overflow-y:auto;border:1px solid var(--border);border-radius:6px;margin-bottom:8px}
.add-check-item{display:flex;align-items:center;gap:8px;padding:7px 10px;font-size:13px;border-bottom:1px solid var(--border);cursor:pointer}
.add-check-item:last-child{border-bottom:none}
.add-check-item:hover{background:var(--bg)}
.loc-row{display:flex;align-items:center;gap:10px;padding:10px 0;border-bottom:1px solid var(--border);font-size:13px}
.loc-row:last-child{border-bottom:none}
.loc-name{width:160px;font-weight:600}
.loc-desc{font-size:11.5px;color:var(--muted);flex:1}
</style>

<div class="menu-layout">

  <!-- LEFT: Add items + Locations -->
  <div style="display:flex;flex-direction:column;gap:16px">

    <?php if ($curMenu): ?>
    <!-- Add items panel -->
    <div class="card">
      <div class="card-header"><h3>Add Menu Items</h3></div>
      <div class="card-body" style="padding:14px">

        <div class="add-items-tabs">
          <div class="add-tab active" onclick="showAddTab('custom',this)">Custom Link</div>
          <div class="add-tab" onclick="showAddTab('page',this)">Pages</div>
          <div class="add-tab" onclick="showAddTab('post',this)">Posts</div>
          <div class="add-tab" onclick="showAddTab('category',this)">Categories</div>
        </div>

        <!-- Custom link -->
        <form method="POST" action="<?= e($base) ?>/manage/menus/<?= $activeId ?>/items/add" class="add-panel active" id="add-custom">
          <input type="hidden" name="type" value="custom">
          <div style="margin-bottom:8px">
            <label style="font-size:11.5px;font-weight:600;color:var(--muted);display:block;margin-bottom:3px">Label</label>
            <input type="text" name="label" class="form-input" style="padding:7px 10px;font-size:13px" placeholder="Link text" required>
          </div>
          <div style="margin-bottom:8px">
            <label style="font-size:11.5px;font-weight:600;color:var(--muted);display:block;margin-bottom:3px">URL</label>
            <input type="text" name="url" class="form-input" style="padding:7px 10px;font-size:13px" placeholder="https://" required>
          </div>
          <label style="display:flex;align-items:center;gap:6px;font-size:12.5px;margin-bottom:10px;cursor:pointer">
            <input type="checkbox" name="target" value="_blank"> Open in new tab
          </label>
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;font-size:12.5px">Add to Menu</button>
        </form>

        <!-- Pages -->
        <form method="POST" action="<?= e($base) ?>/manage/menus/<?= $activeId ?>/items/add" class="add-panel" id="add-page">
          <input type="hidden" name="type" value="page">
          <div class="add-check-list">
            <?php foreach ($pageList as $pg): ?>
            <label class="add-check-item">
              <input type="checkbox" name="object_ids[]" value="<?= (int)$pg['id'] ?>">
              <?= e($pg['title']) ?>
            </label>
            <?php endforeach ?>
            <?php if (empty($pageList)): ?><div style="padding:12px;color:var(--muted);font-size:13px">No published pages.</div><?php endif ?>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;font-size:12.5px">Add Selected</button>
        </form>

        <!-- Posts -->
        <form method="POST" action="<?= e($base) ?>/manage/menus/<?= $activeId ?>/items/add" class="add-panel" id="add-post">
          <input type="hidden" name="type" value="post">
          <div class="add-check-list">
            <?php foreach ($postList as $pt): ?>
            <label class="add-check-item">
              <input type="checkbox" name="object_ids[]" value="<?= (int)$pt['id'] ?>">
              <?= e($pt['title']) ?>
            </label>
            <?php endforeach ?>
            <?php if (empty($postList)): ?><div style="padding:12px;color:var(--muted);font-size:13px">No published posts.</div><?php endif ?>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;font-size:12.5px">Add Selected</button>
        </form>

        <!-- Categories -->
        <form method="POST" action="<?= e($base) ?>/manage/menus/<?= $activeId ?>/items/add" class="add-panel" id="add-category">
          <input type="hidden" name="type" value="category">
          <div class="add-check-list">
            <?php foreach ($catList as $c): ?>
            <label class="add-check-item">
              <input type="checkbox" name="object_ids[]" value="<?= (int)$c['id'] ?>">
              <?= e($c['name']) ?>
            </label>
            <?php endforeach ?>
            <?php if (empty($catList)): ?><div style="padding:12px;color:var(--muted);font-size:13px">No categories.</div><?php endif ?>
          </div>
          <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;font-size:12.5px">Add Selected</button>
        </form>

      </div>
    </div>
    <?php endif ?>

    <!-- Menu Locations -->
    <div class="card">
      <div class="card-header"><h3>Menu Locations</h3></div>
      <div class="card-body" style="padding:16px">
        <?php if (empty($locDefs)): ?>
        <p style="font-size:13px;color:var(--muted)">No locations registered by the theme.</p>
        <?php else: ?>
        <form method="POST" action="<?= e($base) ?>/manage/menus/assign-locations">
          <?php foreach ($locDefs as $locSlug => $locName): ?>
          <div class="loc-row">
            <div class="loc-name"><?= e($locName) ?></div>
            <select name="location_<?= e($locSlug) ?>" class="form-select" style="flex:1">
              <option value="">— Not assigned —</option>
              <?php foreach ($menuList as $m): ?>
              <option value="<?= (int)$m['id'] ?>"
                <?= ((int)($locAssigned[$locSlug] ?? 0) === (int)$m['id']) ? 'selected' : '' ?>>
                <?= e($m['name']) ?>
              </option>
              <?php endforeach ?>
            </select>
          </div>
          <?php endforeach ?>
          <div style="margin-top:14px">
            <button type="submit" class="btn btn-primary" style="font-size:12.5px">Save Locations</button>
          </div>
        </form>
        <?php endif ?>
      </div>
    </div>

  </div>

  <!-- RIGHT: Menu editor -->
  <div>

    <!-- Menu tabs + create -->
    <div class="menu-tab-bar">
      <?php foreach ($menuList as $m): ?>
      <a href="?menu=<?= (int)$m['id'] ?>"
         class="menu-tab <?= (int)$m['id'] === $activeId ? 'active' : '' ?>">
        <?= e($m['name']) ?>
      </a>
      <?php endforeach ?>
      <button type="button" class="btn btn-ghost" style="font-size:12.5px;padding:6px 14px"
        onclick="document.getElementById('createMenuWrap').classList.toggle('hidden')">+ New Menu</button>
    </div>

    <!-- Create menu inline form -->
    <div id="createMenuWrap" class="hidden card" style="margin-bottom:16px;padding:14px">
      <form method="POST" action="<?= e($base) ?>/manage/menus/create" style="display:flex;gap:8px">
        <input type="text" name="name" class="form-input" style="flex:1;padding:8px 12px;font-size:13px" placeholder="Menu name" required>
        <button type="submit" class="btn btn-primary" style="font-size:13px">Create</button>
      </form>
    </div>

    <?php if ($curMenu): ?>
    <!-- Menu name + delete -->
    <div class="card" style="margin-bottom:16px">
      <div class="card-header">
        <form method="POST" action="<?= e($base) ?>/manage/menus/<?= $activeId ?>/rename"
              style="display:flex;gap:8px;flex:1;align-items:center">
          <input type="text" name="name" class="form-input" style="flex:1;padding:7px 12px;font-size:14px;font-weight:700"
                 value="<?= e($curMenu['name']) ?>">
          <button type="submit" class="btn btn-ghost" style="font-size:12.5px">Rename</button>
        </form>
        <form method="POST" action="<?= e($base) ?>/manage/menus/<?= $activeId ?>/delete" style="margin-left:8px">
          <button type="button" class="btn btn-danger" style="font-size:12.5px;padding:6px 12px"
            onclick="gcConfirm(this, <?= e(json_encode(t('menus.confirm_delete'), JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode(t('admin.cannot_undo'), JSON_UNESCAPED_UNICODE)) ?>, <?= e(json_encode(t('admin.yes_delete'), JSON_UNESCAPED_UNICODE)) ?>)"><?= e(t('admin.delete')) ?></button>
        </form>
      </div>
    </div>

    <!-- Items list -->
    <div class="card">
      <div class="card-header"><h3>Menu Items</h3>
        <span style="font-size:12px;color:var(--muted)"><?= count($items) ?> items — drag to reorder</span>
      </div>
      <div class="card-body" style="padding:12px">
        <?php if (empty($items)): ?>
        <div style="text-align:center;padding:32px;color:var(--muted)">
          <div style="margin-bottom:8px"><span class="material-symbols-outlined" style="font-size:28px">menu</span></div>
          <div style="font-size:13px">No items yet. Add items from the left panel.</div>
        </div>
        <?php else: ?>
        <div id="menuItemsList">
          <?php
          // Render flat — top level first, then children indented
          $topItems = array_filter($items, fn($it) => !$it['parent_id']);
          $childItems = array_filter($items, fn($it) => (bool)$it['parent_id']);

          function renderMenuItem(array $it, array $allChildren, string $base, int $menuId): void {
              $typeLabel = ['custom'=>'Custom','page'=>'Page','post'=>'Post','category'=>'Category'][$it['type']] ?? 'Custom';
              ?>
              <div class="menu-item-row" id="mi-<?= (int)$it['id'] ?>"
                   draggable="true" data-id="<?= (int)$it['id'] ?>">
                <span class="menu-item-drag">⣿</span>
                <div class="menu-item-info">
                  <div class="menu-item-label"><?= e((string)$it['label']) ?></div>
                  <div class="menu-item-meta"><?= $typeLabel ?> · <?= e((string)($it['url'] ?? '—')) ?></div>
                </div>
                <div class="menu-item-actions">
                  <button type="button" class="btn btn-ghost" style="font-size:11px;padding:3px 8px"
                    onclick="toggleItemEditor(<?= (int)$it['id'] ?>)">Edit</button>
                  <form method="POST" action="<?= e($base) ?>/manage/menus/items/<?= (int)$it['id'] ?>/delete" style="display:inline">
                    <input type="hidden" name="menu_id" value="<?= $menuId ?>">
                    <button type="button" class="btn btn-danger" style="font-size:11px;padding:3px 8px"
                      onclick="gcConfirm(this, gcI18n.confirmTitle, '', gcI18n.yesDelete)">✕</button>
                  </form>
                </div>
              </div>
              <!-- inline editor -->
              <div id="mie-<?= (int)$it['id'] ?>" class="menu-item-editor">
                <form method="POST" action="<?= e($base) ?>/manage/menus/items/<?= (int)$it['id'] ?>/update">
                  <input type="hidden" name="menu_id" value="<?= $menuId ?>">
                  <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px">
                    <div>
                      <label style="font-size:11px;font-weight:600;color:var(--muted);display:block;margin-bottom:3px">Label</label>
                      <input type="text" name="label" class="form-input" style="padding:6px 9px;font-size:13px"
                             value="<?= e((string)$it['label']) ?>" required>
                    </div>
                    <div>
                      <label style="font-size:11px;font-weight:600;color:var(--muted);display:block;margin-bottom:3px">URL</label>
                      <input type="text" name="url" class="form-input" style="padding:6px 9px;font-size:13px"
                             value="<?= e((string)($it['url'] ?? '')) ?>">
                    </div>
                  </div>
                  <label style="display:flex;align-items:center;gap:6px;font-size:12px;margin-bottom:8px;cursor:pointer">
                    <input type="checkbox" name="target" value="_blank" <?= ($it['target'] ?? '') === '_blank' ? 'checked' : '' ?>>
                    Open in new tab
                  </label>
                  <div style="display:flex;gap:6px">
                    <button type="submit" class="btn btn-primary" style="font-size:12px;padding:5px 14px">Save</button>
                    <button type="button" class="btn btn-ghost" style="font-size:12px;padding:5px 14px"
                      onclick="toggleItemEditor(<?= (int)$it['id'] ?>)">Cancel</button>
                  </div>
                </form>
              </div>
              <?php
              // Children
              $children = array_filter($allChildren, fn($c) => (int)$c['parent_id'] === (int)$it['id']);
              if ($children): ?>
              <div class="menu-indent">
                <?php foreach ($children as $ch): renderMenuItem($ch, [], $base, $menuId); endforeach ?>
              </div>
              <?php endif;
          }

          foreach ($topItems as $it): renderMenuItem($it, $childItems, $base, $activeId); endforeach;
          ?>
        </div>
        <?php endif ?>
      </div>
    </div>
    <?php else: ?>
    <div class="card">
      <div class="card-body" style="text-align:center;padding:60px 24px;color:var(--muted)">
        <div style="margin-bottom:12px"><span class="material-symbols-outlined" style="font-size:36px">menu</span></div>
        <h3 style="font-size:15px;color:var(--text);margin-bottom:8px">No menu selected</h3>
        <p style="font-size:13px">Create a new menu or select one from the tabs above.</p>
      </div>
    </div>
    <?php endif ?>
  </div>
</div>

<style>.hidden{display:none!important}</style>

<script>
function showAddTab(name, el) {
    document.querySelectorAll('.add-tab').forEach(function(t){ t.classList.remove('active'); });
    document.querySelectorAll('.add-panel').forEach(function(p){ p.classList.remove('active'); });
    el.classList.add('active');
    document.getElementById('add-' + name).classList.add('active');
}

function toggleItemEditor(id) {
    var el = document.getElementById('mie-' + id);
    if (el) el.classList.toggle('open');
}

// Drag-to-reorder
(function(){
    var dragged = null;
    var list = document.getElementById('menuItemsList');
    if (!list) return;
    list.querySelectorAll('.menu-item-row').forEach(function(row){
        row.addEventListener('dragstart', function(e){
            dragged = row;
            setTimeout(function(){ row.classList.add('dragging'); }, 0);
            e.dataTransfer.effectAllowed = 'move';
        });
        row.addEventListener('dragend', function(){
            row.classList.remove('dragging');
            list.querySelectorAll('.menu-item-row').forEach(function(r){ r.classList.remove('drag-over'); });
            saveOrder();
        });
        row.addEventListener('dragover', function(e){
            e.preventDefault();
            if (dragged && dragged !== row) {
                row.classList.add('drag-over');
                var rows = Array.from(list.querySelectorAll('.menu-item-row'));
                var di = rows.indexOf(dragged), ti = rows.indexOf(row);
                if (di < ti) list.insertBefore(dragged, row.nextSibling);
                else list.insertBefore(dragged, row);
            }
        });
        row.addEventListener('dragleave', function(){ row.classList.remove('drag-over'); });
        row.addEventListener('drop', function(e){ e.preventDefault(); row.classList.remove('drag-over'); });
    });

    function saveOrder() {
        var ids = Array.from(list.querySelectorAll('.menu-item-row')).map(function(r){ return parseInt(r.dataset.id); });
        fetch('<?= e($base) ?>/manage/menus/items/reorder', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: ids.map(function(id, i){ return 'ids[' + i + ']=' + id; }).join('&') + '&_csrf=' + encodeURIComponent(window.gcCsrf || '')
        });
    }
})();
</script>
