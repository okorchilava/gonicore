<?php
$pageTitle     = $isEdit ? 'GoniSocial — პროფილის რედაქტირება' : 'GoniSocial — პროფილის დამატება';
$activeNav     = 'gonisocial-profiles';
$topbarActions = '<a href="' . e($base) . '/manage/gonisocial/profiles" class="btn btn-ghost" style="font-size:13px">← პროფილები</a>';

$r = $row ?? [];
$selectedNet = (string)($r['network'] ?? array_key_first($networks));

// Color map for JS preview
$netColorMap = [];
foreach ($networks as $k => $v) $netColorMap[$k] = $v['color'];
?>
<style>
.gsc-net-sel{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:4px}
.gsc-net-opt{display:inline-flex;align-items:center;gap:8px;padding:8px 14px;border-radius:8px;border:2px solid transparent;cursor:pointer;font-size:13px;font-weight:600;transition:border-color .15s,opacity .15s;opacity:.4;color:#fff;user-select:none}
.gsc-net-opt input[type=radio]{display:none}
.gsc-net-opt.selected{opacity:1;border-color:#000}
</style>

<div style="max-width:560px">
<div class="card">
    <div class="card-header">
        <h3><?= $isEdit ? 'პროფილის რედაქტირება' : 'ახალი სოციალური პროფილი' ?></h3>
    </div>
    <div class="card-body">
    <form method="POST" action="<?= e($base) ?>/manage/gonisocial/profiles/save">
        <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>">
        <?php endif ?>

        <!-- Network selector -->
        <div class="form-group">
            <label class="form-label">ქსელი <span style="color:#ef4444">*</span></label>
            <div class="gsc-net-sel" id="netSel">
                <?php foreach ($networks as $netKey => $netMeta): ?>
                <label class="gsc-net-opt <?= $selectedNet === $netKey ? 'selected' : '' ?>"
                       style="background:<?= e($netMeta['color']) ?>"
                       data-net="<?= e($netKey) ?>"
                       onclick="gscSelectNet(this)">
                    <input type="radio" name="network" value="<?= e($netKey) ?>" <?= $selectedNet === $netKey ? 'checked' : '' ?>>
                    <?= e($netMeta['name']) ?>
                </label>
                <?php endforeach ?>
            </div>

            <!-- Color accent bar -->
            <div id="netAccent" style="height:3px;border-radius:3px;margin-top:4px;background:<?= e($netColorMap[$selectedNet] ?? '#475569') ?>;max-width:200px;transition:background .2s"></div>
        </div>

        <!-- Display name -->
        <div class="form-group">
            <label class="form-label" for="gscDispName">ჩვენების სახელი</label>
            <input type="text" id="gscDispName" name="display_name" class="form-input"
                   value="<?= e((string)($r['display_name'] ?? '')) ?>"
                   placeholder="მაგ. ჩვენი Facebook გვერდი"
                   autofocus>
            <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                ცარიელი = ქსელის სახელი გამოიყენება.
            </div>
        </div>

        <!-- URL -->
        <div class="form-group">
            <label class="form-label" for="gscUrl">URL <span style="color:#ef4444">*</span></label>
            <input type="url" id="gscUrl" name="url" class="form-input" required
                   value="<?= e((string)($r['url'] ?? '')) ?>"
                   placeholder="https://facebook.com/yourpage">
        </div>

        <!-- Handle -->
        <div class="form-group">
            <label class="form-label" for="gscHandle">Handle / Username</label>
            <div style="display:flex;align-items:center;gap:0">
                <span style="padding:0 10px;background:var(--border);border:1px solid var(--border-dark,#cbd5e1);border-right:none;border-radius:8px 0 0 8px;font-size:14px;height:40px;display:flex;align-items:center;color:var(--muted)">@</span>
                <input type="text" id="gscHandle" name="handle" class="form-input"
                       style="border-radius:0 8px 8px 0"
                       value="<?= e(ltrim((string)($r['handle'] ?? ''), '@')) ?>"
                       placeholder="yourhandle">
            </div>
            <div style="font-size:11.5px;color:var(--muted);margin-top:4px">Optional. მხოლოდ ინფორმაციისთვის.</div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
            <!-- Active -->
            <div class="form-group">
                <label class="form-label" for="gscActive">სტატუსი</label>
                <select id="gscActive" name="active" class="form-input">
                    <option value="1" <?= (int)($r['active'] ?? 1) === 1 ? 'selected' : '' ?>>✅ აქტიური</option>
                    <option value="0" <?= (int)($r['active'] ?? 1) === 0 ? 'selected' : '' ?>>⏸ გათიშული</option>
                </select>
            </div>

            <!-- Sort order -->
            <div class="form-group">
                <label class="form-label" for="gscSort">თანმიმდევრობა</label>
                <input type="number" id="gscSort" name="sort_order" class="form-input"
                       min="0" value="<?= (int)($r['sort_order'] ?? 0) ?>"
                       style="max-width:100px">
                <div style="font-size:11px;color:var(--muted);margin-top:3px">0 = პირველი</div>
            </div>
        </div>

        <div style="display:flex;gap:12px;margin-top:8px">
            <button type="submit" class="btn btn-primary">
                <?= $isEdit ? '💾 განახლება' : '+ დამატება' ?>
            </button>
            <a href="<?= e($base) ?>/manage/gonisocial/profiles" class="btn btn-ghost">გაუქმება</a>
            <?php if ($isEdit): ?>
            <form method="POST" action="<?= e($base) ?>/manage/gonisocial/profiles/delete"
                  style="margin:0;margin-left:auto"
                  onsubmit="return confirm('პროფილი წაიშლება?')">
                <input type="hidden" name="id" value="<?= (int)($r['id'] ?? 0) ?>">
                <button type="submit" class="btn btn-ghost" style="color:#ef4444">🗑 წაშლა</button>
            </form>
            <?php endif ?>
        </div>
    </form>
    </div>
</div>
</div>

<script>
var netColorMap = <?= json_encode($netColorMap, JSON_UNESCAPED_UNICODE) ?>;

function gscSelectNet(el) {
    document.querySelectorAll('.gsc-net-opt').forEach(function(o){ o.classList.remove('selected'); });
    el.classList.add('selected');
    el.querySelector('input[type=radio]').checked = true;
    var net = el.dataset.net;
    var c = netColorMap[net] || '#475569';
    document.getElementById('netAccent').style.background = c;

    // Auto-fill placeholder for URL if empty
    var urlInput = document.getElementById('gscUrl');
    var urlPlaceholders = {
        facebook:'https://facebook.com/yourpage',instagram:'https://instagram.com/yourhandle',
        twitter:'https://twitter.com/yourhandle',linkedin:'https://linkedin.com/company/yourcompany',
        youtube:'https://youtube.com/@yourchannel',tiktok:'https://tiktok.com/@yourhandle',
        telegram:'https://t.me/youraccount',whatsapp:'https://wa.me/995XXXXXXXXX',
        pinterest:'https://pinterest.com/yourprofile',reddit:'https://reddit.com/r/yoursubreddit',
        github:'https://github.com/yourprofile',viber:'viber://chat?number=995XXXXXXXXX',
    };
    if (!urlInput.value && urlPlaceholders[net]) urlInput.placeholder = urlPlaceholders[net];
}
</script>
