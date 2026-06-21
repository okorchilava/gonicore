<?php
$pageTitle     = $isEdit ? 'GCCounter — Group-ის რედაქტირება' : 'GCCounter — ახალი Counter Group';
$activeNav     = 'gccounter';
$topbarActions = '<a href="' . e($base) . '/manage/gccounter" class="btn btn-ghost" style="font-size:13px">← Counter Groups</a>';

$g   = $group ?? [];
$its = $items ?? [];
// Pass initial item count to JS
$initCount = count($its);

$defaultColors = ['#10B27C','#7c3aed','#ef4444','#f59e0b','#3b82f6','#ec4899','#14b8a6','#8b5cf6'];
?>
<style>
.gcc-item-card{background:var(--card-bg,#fff);border:1.5px solid var(--border);border-radius:12px;margin-bottom:12px;overflow:hidden;transition:border-color .15s}
.gcc-item-card:hover{border-color:#a78bfa}
.gcc-item-card-head{display:flex;align-items:center;justify-content:space-between;padding:12px 18px;background:var(--bg,#f8fafc);border-bottom:1px solid var(--border);cursor:move;user-select:none}
.gcc-item-card-body{padding:16px 18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px}
.gcc-item-card-num{font-size:13px;font-weight:700;color:#475569}
.gcc-color-dot{width:18px;height:18px;border-radius:50%;display:inline-block;vertical-align:middle;margin-right:6px;border:1.5px solid rgba(0,0,0,.1)}
</style>

<div style="max-width:820px">
<form method="POST" action="<?= e($base) ?>/manage/gccounter/save" id="gccForm">
    <?php if ($isEdit): ?>
    <input type="hidden" name="id" value="<?= (int)($g['id'] ?? 0) ?>">
    <?php endif ?>

    <!-- Group Settings -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-header"><h3>⚙ Group-ის პარამეტრები</h3></div>
        <div class="card-body">

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px">
                <div class="form-group" style="margin:0">
                    <label class="form-label" for="gccName">სახელი <span style="color:#ef4444">*</span></label>
                    <input type="text" id="gccName" name="name" class="form-input" required autofocus
                           value="<?= e((string)($g['name'] ?? '')) ?>"
                           placeholder="მაგ. ჩვენი შედეგები"
                           oninput="gccAutoSlug(this)">
                </div>
                <div class="form-group" style="margin:0">
                    <label class="form-label" for="gccSlug">Slug (shortcode-ისთვის)</label>
                    <input type="text" id="gccSlug" name="slug" class="form-input"
                           value="<?= e((string)($g['slug'] ?? '')) ?>"
                           placeholder="ავტომატური">
                    <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                        <code>[gccounter slug="<span id="gccSlugPreview"><?= e((string)($g['slug'] ?? 'slug')) ?></span>"]</code>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:16px">
                <div class="form-group" style="margin:0">
                    <label class="form-label" for="gccCols">სვეტების რაოდენობა</label>
                    <select id="gccCols" name="columns" class="form-input">
                        <?php foreach ([2,3,4,5,6] as $c): ?>
                        <option value="<?= $c ?>" <?= (int)($g['columns'] ?? 4) === $c ? 'selected' : '' ?>>
                            <?= $c ?> სვეტი
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="form-group" style="margin:0">
                    <label class="form-label" for="gccDur">ანიმაციის ხანგრძლივობა</label>
                    <select id="gccDur" name="duration_ms" class="form-input">
                        <?php foreach ([500=>'0.5s',1000=>'1s',1500=>'1.5s',2000=>'2s',2500=>'2.5s',3000=>'3s',4000=>'4s',5000=>'5s'] as $ms => $label): ?>
                        <option value="<?= $ms ?>" <?= (int)($g['duration_ms'] ?? 2000) === $ms ? 'selected' : '' ?>>
                            <?= $label ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="form-group" style="margin:0">
                    <label class="form-label" for="gccSep">ათასების გამყოფი</label>
                    <select id="gccSep" name="separator" class="form-input">
                        <?php foreach ([','  => '1,000 (მძიმე)',
                                         '.' => '1.000 (წერტილი)',
                                         ''  => '1000 (გარეშე)'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($g['separator'] ?? ',') === $v ? 'selected' : '' ?>>
                            <?= $l ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>

                <div class="form-group" style="margin:0">
                    <label class="form-label" for="gccAlign">გასწორება</label>
                    <select id="gccAlign" name="align" class="form-input">
                        <?php foreach (['center'=>'Center','left'=>'Left','right'=>'Right'] as $v => $l): ?>
                        <option value="<?= $v ?>" <?= ($g['align'] ?? 'center') === $v ? 'selected' : '' ?>>
                            <?= $l ?>
                        </option>
                        <?php endforeach ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Items -->
    <div class="card" style="margin-bottom:20px">
        <div class="card-header" style="justify-content:space-between">
            <h3>🔢 Counter-ები</h3>
            <span style="font-size:12px;color:var(--muted)">ჩავარდნა = თანმიმდევრობის ცვლილება</span>
        </div>
        <div class="card-body" style="padding-bottom:0">

            <div id="gcc-items-list">
            <?php foreach ($its as $i => $item): ?>
            <div class="gcc-item-card" data-idx="<?= $i ?>">
                <div class="gcc-item-card-head">
                    <span class="gcc-item-card-num">Counter #<?= $i + 1 ?></span>
                    <button type="button" class="btn btn-ghost" style="font-size:12px;padding:4px 10px;color:#ef4444"
                            onclick="gccRemove(this)">× წაშლა</button>
                </div>
                <div class="gcc-item-card-body">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">რიცხვი <span style="color:#ef4444">*</span></label>
                        <input type="number" name="items[<?= $i ?>][number]" class="form-input"
                               value="<?= (int)($item['number'] ?? 0) ?>"
                               min="0" max="999999999999" required placeholder="500">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Prefix</label>
                        <input type="text" name="items[<?= $i ?>][prefix]" class="form-input"
                               value="<?= e((string)($item['prefix'] ?? '')) ?>"
                               maxlength="20" placeholder="$ ან ₾">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Suffix</label>
                        <input type="text" name="items[<?= $i ?>][suffix]" class="form-input"
                               value="<?= e((string)($item['suffix'] ?? '')) ?>"
                               maxlength="20" placeholder="+, %, K, M">
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">ფერი</label>
                        <div style="display:flex;gap:6px;align-items:center">
                            <input type="color" name="items[<?= $i ?>][color]" class="form-input"
                                   value="<?= e((string)($item['color'] ?? '#10B27C')) ?>"
                                   style="width:48px;height:40px;padding:2px 4px;cursor:pointer">
                            <div style="display:flex;gap:4px;flex-wrap:wrap">
                                <?php foreach ($defaultColors as $dc): ?>
                                <div class="gcc-color-dot" style="background:<?= e($dc) ?>;cursor:pointer"
                                     title="<?= e($dc) ?>"
                                     onclick="this.closest('.gcc-item-card-body').querySelector('input[type=color]').value='<?= e($dc) ?>'"></div>
                                <?php endforeach ?>
                            </div>
                        </div>
                    </div>
                    <div class="form-group" style="margin:0;grid-column:1/-1">
                        <label class="form-label">სათაური / Label</label>
                        <input type="text" name="items[<?= $i ?>][label]" class="form-input"
                               value="<?= e((string)($item['label'] ?? '')) ?>"
                               maxlength="200" placeholder="მაგ. კმაყოფილი კლიენტი">
                    </div>
                    <div class="form-group" style="margin:0;grid-column:1/-1">
                        <label class="form-label">აღწერა (optional)</label>
                        <input type="text" name="items[<?= $i ?>][description]" class="form-input"
                               value="<?= e((string)($item['description'] ?? '')) ?>"
                               maxlength="500" placeholder="პატარა ტექსტი counter-ის ქვეშ">
                    </div>
                    <input type="hidden" name="items[<?= $i ?>][sort_order]" value="<?= $i ?>">
                </div>
            </div>
            <?php endforeach ?>
            </div><!-- #gcc-items-list -->

            <div style="padding:14px 0 18px">
                <button type="button" class="btn btn-ghost" style="width:100%;font-size:13.5px;border:2px dashed var(--border);border-radius:10px;padding:14px"
                        onclick="gccAddItem()">
                    + Counter-ის დამატება
                </button>
            </div>
        </div>
    </div>

    <!-- Submit -->
    <div style="display:flex;gap:12px;margin-bottom:30px">
        <button type="submit" class="btn btn-primary">💾 შენახვა</button>
        <a href="<?= e($base) ?>/manage/gccounter" class="btn btn-ghost">გაუქმება</a>
        <?php if ($isEdit): ?>
        <form method="POST" action="<?= e($base) ?>/manage/gccounter/delete"
              style="margin:0;margin-left:auto"
              onsubmit="return confirm('Counter Group სრულად წაიშლება?')">
            <input type="hidden" name="id" value="<?= (int)($g['id'] ?? 0) ?>">
            <button type="submit" class="btn btn-ghost" style="color:#ef4444">🗑 Group-ის წაშლა</button>
        </form>
        <?php endif ?>
    </div>
</form>
</div>

<script>
var gccIdx   = <?= $initCount ?>;
var gccColors = <?= json_encode($defaultColors, JSON_UNESCAPED_UNICODE) ?>;

// ── Auto slug from name ──────────────────────────────────────────────────────
function gccAutoSlug(input) {
    if (document.getElementById('gccSlug').dataset.manual) return;
    var slug = input.value
        .toLowerCase()
        .replace(/\s+/g, '-')
        .replace(/[^a-z0-9ა-ჿ\-]/g, '')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '') || 'group';
    document.getElementById('gccSlug').value = slug;
    document.getElementById('gccSlugPreview').textContent = slug;
}
document.getElementById('gccSlug').addEventListener('input', function(){
    this.dataset.manual = '1';
    document.getElementById('gccSlugPreview').textContent = this.value || 'slug';
});

// ── Add item ──────────────────────────────────────────────────────────────────
function gccAddItem() {
    var i   = gccIdx++;
    var col = (Math.floor(Math.random() * gccColors.length));
    var div = document.createElement('div');
    div.className = 'gcc-item-card';
    div.dataset.idx = i;
    div.innerHTML = gccCardHtml(i, {color: gccColors[col]});
    document.getElementById('gcc-items-list').appendChild(div);
    div.querySelector('input[type=number]').focus();
    gccRenumber();
}

// ── Remove item ───────────────────────────────────────────────────────────────
function gccRemove(btn) {
    btn.closest('.gcc-item-card').remove();
    gccRenumber();
}

// ── Renumber all items after add/remove ───────────────────────────────────────
function gccRenumber() {
    document.querySelectorAll('.gcc-item-card').forEach(function(card, i) {
        var numEl = card.querySelector('.gcc-item-card-num');
        if (numEl) numEl.textContent = 'Counter #' + (i + 1);
        card.querySelectorAll('[name]').forEach(function(el) {
            el.name = el.name.replace(/items\[\d+\]/, 'items[' + i + ']');
        });
        var so = card.querySelector('input[name$="[sort_order]"]');
        if (so) so.value = i;
    });
}

// ── Build HTML for a new item card ───────────────────────────────────────────
function gccCardHtml(i, defaults) {
    var color = defaults.color || '#10B27C';
    var dots = gccColors.map(function(c){
        return '<div class="gcc-color-dot" style="background:'+c+';cursor:pointer" title="'+c+'"'
             + ' onclick="this.closest(\'.gcc-item-card-body\').querySelector(\'input[type=color]\').value=\''+c+'\'"></div>';
    }).join('');

    return '<div class="gcc-item-card-head">'
        + '<span class="gcc-item-card-num">Counter #' + (i + 1) + '</span>'
        + '<button type="button" class="btn btn-ghost" style="font-size:12px;padding:4px 10px;color:#ef4444" onclick="gccRemove(this)">× წაშლა</button>'
        + '</div>'
        + '<div class="gcc-item-card-body">'
        + '<div class="form-group" style="margin:0"><label class="form-label">რიცხვი <span style="color:#ef4444">*</span></label>'
        + '<input type="number" name="items[' + i + '][number]" class="form-input" min="0" max="999999999999" required placeholder="500"></div>'
        + '<div class="form-group" style="margin:0"><label class="form-label">Prefix</label>'
        + '<input type="text" name="items[' + i + '][prefix]" class="form-input" maxlength="20" placeholder="$ ან ₾"></div>'
        + '<div class="form-group" style="margin:0"><label class="form-label">Suffix</label>'
        + '<input type="text" name="items[' + i + '][suffix]" class="form-input" maxlength="20" placeholder="+, %, K, M"></div>'
        + '<div class="form-group" style="margin:0"><label class="form-label">ფერი</label>'
        + '<div style="display:flex;gap:6px;align-items:center">'
        + '<input type="color" name="items[' + i + '][color]" class="form-input" value="' + color + '" style="width:48px;height:40px;padding:2px 4px;cursor:pointer">'
        + '<div style="display:flex;gap:4px;flex-wrap:wrap">' + dots + '</div>'
        + '</div></div>'
        + '<div class="form-group" style="margin:0;grid-column:1/-1"><label class="form-label">სათაური / Label</label>'
        + '<input type="text" name="items[' + i + '][label]" class="form-input" maxlength="200" placeholder="მაგ. კმაყოფილი კლიენტი"></div>'
        + '<div class="form-group" style="margin:0;grid-column:1/-1"><label class="form-label">აღწერა (optional)</label>'
        + '<input type="text" name="items[' + i + '][description]" class="form-input" maxlength="500" placeholder="პატარა ტექსტი counter-ის ქვეშ"></div>'
        + '<input type="hidden" name="items[' + i + '][sort_order]" value="' + i + '">'
        + '</div>';
}

// ── If no items yet — add first one automatically ─────────────────────────────
<?php if ($initCount === 0): ?>
gccAddItem();
<?php endif ?>
</script>
