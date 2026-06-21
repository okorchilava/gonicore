<?php
$pageTitle     = 'GoniSEO — პარამეტრები';
$activeNav     = 'goniseo-settings';
$topbarActions = '<a href="' . e($base) . '/manage/goniseo" class="btn btn-ghost" style="font-size:13px">← Dashboard</a>';
?>
<style>
.gseo-toggle-row{display:flex;align-items:center;justify-content:space-between;padding:14px 0;border-bottom:1px solid var(--border)}
.gseo-toggle-row:last-child{border-bottom:none}
.gseo-toggle-label{font-weight:600;font-size:14px}
.gseo-toggle-desc{font-size:12.5px;color:var(--muted);margin-top:2px}
.gseo-switch{position:relative;display:inline-block;width:44px;height:24px;flex-shrink:0}
.gseo-switch input{opacity:0;width:0;height:0}
.gseo-switch-slider{position:absolute;cursor:pointer;top:0;left:0;right:0;bottom:0;background:#cbd5e1;transition:.2s;border-radius:24px}
.gseo-switch-slider:before{position:absolute;content:"";height:18px;width:18px;left:3px;bottom:3px;background:#fff;transition:.2s;border-radius:50%}
.gseo-switch input:checked + .gseo-switch-slider{background:#7c3aed}
.gseo-switch input:checked + .gseo-switch-slider:before{transform:translateX(20px)}
.gseo-section{background:var(--card-bg,#fff);border:1px solid var(--border);border-radius:12px;margin-bottom:16px}
.gseo-section-hdr{padding:16px 22px;border-bottom:1px solid var(--border);font-weight:700;font-size:14px}
.gseo-section-body{padding:20px 22px}
.token-hint{font-size:11.5px;background:#ede9fe;color:#6d28d9;border-radius:6px;padding:2px 8px;margin-left:6px;font-family:monospace}
</style>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:16px">✓ პარამეტრები შენახულია.</div>
<?php endif ?>

<form method="POST" action="<?= e($base) ?>/manage/goniseo/settings/save">
<div style="max-width:720px">

<!-- General -->
<div class="gseo-section">
    <div class="gseo-section-hdr">⚙ ზოგადი</div>
    <div class="gseo-section-body">
        <!-- Enabled toggle -->
        <div class="gseo-toggle-row">
            <div>
                <div class="gseo-toggle-label">პლაგინი ჩართულია</div>
                <div class="gseo-toggle-desc">Head tag injection + სიტემაპი + robots.txt ჩართვა/გათიშვა</div>
            </div>
            <div>
                <input type="hidden" name="enabled" value="0">
                <label class="gseo-switch">
                    <input type="checkbox" name="enabled" value="1" <?= ($settings['enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                    <span class="gseo-switch-slider"></span>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Title -->
<div class="gseo-section">
    <div class="gseo-section-hdr">📝 Title</div>
    <div class="gseo-section-body" style="display:flex;flex-direction:column;gap:16px">

        <div class="form-group" style="margin:0">
            <label class="form-label" for="gsTitleFormat">
                Title Format
                <span class="token-hint">{title}</span>
                <span class="token-hint">{site_name}</span>
            </label>
            <input type="text" id="gsTitleFormat" name="title_format" class="form-input"
                   value="<?= e((string)($settings['title_format'] ?? '{title} | {site_name}')) ?>"
                   oninput="gsTitlePreview()"
                   placeholder="{title} | {site_name}">
            <div style="font-size:11.5px;color:var(--muted);margin-top:5px">
                <code>{title}</code> = გვერდის სათაური, <code>{site_name}</code> = საიტის სახელი
            </div>
        </div>

        <div class="form-group" style="margin:0">
            <label class="form-label" for="gsSiteName">საიტის სახელი (Site Name)</label>
            <input type="text" id="gsSiteName" name="site_name" class="form-input"
                   value="<?= e((string)($settings['site_name'] ?? '')) ?>"
                   oninput="gsTitlePreview()"
                   placeholder="მაგ: ჩემი ბლოგი">
        </div>

        <!-- Live preview -->
        <div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:14px 18px">
            <div style="font-size:11.5px;color:var(--muted);margin-bottom:6px">🔍 Preview (Google-ის შედეგებში)</div>
            <div id="gsPreviewTitle" style="font-size:18px;color:#1a0dab;font-weight:400;line-height:1.4;font-family:'Arial',sans-serif"></div>
            <div id="gsPreviewUrl" style="font-size:13px;color:#188038;margin-top:2px">https://example.com/about</div>
            <div id="gsPreviewDesc" style="font-size:13.5px;color:#4d5156;margin-top:4px;line-height:1.5">
                <?= e(mb_strimwidth((string)($settings['default_description'] ?? ''), 0, 160, '…')) ?>
            </div>
        </div>
    </div>
</div>

<!-- Default Meta -->
<div class="gseo-section">
    <div class="gseo-section-hdr">🏷 Default Meta (ყველა გვერდისთვის სათადარიგო)</div>
    <div class="gseo-section-body" style="display:flex;flex-direction:column;gap:16px">

        <div class="form-group" style="margin:0">
            <label class="form-label" for="gsDefDesc">Default Description
                <span style="font-size:11px;color:var(--muted);font-weight:400">(max 160 სიმბოლო)</span>
            </label>
            <textarea id="gsDefDesc" name="default_description" class="form-input" rows="3"
                      maxlength="500"
                      placeholder="საიტის ზოგადი აღწერა..."><?= e((string)($settings['default_description'] ?? '')) ?></textarea>
        </div>

        <div class="form-group" style="margin:0">
            <label class="form-label" for="gsDefKeys">Default Keywords</label>
            <input type="text" id="gsDefKeys" name="default_keywords" class="form-input"
                   value="<?= e((string)($settings['default_keywords'] ?? '')) ?>"
                   placeholder="keyword1, keyword2, keyword3">
        </div>

        <div class="form-group" style="margin:0">
            <label class="form-label" for="gsDefOgImg">Default OG Image URL</label>
            <input type="url" id="gsDefOgImg" name="default_og_image" class="form-input"
                   value="<?= e((string)($settings['default_og_image'] ?? '')) ?>"
                   placeholder="https://example.com/og-image.jpg">
            <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                გამოყენება: Facebook / Twitter share card-ი. რეკომენდებული 1200×630px.
            </div>
        </div>

        <div class="form-group" style="margin:0">
            <label class="form-label" for="gsDefRobots">Default Robots</label>
            <select id="gsDefRobots" name="default_robots" class="form-input" style="max-width:220px">
                <?php foreach (['index,follow','noindex,follow','noindex,nofollow','index,nofollow'] as $opt): ?>
                <option value="<?= e($opt) ?>" <?= ($settings['default_robots'] ?? 'index,follow') === $opt ? 'selected' : '' ?>>
                    <?= e($opt) ?>
                </option>
                <?php endforeach ?>
            </select>
        </div>
    </div>
</div>

<!-- Search Engine Verification -->
<div class="gseo-section">
    <div class="gseo-section-hdr">✅ Search Engine Verification</div>
    <div class="gseo-section-body" style="display:flex;flex-direction:column;gap:14px">

        <div class="form-group" style="margin:0">
            <label class="form-label" for="gsGVerify">
                Google Site Verification
                <span style="font-size:11px;color:var(--muted);font-weight:400">
                    (<a href="https://search.google.com/search-console" target="_blank">Search Console →</a>)
                </span>
            </label>
            <input type="text" id="gsGVerify" name="google_verify" class="form-input"
                   value="<?= e((string)($settings['google_verify'] ?? '')) ?>"
                   placeholder="google1234567890abcdef.html ან ვერიფიკაციის კოდი">
            <div style="font-size:11.5px;color:var(--muted);margin-top:4px">
                Search Console → "HTML tag" მეთოდი → content= ველის მნიშვნელობა
            </div>
        </div>

        <div class="form-group" style="margin:0">
            <label class="form-label" for="gsBVerify">
                Bing/Microsoft Verification
                <span style="font-size:11px;color:var(--muted);font-weight:400">
                    (<a href="https://www.bing.com/webmasters" target="_blank">Bing Webmaster →</a>)
                </span>
            </label>
            <input type="text" id="gsBVerify" name="bing_verify" class="form-input"
                   value="<?= e((string)($settings['bing_verify'] ?? '')) ?>"
                   placeholder="msvalidate.01 content-ის მნიშვნელობა">
        </div>
    </div>
</div>

<div style="display:flex;gap:12px;padding-top:4px">
    <button type="submit" class="btn btn-primary">💾 შენახვა</button>
    <a href="<?= e($base) ?>/manage/goniseo" class="btn btn-ghost">გაუქმება</a>
</div>
</div>
</form>

<script>
function gsTitlePreview() {
    var fmt  = document.getElementById('gsTitleFormat').value || '{title} | {site_name}';
    var site = document.getElementById('gsSiteName').value || 'Site Name';
    var out  = fmt.replace('{title}', 'გვერდის სახელი').replace('{site_name}', site);
    var el   = document.getElementById('gsPreviewTitle');
    if (el) {
        el.textContent = out;
        el.style.color = out.length > 60 ? '#ef4444' : '#1a0dab';
    }
}
gsTitlePreview();
</script>
