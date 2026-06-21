<?php
$pageTitle     = 'GoniSEO — Sitemap';
$activeNav     = 'goniseo-sitemap';
$topbarActions = '<a href="' . e($base) . '/manage/goniseo/sitemap/form" class="btn btn-primary" style="font-size:13px">+ URL-ის დამატება</a>';
?>
<style>
.gseo-url-card{background:var(--card-bg,#fff);border:1px solid var(--border);border-radius:12px;padding:16px 20px;margin-bottom:8px;display:flex;align-items:center;gap:16px;flex-wrap:wrap}
.gseo-freq-badge{font-size:11px;font-weight:700;background:#ede9fe;color:#6d28d9;border-radius:20px;padding:2px 8px}
.gseo-prio{font-size:13px;font-weight:700;color:#7c3aed;min-width:32px;text-align:center}
</style>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:14px">✓ URL შენახულია.</div>
<?php endif ?>
<?php if ($deleted): ?>
<div class="alert alert-success" style="margin-bottom:14px">✓ URL წაიშალა.</div>
<?php endif ?>
<?php if ($pinged === 'ok'): ?>
<div class="alert alert-success" style="margin-bottom:14px">✓ Bing-ი pin-ულია.</div>
<?php elseif ($pinged === 'error'): ?>
<div class="alert" style="background:#fef2f2;border:1px solid #fca5a5;color:#b91c1c;border-radius:10px;padding:12px 18px;margin-bottom:14px">
    ✗ Ping error — Bing-ს ვერ მივაღწიეთ. Sitemap URL სწორია?
</div>
<?php endif ?>

<!-- Sitemap URL -->
<div class="card" style="margin-bottom:16px">
    <div class="card-header"><h3>🔗 Sitemap URL</h3></div>
    <div class="card-body" style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <code id="gsSitemapUrl" style="flex:1;font-size:13px;background:var(--border);padding:8px 14px;border-radius:8px;min-width:200px;word-break:break-all">
            <?= e($sitemapUrl) ?>
        </code>
        <button type="button" class="btn btn-ghost" style="font-size:13px;flex-shrink:0"
                onclick="navigator.clipboard.writeText('<?= e($sitemapUrl) ?>').then(function(){this.textContent='✓ Copied!';}.bind(this))"
        >📋 Copy</button>
        <a href="<?= e($sitemapUrl) ?>" target="_blank" class="btn btn-ghost" style="font-size:13px">
            ↗ გახსნა
        </a>
    </div>

    <!-- Submit to search engines -->
    <div style="padding:14px 20px;border-top:1px solid var(--border);display:flex;align-items:center;gap:12px;flex-wrap:wrap">
        <div style="font-size:13px;color:var(--muted);flex:1">
            📡 Sitemap სერჩ ძრავებს გაუგზავნე:
        </div>
        <!-- Bing ping -->
        <form method="POST" action="<?= e($base) ?>/manage/goniseo/sitemap/ping">
            <input type="hidden" name="sitemap_url" value="<?= e($sitemapUrl) ?>">
            <button type="submit" class="btn btn-ghost" style="font-size:13px">
                🔍 Bing-ს გაუგზავნე
            </button>
        </form>
        <!-- Google Search Console link -->
        <a href="https://search.google.com/search-console" target="_blank" class="btn btn-ghost" style="font-size:13px">
            🌐 Google Search Console →
        </a>
    </div>
    <div style="padding:0 20px 14px;font-size:12px;color:var(--muted)">
        ⚠ Google-ი deprecated-ი გახადა ping endpoint. Google Search Console-ში ხელით დააბმე sitemap URL.
    </div>
</div>

<!-- Custom URL list -->
<div class="card">
    <div class="card-header" style="justify-content:space-between">
        <h3>🗺 Custom URLs <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= count($urls) ?>)</span></h3>
        <a href="<?= e($base) ?>/manage/goniseo/sitemap/form" class="btn btn-ghost" style="font-size:13px">+ URL-ის დამატება</a>
    </div>

    <?php if (empty($urls)): ?>
    <div class="empty">
        <div class="empty-icon">🗺</div>
        <h3>Custom URL-ები არ არის</h3>
        <p>Meta-ს URLs ავტომატურად ჩაიყრება სიტემაპში.<br>
           Custom URLs ფართო კონტროლისთვის.</p>
        <a href="<?= e($base) ?>/manage/goniseo/sitemap/form" class="btn btn-primary">+ URL-ის დამატება</a>
    </div>
    <?php else: ?>
    <div style="padding:8px 0">
    <?php foreach ($urls as $u): ?>
    <div class="gseo-url-card">
        <span class="gseo-prio"><?= number_format((float)$u['priority'], 1) ?></span>
        <div style="flex:1;min-width:0">
            <div style="font-size:13.5px;font-weight:600;word-break:break-all"><?= e((string)$u['url']) ?></div>
            <div style="font-size:12px;color:var(--muted);margin-top:3px;display:flex;gap:10px;flex-wrap:wrap">
                <span class="gseo-freq-badge"><?= e((string)$u['changefreq']) ?></span>
                <?php if ($u['lastmod']): ?>
                <span>lastmod: <?= e((string)$u['lastmod']) ?></span>
                <?php endif ?>
            </div>
        </div>
        <div style="display:flex;gap:6px;flex-shrink:0">
            <a href="<?= e($base) ?>/manage/goniseo/sitemap/form?id=<?= (int)$u['id'] ?>"
               class="btn btn-ghost" style="font-size:12px;padding:4px 10px">✏</a>
            <form method="POST" action="<?= e($base) ?>/manage/goniseo/sitemap/delete"
                  onsubmit="return confirm('URL წაიშლება სიტემაპიდან?')">
                <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                <button type="submit" class="btn btn-ghost"
                        style="font-size:12px;padding:4px 10px;color:#ef4444">🗑</button>
            </form>
        </div>
    </div>
    <?php endforeach ?>
    </div>
    <?php endif ?>
</div>

<!-- Info -->
<div style="margin-top:12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:14px 18px;font-size:12.5px;color:#166534">
    <strong>ℹ სიტემაპში ავტომატურად ჩაიყრება:</strong><br>
    • ყველა Custom URL (ზემოთ სია)<br>
    • ყველა Meta Tags-ში შეყვანილი URL Path
</div>
