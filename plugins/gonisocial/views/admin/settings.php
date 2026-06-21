<?php
$pageTitle     = 'GoniSocial — OG / Twitter Card';
$activeNav     = 'gonisocial-settings';
$topbarActions = '<a href="' . e($base) . '/manage/gonisocial" class="btn btn-ghost" style="font-size:13px">← Dashboard</a>';

$s = $settings;
?>
<style>
.gsc-switch{position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0}
.gsc-switch input{opacity:0;width:0;height:0}
.gsc-switch-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#cbd5e1;transition:.2s;border-radius:24px}
.gsc-switch-slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#fff;transition:.2s;border-radius:50%}
.gsc-switch input:checked + .gsc-switch-slider{background:#7c3aed}
.gsc-switch input:checked + .gsc-switch-slider:before{transform:translateX(20px)}
</style>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:14px">✓ პარამეტრები შენახულია.</div>
<?php endif ?>

<?php if (function_exists('goniseo_head')): ?>
<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 18px;margin-bottom:18px;font-size:13px;color:#1e40af">
    ℹ <strong>GoniSEO გამოვლენილია.</strong>
    OG და Twitter Card tag-ებს GoniSEO ამუშავებს — ეს პარამეტრები გამოიყენება მხოლოდ მაშინ, როდესაც GoniSEO გათიშულია.
</div>
<?php endif ?>

<div style="max-width:680px">
<form method="POST" action="<?= e($base) ?>/manage/gonisocial/settings/save">

<!-- Global enable -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;padding:16px 22px">
        <div>
            <div style="font-weight:700;font-size:14px">GoniSocial პლაგინი ჩართულია</div>
            <div style="font-size:12.5px;color:var(--muted);margin-top:2px">გათიშვისას OG tags და share ღილაკები გაქრება.</div>
        </div>
        <div>
            <input type="hidden" name="enabled" value="0">
            <label class="gsc-switch">
                <input type="checkbox" name="enabled" value="1" <?= ($s['enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                <span class="gsc-switch-slider"></span>
            </label>
        </div>
    </div>
</div>

<!-- OG enable -->
<div class="card" style="margin-bottom:16px">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;padding:16px 22px">
        <div>
            <div style="font-weight:700;font-size:14px">OG / Twitter Card ჩართულია</div>
            <div style="font-size:12.5px;color:var(--muted);margin-top:2px">ჩართვისას OG meta tag-ები ემატება &lt;head&gt;-ში.</div>
        </div>
        <div>
            <input type="hidden" name="og_enabled" value="0">
            <label class="gsc-switch">
                <input type="checkbox" name="og_enabled" value="1" <?= ($s['og_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                <span class="gsc-switch-slider"></span>
            </label>
        </div>
    </div>
</div>

<!-- OG fields -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header"><h3>🌐 Open Graph</h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:16px">

        <div class="form-group" style="margin:0">
            <label class="form-label" for="gscOgType">og:type</label>
            <select id="gscOgType" name="og_type" class="form-input" style="max-width:200px">
                <?php foreach (['website','article','blog','profile'] as $ot): ?>
                <option value="<?= $ot ?>" <?= ($s['og_type'] ?? 'website') === $ot ? 'selected' : '' ?>><?= $ot ?></option>
                <?php endforeach ?>
            </select>
            <div style="font-size:11.5px;color:var(--muted);margin-top:4px">სხვა: article (blog posts), profile (პიროვნება)</div>
        </div>

        <div class="form-group" style="margin:0">
            <label class="form-label" for="gscSiteName">og:site_name (საიტის სახელი)</label>
            <input type="text" id="gscSiteName" name="og_site_name" class="form-input"
                   value="<?= e((string)($s['og_site_name'] ?? '')) ?>"
                   placeholder="მაგ. ჩემი საიტი">
        </div>

        <div class="form-group" style="margin:0">
            <label class="form-label" for="gscOgImg">og:image (default)</label>
            <input type="url" id="gscOgImg" name="og_default_image" class="form-input"
                   value="<?= e((string)($s['og_default_image'] ?? '')) ?>"
                   placeholder="https://example.com/share-image.jpg">
            <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                რეკომენდებული: 1200×630px. გამოიყენება, როდესაც გვერდს სხვა OG სურათი არ აქვს.
            </div>
            <?php if (!empty($s['og_default_image'])): ?>
            <img src="<?= e((string)$s['og_default_image']) ?>" alt="OG preview"
                 style="margin-top:8px;max-width:260px;max-height:140px;object-fit:cover;border-radius:8px;border:1px solid var(--border)">
            <?php endif ?>
        </div>

    </div>
</div>

<!-- Twitter Card -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header"><h3>🐦 Twitter / X Card</h3></div>
    <div class="card-body" style="display:flex;flex-direction:column;gap:16px">

        <div class="form-group" style="margin:0">
            <label class="form-label" for="gscTwCard">twitter:card</label>
            <select id="gscTwCard" name="twitter_card" class="form-input" style="max-width:240px">
                <?php foreach (['summary_large_image' => 'summary_large_image (დიდი სურათი)', 'summary' => 'summary (პატარა სურათი)'] as $v => $l): ?>
                <option value="<?= $v ?>" <?= ($s['twitter_card'] ?? 'summary_large_image') === $v ? 'selected' : '' ?>><?= $l ?></option>
                <?php endforeach ?>
            </select>
        </div>

        <div class="form-group" style="margin:0">
            <label class="form-label" for="gscTwHandle">Twitter/X @handle</label>
            <div style="display:flex;align-items:center;gap:0">
                <span style="padding:0 10px;background:var(--border);border:1px solid var(--border-dark,#cbd5e1);border-right:none;border-radius:8px 0 0 8px;font-size:14px;height:40px;display:flex;align-items:center;color:var(--muted)">@</span>
                <input type="text" id="gscTwHandle" name="twitter_handle" class="form-input"
                       style="border-radius:0 8px 8px 0;flex:1"
                       value="<?= e(ltrim((string)($s['twitter_handle'] ?? ''), '@')) ?>"
                       placeholder="youraccount">
            </div>
            <div style="font-size:11.5px;color:var(--muted);margin-top:4px">twitter:site tag-ისთვის. ცარიელი = tag-ი არ ჩაიწერება.</div>
        </div>

    </div>
</div>

<!-- Facebook App ID -->
<div class="card" style="margin-bottom:20px">
    <div class="card-header"><h3>📘 Facebook App ID</h3></div>
    <div class="card-body">
        <div class="form-group" style="margin:0">
            <label class="form-label" for="gscFbApp">fb:app_id</label>
            <input type="text" id="gscFbApp" name="facebook_app_id" class="form-input"
                   style="max-width:280px"
                   value="<?= e((string)($s['facebook_app_id'] ?? '')) ?>"
                   placeholder="123456789012345">
            <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                Optional. Facebook Insights-ისთვის.
                <a href="https://developers.facebook.com/apps" target="_blank" rel="noopener" style="color:#7c3aed">Facebook App-ების კონსოლი ↗</a>
            </div>
        </div>
    </div>
</div>

<div style="display:flex;gap:12px">
    <button type="submit" class="btn btn-primary">💾 შენახვა</button>
    <a href="<?= e($base) ?>/manage/gonisocial" class="btn btn-ghost">გაუქმება</a>
</div>
</form>
</div>
