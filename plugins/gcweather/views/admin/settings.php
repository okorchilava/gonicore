<?php
$pageTitle     = 'GCWeather — Settings';
$activeNav     = 'gcweather';
$topbarActions = '';

$s = $settings;
?>

<div style="display:flex;gap:8px;align-items:center;margin-bottom:20px">
    <a href="<?= e($base) ?>/manage/gcweather" class="btn btn-ghost" style="font-size:13px">← ლოკაციები</a>
</div>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:16px">✓ პარამეტრები შენახულია.</div>
<?php endif ?>

<form method="POST" action="<?= e($base) ?>/manage/gcweather/settings">
<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

<!-- ── Units & API ── -->
<div class="card">
    <div class="card-body" style="padding:22px 24px">
        <div style="font-weight:700;font-size:15px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:12px">
            🌡 ერთეულები & API
        </div>

        <!-- Temperature unit -->
        <div style="margin-bottom:18px">
            <label style="font-weight:600;font-size:13.5px;display:block;margin-bottom:8px">🌡 ტემპერატურა</label>
            <div style="display:flex;gap:10px">
                <?php foreach (['celsius' => '°C (Celsius)', 'fahrenheit' => '°F (Fahrenheit)'] as $val => $lbl): ?>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:8px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;
                              <?= ($s['temperature_unit'] ?? 'celsius') === $val ? 'border-color:var(--primary);background:#eff6ff' : '' ?>">
                    <input type="radio" name="temperature_unit" value="<?= $val ?>" <?= ($s['temperature_unit'] ?? 'celsius') === $val ? 'checked' : '' ?>>
                    <?= $lbl ?>
                </label>
                <?php endforeach ?>
            </div>
        </div>

        <!-- Wind speed -->
        <div style="margin-bottom:18px">
            <label style="font-weight:600;font-size:13.5px;display:block;margin-bottom:8px">💨 ქარის სიჩქარე</label>
            <select name="windspeed_unit" class="form-control" style="max-width:220px">
                <?php foreach (['kmh' => 'km/h', 'mph' => 'mph', 'ms' => 'm/s'] as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= ($s['windspeed_unit'] ?? 'kmh') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach ?>
            </select>
        </div>

        <!-- Precipitation -->
        <div style="margin-bottom:18px">
            <label style="font-weight:600;font-size:13.5px;display:block;margin-bottom:8px">☔ ნალექი</label>
            <div style="display:flex;gap:10px">
                <?php foreach (['mm' => 'mm', 'inch' => 'inch'] as $val => $lbl): ?>
                <label style="display:flex;align-items:center;gap:6px;cursor:pointer;padding:8px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;
                              <?= ($s['precipitation_unit'] ?? 'mm') === $val ? 'border-color:var(--primary);background:#eff6ff' : '' ?>">
                    <input type="radio" name="precipitation_unit" value="<?= $val ?>" <?= ($s['precipitation_unit'] ?? 'mm') === $val ? 'checked' : '' ?>>
                    <?= $lbl ?>
                </label>
                <?php endforeach ?>
            </div>
        </div>

        <!-- Cache duration -->
        <div style="margin-bottom:18px">
            <label style="font-weight:600;font-size:13.5px;display:block;margin-bottom:8px">🔄 Cache-ის ვადა</label>
            <select name="cache_minutes" class="form-control" style="max-width:220px">
                <?php foreach (['5'=>'5 წუთი','10'=>'10 წუთი','15'=>'15 წუთი','30'=>'30 წუთი','60'=>'1 საათი','120'=>'2 საათი'] as $val => $lbl): ?>
                <option value="<?= $val ?>" <?= ($s['cache_minutes'] ?? '30') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                <?php endforeach ?>
            </select>
            <div style="font-size:12px;color:var(--muted);margin-top:4px">API-ს call-ების შეზღუდვა. Open-Meteo-ს data 15წთ-ში ერთხელ ახლდება.</div>
        </div>

        <!-- Forecast days -->
        <div>
            <label style="font-weight:600;font-size:13.5px;display:block;margin-bottom:8px">📅 პროგნოზის დღეები</label>
            <div style="display:flex;gap:8px">
                <?php foreach (['1'=>'1','3'=>'3','5'=>'5','7'=>'7'] as $val => $lbl): ?>
                <label style="display:flex;align-items:center;gap:5px;cursor:pointer;padding:6px 14px;border:1.5px solid var(--border);border-radius:8px;font-size:13px;
                              <?= ($s['forecast_days'] ?? '7') === $val ? 'border-color:var(--primary);background:#eff6ff' : '' ?>">
                    <input type="radio" name="forecast_days" value="<?= $val ?>" <?= ($s['forecast_days'] ?? '7') === $val ? 'checked' : '' ?>>
                    <?= $lbl ?> დღე
                </label>
                <?php endforeach ?>
            </div>
        </div>
    </div>
</div>

<!-- ── Display options ── -->
<div>
    <div class="card" style="margin-bottom:16px">
        <div class="card-body" style="padding:22px 24px">
            <div style="font-weight:700;font-size:15px;margin-bottom:20px;border-bottom:1px solid var(--border);padding-bottom:12px">
                🎨 Default სტილი
            </div>

            <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:4px">
                <?php
                $styles = [
                    'card'    => ['icon'=>'🃏','name'=>'Card',    'desc'=>'მიმდინარე ამინდი'],
                    'full'    => ['icon'=>'📊','name'=>'Full',    'desc'=>'+ საათობრივი/დღიური'],
                    'minimal' => ['icon'=>'💬','name'=>'Minimal', 'desc'=>'ინლაინ, კომპაქტური'],
                ];
                foreach ($styles as $val => $info):
                    $sel = ($s['default_style'] ?? 'card') === $val;
                ?>
                <label style="cursor:pointer;border:2px solid <?= $sel ? 'var(--primary)' : 'var(--border)' ?>;border-radius:10px;padding:12px 8px;text-align:center;background:<?= $sel ? '#eff6ff' : '' ?>">
                    <input type="radio" name="default_style" value="<?= $val ?>" <?= $sel ? 'checked' : '' ?> style="display:none">
                    <div style="font-size:1.6rem"><?= $info['icon'] ?></div>
                    <div style="font-weight:700;font-size:13px;margin-top:4px"><?= $info['name'] ?></div>
                    <div style="font-size:11px;color:var(--muted);margin-top:2px"><?= $info['desc'] ?></div>
                </label>
                <?php endforeach ?>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding:22px 24px">
            <div style="font-weight:700;font-size:15px;margin-bottom:16px;border-bottom:1px solid var(--border);padding-bottom:12px">
                👁 ნაჩვენები ველები
            </div>

            <?php
            $toggleFields = [
                'show_feels_like'     => ['🌡', 'Feels Like (იგრძნობა)'],
                'show_humidity'       => ['💧', 'ტენიანობა'],
                'show_wind'           => ['💨', 'ქარი'],
                'show_pressure'       => ['📊', 'წნევა (hPa)'],
                'show_sunrise_sunset' => ['🌅', 'მზის ამოსვლა/ჩასვლა'],
                'show_hourly'         => ['🕐', 'საათობრივი პროგნოზი (Full)'],
                'show_daily'          => ['📅', '7-დღიანი პროგნოზი (Full)'],
            ];
            foreach ($toggleFields as $key => [$ico, $label]):
            ?>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--border)">
                <div style="font-size:13.5px"><?= $ico ?> <?= $label ?></div>
                <label class="toggle-switch" style="flex-shrink:0">
                    <input type="checkbox" name="<?= $key ?>" value="1" <?= ($s[$key] ?? '1') === '1' ? 'checked' : '' ?>>
                    <span class="toggle-slider"></span>
                </label>
            </div>
            <?php endforeach ?>
        </div>
    </div>
</div>

</div><!-- grid -->

<div style="margin-top:20px">
    <button type="submit" class="btn btn-primary">💾 შენახვა</button>
    <span style="font-size:12.5px;color:var(--muted);margin-left:12px">
        ⚠️ ერთეულების შეცვლისას cache ავტომატურად ინვალიდირდება
    </span>
</div>
</form>
