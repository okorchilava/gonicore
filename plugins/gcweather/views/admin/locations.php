<?php
$pageTitle     = 'GCWeather — Locations';
$activeNav     = 'gcweather';
$topbarActions = '<a href="' . e($base) . '/manage/gcweather/form" class="btn btn-primary" style="font-size:13px">+ ახალი ლოკაცია</a>';
?>

<?php if ($saved):    ?><div class="alert alert-success" style="margin-bottom:14px">✓ ლოკაცია შენახულია.</div><?php endif ?>
<?php if ($deleted):  ?><div class="alert alert-success" style="margin-bottom:14px">🗑 ლოკაცია წაიშალა.</div><?php endif ?>
<?php if ($refreshed):?><div class="alert alert-success" style="margin-bottom:14px">🔄 ამინდი განახლდა.</div><?php endif ?>

<div style="display:flex;justify-content:flex-end;margin-bottom:12px">
    <a href="<?= e($base) ?>/manage/gcweather/settings" class="btn btn-ghost" style="font-size:13px">⚙ პარამეტრები</a>
</div>

<?php if (empty($locs)): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:60px 20px;color:var(--muted)">
        <div style="font-size:48px;margin-bottom:14px">🌤️</div>
        <div style="font-size:16px;font-weight:700;margin-bottom:6px">ლოკაცია ჯერ არ დამატებია</div>
        <div style="font-size:13.5px;margin-bottom:22px">დაამატე პირველი ქალაქი ამინდის ვიჯეტისთვის.</div>
        <a href="<?= e($base) ?>/manage/gcweather/form" class="btn btn-primary">+ ახალი ლოკაცია</a>
    </div>
</div>
<?php else: ?>

<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:16px">
<?php foreach ($locs as $loc):
    $w      = $loc['_weather'];
    $active = (bool)(int)$loc['active'];
    $id     = (int)$loc['id'];
    $name   = $loc['display_name'] ?: $loc['name'];
?>
<div class="card" style="border-top:3px solid <?= $active ? '#10b981' : '#9ca3af' ?>">
    <div class="card-body" style="padding:18px 20px">

        <!-- Location header -->
        <div style="display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px">
            <div>
                <div style="font-weight:700;font-size:15px"><?= e((string)$name) ?>
                    <?php if (!empty($loc['country_code'])): ?>
                    <span style="font-size:12px;color:var(--muted);font-weight:400"><?= e((string)$loc['country_code']) ?></span>
                    <?php endif ?>
                </div>
                <div style="font-size:12px;color:var(--muted);margin-top:3px">
                    <?= number_format((float)$loc['latitude'], 4) ?>, <?= number_format((float)$loc['longitude'], 4) ?>
                    &nbsp;·&nbsp; <?= e((string)$loc['timezone']) ?>
                </div>
            </div>
            <!-- Active badge -->
            <span style="font-size:11px;font-weight:600;padding:3px 8px;border-radius:20px;
                         background:<?= $active ? '#d1fae5' : '#f1f5f9' ?>;
                         color:<?= $active ? '#065f46' : '#64748b' ?>">
                <?= $active ? '● ჩართული' : '○ გამოთიშული' ?>
            </span>
        </div>

        <!-- Weather preview -->
        <?php if ($w && isset($w['current'])): ?>
        <?php
            $c       = $w['current'];
            $icon    = GCWeather\GCWeatherService::wmoIcon((int)$c['code'], (bool)$c['is_day']);
            $label   = GCWeather\GCWeatherService::wmoLabel((int)$c['code']);
            $unit    = ($w['temp_unit'] ?? 'celsius') === 'fahrenheit' ? '°F' : '°C';
            $mins    = max(0, (int)((time() - strtotime($w['fetched_at'])) / 60));
            $updStr  = $mins < 2 ? 'ახლახანს' : $mins . ' წთ წინ';
            $isStale = !empty($w['_stale']);
        ?>
        <div style="display:flex;align-items:center;gap:12px;padding:12px;background:var(--bg-subtle,#f8fafc);border-radius:10px;margin-bottom:12px">
            <div style="font-size:2.5rem;line-height:1"><?= $icon ?></div>
            <div style="flex:1">
                <div style="font-size:1.6rem;font-weight:900;line-height:1;color:#1e293b">
                    <?= round((float)$c['temp']) ?><?= e($unit) ?>
                </div>
                <div style="font-size:12px;color:var(--muted);margin-top:2px"><?= e($label) ?></div>
                <div style="font-size:11px;color:var(--muted);margin-top:1px">
                    💧 <?= (int)$c['humidity'] ?>% &nbsp; 💨 <?= round((float)$c['wind_speed']) ?> km/h
                </div>
            </div>
            <div style="text-align:right;font-size:11px;color:var(--muted)">
                <?= e($updStr) ?><?= $isStale ? ' ⚠️' : '' ?>
            </div>
        </div>

        <!-- Daily mini forecast -->
        <?php if (!empty($w['daily'])): ?>
        <div style="display:flex;gap:4px;margin-bottom:12px">
            <?php foreach (array_slice($w['daily'], 0, 5) as $day): ?>
            <div style="flex:1;text-align:center;background:var(--bg-subtle,#f8fafc);border-radius:6px;padding:5px 2px">
                <div style="font-size:10px;color:var(--muted);font-weight:600"><?= e((string)$day['name']) ?></div>
                <div style="font-size:1rem;margin:2px 0"><?= GCWeather\GCWeatherService::wmoIcon((int)$day['code'], true) ?></div>
                <div style="font-size:10px;font-weight:700;color:#1e293b"><?= round((float)$day['max']) ?>°</div>
                <div style="font-size:10px;color:#94a3b8"><?= round((float)$day['min']) ?>°</div>
            </div>
            <?php endforeach ?>
        </div>
        <?php endif ?>

        <?php else: ?>
        <div style="text-align:center;padding:16px;color:var(--muted);font-size:13px;background:var(--bg-subtle,#f8fafc);border-radius:10px;margin-bottom:12px">
            ⚠️ ამინდი ვერ ჩაიტვირთა
        </div>
        <?php endif ?>

        <!-- Shortcode -->
        <div style="margin-bottom:12px">
            <button class="btn btn-ghost" style="font-size:11.5px;font-family:monospace;width:100%"
                    onclick="gcwCopy(this,'[gcweather id=&quot;<?= $id ?>&quot;]')"
                    title="Shortcode-ის კოპირება">
                [gcweather id="<?= $id ?>"] 📋
            </button>
        </div>

        <!-- Actions -->
        <div style="display:flex;gap:6px;flex-wrap:wrap">
            <a href="<?= e($base) ?>/manage/gcweather/form?id=<?= $id ?>"
               class="btn btn-ghost" style="font-size:12px;flex:1">✏ რედ.</a>

            <form method="POST" action="<?= e($base) ?>/manage/gcweather/toggle" style="margin:0;flex:1">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-ghost" style="font-size:12px;width:100%;color:<?= $active ? '#f59e0b' : '#10b981' ?>">
                    <?= $active ? '⏸ გამოთიშვა' : '▶ ჩართვა' ?>
                </button>
            </form>

            <form method="POST" action="<?= e($base) ?>/manage/gcweather/refresh" style="margin:0">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-ghost" style="font-size:12px" title="განახლება">🔄</button>
            </form>

            <form method="POST" action="<?= e($base) ?>/manage/gcweather/delete"
                  style="margin:0" onsubmit="return confirm('<?= e((string)$name) ?> — წაიშლება?')">
                <input type="hidden" name="id" value="<?= $id ?>">
                <button type="submit" class="btn btn-ghost" style="font-size:12px;color:#ef4444">🗑</button>
            </form>
        </div>

    </div>
</div>
<?php endforeach ?>
</div>

<div style="margin-top:16px;font-size:12.5px;color:var(--muted)">
    გამოყენება: <code>gcweather(1)</code> ან <code>gcweather(1, 'full')</code> ·
    shortcode: <code>[gcweather id="1"]</code> ·
    სტილები: <code>card</code> | <code>full</code> | <code>minimal</code>
</div>

<?php endif ?>

<script>
function gcwCopy(btn, text) {
    var decoded = text.replace(/&quot;/g, '"');
    navigator.clipboard
        ? navigator.clipboard.writeText(decoded)
        : (function(){var t=document.createElement('textarea');t.value=decoded;document.body.appendChild(t);t.select();document.execCommand('copy');document.body.removeChild(t);})();
    var orig = btn.innerHTML;
    btn.innerHTML = '✅ Copied!';
    setTimeout(function(){ btn.innerHTML = orig; }, 1600);
}
</script>
