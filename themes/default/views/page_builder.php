<?php
/**
 * GoniBuilder — Frontend template.
 * Variables: $post, $builderHtml, $base, $siteName, $siteTagline, $categories, $lang, $languages, $langService
 */
?>
<!DOCTYPE html>
<html lang="<?= e($lang ?? 'en') ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($post['title'] ?? '') ?> — <?= e((string)($siteName ?? 'GoniCore')) ?></title>
<?php /* SEO */ ?>
<?php if (!empty($post['excerpt'])): ?>
<meta name="description" content="<?= e(mb_substr(strip_tags((string)$post['excerpt']), 0, 160)) ?>">
<?php endif ?>
<?php if (!empty($post['featured_image'])): ?>
<meta property="og:image" content="<?= e($post['featured_image']) ?>">
<?php endif ?>
<style>
/* ── Reset ──────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --accent:#10B27C;--accent-d:#0e9c6c;--text:#0f172a;--muted:#64748b;
  --border:#e2e8f0;--bg:#f8fafc;--surface:#fff;
  --font:system-ui,-apple-system,'Segoe UI',sans-serif;
}
body{font-family:var(--font);color:var(--text);line-height:1.65;background:var(--surface)}
a{color:var(--accent)}
img{max-width:100%}

/* ── Header ─────────────────────────────────────── */
.gb-site-header{
  background:#fff;border-bottom:1px solid var(--border);
  position:sticky;top:0;z-index:100;
}
.gb-header-inner{
  max-width:1200px;margin:0 auto;padding:0 24px;
  height:64px;display:flex;align-items:center;justify-content:space-between;
}
.gb-logo-link{display:flex;align-items:center;text-decoration:none}
.gb-nav{display:flex;list-style:none;gap:24px;margin:0;padding:0}
.gb-nav a{font-size:14px;font-weight:500;color:var(--text);text-decoration:none;transition:color .15s}
.gb-nav a:hover{color:var(--accent)}
.gb-nav ul{display:none;position:absolute;top:100%;left:0;background:#fff;border:1px solid var(--border);border-radius:8px;padding:8px 0;min-width:180px;box-shadow:0 8px 24px rgba(0,0,0,.1);z-index:200}
.gb-nav li{position:relative}
.gb-nav li:hover>ul{display:block}
.gb-nav ul a{display:block;padding:8px 16px;font-size:13.5px}

/* ── Builder sections ────────────────────────────── */
.gb-page{width:100%}
.gb-section{width:100%;position:relative}
.gb-section-inner{display:flex;flex-wrap:wrap;max-width:1200px;margin:0 auto;padding:0 20px}
.gb-full-width>.gb-section-inner{max-width:100%;padding:0}
.gb-column{padding:0 12px;box-sizing:border-box;min-width:0}
.gb-heading{margin-bottom:.5em;line-height:1.25}
.gb-text{line-height:1.75}
.gb-button{margin:8px 0}
.gb-image{margin:8px 0}
.gb-spacer,.gb-divider{margin:4px 0}
.gb-icon-box{margin:8px 0}
.gb-counter{margin:8px 0}
.gb-alert{margin:8px 0}
.gb-gallery{margin:8px 0}
.gb-posts-grid{margin:8px 0}
.gb-video{margin:8px 0}
.gb-html{margin:8px 0}

/* Counter animation */
.gb-counter-num{display:inline-block}

/* ── Footer ─────────────────────────────────────── */
.gb-site-footer{background:#0f172a;color:#94a3b8;padding:40px 24px;text-align:center;font-size:13px;margin-top:0}
.gb-site-footer a{color:#64748b}
.gb-site-footer a:hover{color:var(--accent)}

/* ── Responsive ──────────────────────────────────── */
@media(max-width:768px){
  .gb-column{flex:0 0 100%!important;max-width:100%!important}
  .gb-header-inner{padding:0 16px}
  .gb-section-inner{padding:0 16px}
}
</style>
</head>
<body>

<!-- Header -->
<header class="gb-site-header">
  <div class="gb-header-inner">
    <a href="<?= e($base) ?>/" class="gb-logo-link">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100" width="100" height="50">
        <rect x="15" y="26" width="48" height="48" rx="10" fill="none" stroke="#0F172A" stroke-width="5"/>
        <rect x="27" y="38" width="24" height="24" rx="6" fill="#10B27C"/>
        <text x="80" y="46" font-family="system-ui" font-size="28" font-weight="900" fill="#0F172A">Goni</text>
        <text x="80" y="74" font-family="system-ui" font-size="28" font-weight="300" fill="#10B27C">Core</text>
      </svg>
    </a>
    <?php
    // Dynamic primary menu from MenuService
    global $menuServiceInstance;
    if ($menuServiceInstance instanceof \GoniCore\Modules\Menu\MenuService):
        $navHtml = $menuServiceInstance->render('primary', 'gc-nav');
        if ($navHtml): echo $navHtml;
        else: // fallback static nav
    ?>
    <ul class="gb-nav">
      <li><a href="<?= e($base) ?>/">Home</a></li>
      <?php foreach ($categories ?? [] as $cat): ?>
      <li><a href="<?= e($base) ?>/category/<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></a></li>
      <?php endforeach ?>
    </ul>
    <?php endif; else: ?>
    <ul class="gb-nav">
      <li><a href="<?= e($base) ?>/">Home</a></li>
      <?php foreach ($categories ?? [] as $cat): ?>
      <li><a href="<?= e($base) ?>/category/<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></a></li>
      <?php endforeach ?>
    </ul>
    <?php endif ?>
  </div>
</header>

<!-- Builder content -->
<?= $builderHtml ?>

<!-- Footer -->
<footer class="gb-site-footer">
  <div style="max-width:1200px;margin:0 auto">
    <?php
    if ($menuServiceInstance instanceof \GoniCore\Modules\Menu\MenuService):
        $footerNav = $menuServiceInstance->render('footer', 'gb-footer-nav');
        if ($footerNav): ?>
        <style>.gb-footer-nav{display:flex;list-style:none;gap:20px;justify-content:center;margin-bottom:16px;padding:0}.gb-footer-nav a{color:#64748b;font-size:13px}.gb-footer-nav a:hover{color:var(--accent)}</style>
        <?= $footerNav ?>
    <?php endif; endif ?>
    <div>&copy; <?= date('Y') ?> <?= e((string)($siteName ?? 'GoniCore')) ?>. All rights reserved.</div>
  </div>
</footer>

<!-- Counter animation -->
<script>
(function(){
  var counters = document.querySelectorAll('.gb-counter-num');
  if (!counters.length) return;
  var observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (!entry.isIntersecting) return;
      var el = entry.target;
      var target = parseInt(el.dataset.target || el.textContent, 10);
      var suffix = el.textContent.replace(/[0-9]/g,'');
      var start = 0, duration = 1500, startTime = null;
      function step(ts) {
        if (!startTime) startTime = ts;
        var progress = Math.min((ts - startTime) / duration, 1);
        el.textContent = Math.floor(progress * target) + suffix;
        if (progress < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
      observer.unobserve(el);
    });
  }, { threshold: 0.3 });
  counters.forEach(function(c){ observer.observe(c); });
})();
</script>
</body>
</html>
