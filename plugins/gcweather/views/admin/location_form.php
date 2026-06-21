<?php
$pageTitle     = $isEdit ? 'GCWeather — ლოკაციის რედაქტირება' : 'GCWeather — ახალი ლოკაცია';
$activeNav     = 'gcweather';
$topbarActions = '';
?>

<div style="display:flex;gap:8px;align-items:center;margin-bottom:20px">
    <a href="<?= e($base) ?>/manage/gcweather" class="btn btn-ghost" style="font-size:13px">← ლოკაციები</a>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

<!-- ── Form ── -->
<div class="card">
    <div class="card-body" style="padding:22px 24px">
        <div style="font-weight:700;font-size:15px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:12px">
            <?= $isEdit ? '✏ ლოკაციის რედაქტირება' : '🌍 ახალი ლოკაცია' ?>
        </div>

        <!-- Geocoding search (only shown when adding new, or re-search when editing) -->
        <div style="margin-bottom:20px">
            <label style="font-weight:600;font-size:13.5px;display:block;margin-bottom:8px">
                🔍 ქალაქის ძებნა
            </label>
            <div style="display:flex;gap:8px">
                <input type="text" id="gcwSearchQ" class="form-control" style="flex:1"
                       placeholder="მაგ. Tbilisi, Paris, New York..."
                       onkeydown="if(event.key==='Enter'){event.preventDefault();gcwSearch()}"
                       value="<?= $isEdit ? e((string)($loc['name'] ?? '')) : '' ?>">
                <button type="button" class="btn btn-ghost" id="gcwSearchBtn" onclick="gcwSearch()">🔍</button>
            </div>
            <div id="gcwResults" style="display:none;border:1px solid var(--border);border-radius:8px;margin-top:6px;overflow:hidden;max-height:220px;overflow-y:auto;background:#fff;box-shadow:0 4px 12px rgba(0,0,0,.08)"></div>
        </div>

        <!-- Selected location indicator -->
        <div id="gcwSelected" style="padding:10px 14px;background:var(--bg-subtle,#f8fafc);border-radius:8px;font-size:13px;margin-bottom:18px;color:var(--muted)">
            <?php if ($isEdit): ?>
            ✅ <?= e((string)($loc['name'] ?? '')) ?>, <?= e((string)($loc['country_code'] ?? '')) ?>
                (<?= number_format((float)($loc['latitude'] ?? 0), 4) ?>, <?= number_format((float)($loc['longitude'] ?? 0), 4) ?>)
            <?php else: ?>
            ❓ ქალაქი ჯერ არ არჩეულია — ძებნის შემდეგ დააჭირე შედეგს
            <?php endif ?>
        </div>

        <form method="POST" action="<?= e($base) ?>/manage/gcweather/save">
            <?php if ($isEdit): ?>
            <input type="hidden" name="id" value="<?= (int)$loc['id'] ?>">
            <?php endif ?>

            <!-- Hidden geocoding fields -->
            <input type="hidden" name="name"         id="gcwName"    value="<?= e((string)($loc['name']         ?? '')) ?>">
            <input type="hidden" name="latitude"      id="gcwLat"     value="<?= e((string)($loc['latitude']      ?? '')) ?>">
            <input type="hidden" name="longitude"     id="gcwLng"     value="<?= e((string)($loc['longitude']     ?? '')) ?>">
            <input type="hidden" name="timezone"      id="gcwTz"      value="<?= e((string)($loc['timezone']      ?? 'UTC')) ?>">
            <input type="hidden" name="country_code"  id="gcwCC"      value="<?= e((string)($loc['country_code']  ?? '')) ?>">

            <!-- Display name -->
            <div style="margin-bottom:16px">
                <label style="font-weight:600;font-size:13.5px;display:block;margin-bottom:6px">
                    📝 Display სახელი
                    <span style="font-weight:400;color:var(--muted);font-size:12px">(ვიჯეტში გამოჩენილი)</span>
                </label>
                <input type="text" name="display_name" id="gcwDisplayName" class="form-control"
                       placeholder="მაგ. თბილისი"
                       value="<?= e((string)($loc['display_name'] ?? '')) ?>">
            </div>

            <!-- Active + Sort order -->
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin-bottom:16px">
                <div>
                    <label style="font-weight:600;font-size:13.5px;display:block;margin-bottom:6px">სტატუსი</label>
                    <label class="toggle-switch">
                        <input type="checkbox" name="active" value="1"
                               <?= (!$isEdit || (int)($loc['active'] ?? 1) === 1) ? 'checked' : '' ?>>
                        <span class="toggle-slider"></span>
                    </label>
                    <span style="font-size:12.5px;color:var(--muted);margin-left:8px">ჩართული</span>
                </div>
                <div>
                    <label style="font-weight:600;font-size:13.5px;display:block;margin-bottom:6px">Sort Order</label>
                    <input type="number" name="sort_order" class="form-control" min="0" max="999"
                           value="<?= (int)($loc['sort_order'] ?? 0) ?>" style="max-width:90px">
                </div>
            </div>

            <!-- Timezone display -->
            <div style="margin-bottom:20px;padding:10px 12px;background:var(--bg-subtle,#f8fafc);border-radius:6px;font-size:12.5px;color:var(--muted)">
                🕐 Timezone: <span id="gcwTzDisplay"><?= e((string)($loc['timezone'] ?? 'UTC')) ?></span>
                &nbsp;·&nbsp;
                📍 Coords: <span id="gcwCoordsDisplay">
                    <?php if ($isEdit): ?>
                    <?= number_format((float)($loc['latitude'] ?? 0), 4) ?>, <?= number_format((float)($loc['longitude'] ?? 0), 4) ?>
                    <?php else: ?>—<?php endif ?>
                </span>
            </div>

            <button type="submit" class="btn btn-primary" id="gcwSubmitBtn">
                <?= $isEdit ? '💾 შენახვა' : '✚ დამატება' ?>
            </button>
        </form>
    </div>
</div>

<!-- ── Info panel ── -->
<div>
    <div class="card" style="margin-bottom:16px;border:1.5px solid #bfdbfe">
        <div class="card-body" style="padding:18px 20px;background:#eff6ff;border-radius:inherit">
            <div style="font-weight:700;color:#1d4ed8;margin-bottom:10px;font-size:14px">ℹ️ Open-Meteo API</div>
            <ul style="font-size:13px;color:#1e40af;margin:0;padding-left:20px;line-height:1.9">
                <li>უფასო სერვისი — API Key-ი არ სჭირდება</li>
                <li>მოიცავს 150,000+ ქალაქს მთელ მსოფლიოში</li>
                <li>მონაცემები ნახევარ საათში ერთხელ განახლდება</li>
                <li>ამინდი ინახება cache-ში — სიჩქარე მაქსიმალური</li>
            </ul>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding:18px 20px">
            <div style="font-weight:700;font-size:14px;margin-bottom:12px">📋 Shortcode მაგალითები</div>
            <div style="font-size:12.5px;color:var(--muted);line-height:2">
                <code>[gcweather id="1"]</code> — კარტი (default)<br>
                <code>[gcweather id="1" style="full"]</code> — სრული + პროგნოზი<br>
                <code>[gcweather id="1" style="minimal"]</code> — მინიმალური<br>
                <br>
                PHP:<br>
                <code>gcweather(1)</code><br>
                <code>gcweather(1, 'full')</code>
            </div>
        </div>
    </div>
</div>

</div><!-- grid -->

<script>
async function gcwSearch() {
    var q = document.getElementById('gcwSearchQ').value.trim();
    if (q.length < 2) return;
    var btn = document.getElementById('gcwSearchBtn');
    btn.textContent = '⏳'; btn.disabled = true;
    var res = document.getElementById('gcwResults');
    res.innerHTML = '<div style="padding:12px;color:var(--muted);font-size:13px">ეძებს...</div>';
    res.style.display = 'block';
    try {
        var r = await fetch(
            'https://geocoding-api.open-meteo.com/v1/search?name=' +
            encodeURIComponent(q) + '&count=10&language=en&format=json'
        );
        var data = await r.json();
        gcwShowResults(data.results || []);
    } catch(e) {
        res.innerHTML = '<div style="padding:12px;color:#ef4444;font-size:13px">⚠️ ძებნა ვერ მოხდა</div>';
    } finally {
        btn.textContent = '🔍'; btn.disabled = false;
    }
}

function gcwShowResults(results) {
    var div = document.getElementById('gcwResults');
    if (!results.length) {
        div.innerHTML = '<div style="padding:12px;color:var(--muted);font-size:13px">შედეგი ვერ მოიძებნა</div>';
        return;
    }
    div.innerHTML = results.map(function(r) {
        var sub = [r.admin1, r.country].filter(Boolean).join(', ');
        return '<div class="gcw-res-item" onclick=\'gcwSelect(' + JSON.stringify(r).replace(/'/g,"&#39;") + ')\'>'
             + '<strong style="font-size:13.5px">' + r.name + '</strong>'
             + (sub ? '<span style="color:var(--muted);font-size:12px;margin-left:6px">' + sub + '</span>' : '')
             + '<span style="float:right;color:#94a3b8;font-size:11px">'
             + r.latitude.toFixed(2) + ', ' + r.longitude.toFixed(2)
             + '</span></div>';
    }).join('');
}

function gcwSelect(r) {
    document.getElementById('gcwName').value        = r.name;
    document.getElementById('gcwLat').value         = r.latitude;
    document.getElementById('gcwLng').value         = r.longitude;
    document.getElementById('gcwTz').value          = r.timezone  || 'UTC';
    document.getElementById('gcwCC').value          = r.country_code || '';
    document.getElementById('gcwTzDisplay').textContent     = r.timezone || 'UTC';
    document.getElementById('gcwCoordsDisplay').textContent = r.latitude.toFixed(4) + ', ' + r.longitude.toFixed(4);

    // Auto-fill display name if empty
    var dn = document.getElementById('gcwDisplayName');
    if (!dn.value || dn.value === dn.dataset.prev) dn.value = r.name;
    dn.dataset.prev = r.name;

    var sub = [r.admin1, r.country].filter(Boolean).join(', ');
    var sel = document.getElementById('gcwSelected');
    sel.innerHTML  = '✅ <strong>' + r.name + '</strong>' + (sub ? ', ' + sub : '') + ' (' + r.country_code + ')';
    sel.style.color = '#10b981';

    document.getElementById('gcwResults').style.display = 'none';
    document.getElementById('gcwSearchQ').value = r.name;
}
</script>

<style>
.gcw-res-item{padding:10px 14px;cursor:pointer;border-bottom:1px solid var(--border);font-size:13px}
.gcw-res-item:last-child{border-bottom:none}
.gcw-res-item:hover{background:var(--bg-subtle,#f8fafc)}
</style>
