<?php
/**
 * GoniBuilder — Frontend template.
 * Variables: $post, $builderHtml, $base, $siteName, $siteTagline, $categories, $lang, $languages, $langService
 */
global $menuServiceInstance, $widgetServiceInstance;
$_menuSvc   = $menuServiceInstance instanceof \GoniCore\Modules\Menu\MenuService   ? $menuServiceInstance   : null;
$_widgetSvc = $widgetServiceInstance instanceof \GoniCore\Modules\Widget\WidgetService ? $widgetServiceInstance : null;
$_lang      = $langService ?? null;
$_langs     = $_lang ? $_lang->activeLanguages() : [];
$_curLang   = $_lang ? $_lang->currentCode() : ($lang ?? 'en');
?>
<!DOCTYPE html>
<html lang="<?= e($_curLang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($post['title'] ?? '') ?> — <?= e((string)($siteName ?? 'GoniCore')) ?></title>
<?php if (!empty($post['excerpt'])): ?>
<meta name="description" content="<?= e(mb_substr(strip_tags((string)$post['excerpt']), 0, 160)) ?>">
<?php endif ?>
<?php if (!empty($post['featured_image'])): ?>
<meta property="og:image" content="<?= e($post['featured_image']) ?>">
<?php endif ?>
<style>
/* ── Reset ─────────────────────────────────────── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
:root{
  --accent:#10B27C;--accent-d:#0e9c6c;
  --text:#0f172a;--muted:#64748b;
  --border:#e2e8f0;--bg:#f8fafc;--surface:#fff;
  --max:1200px;
  --font:system-ui,-apple-system,'Segoe UI',sans-serif;
}
html{scroll-behavior:smooth}
body{
  font-family:var(--font);color:var(--text);line-height:1.65;
  background:var(--surface);
  display:flex;flex-direction:column;min-height:100vh;
  margin:0;padding:0;
}
a{color:var(--accent);text-decoration:none}
img{max-width:100%}

/* ── Header ────────────────────────────────────── */
.gb-site-header{
  background:#fff;border-bottom:1px solid var(--border);
  position:sticky;top:0;z-index:100;
}
.gb-header-inner{
  max-width:var(--max);margin:0 auto;padding:0 24px;
  height:64px;display:flex;align-items:center;
  justify-content:space-between;gap:24px;
}
.gb-logo-link{display:flex;align-items:center;flex-shrink:0}
.gb-nav{display:flex;list-style:none;gap:24px;margin:0;padding:0;overflow-x:auto}
.gb-nav a{font-size:14px;font-weight:500;color:var(--text);white-space:nowrap;transition:color .15s}
.gb-nav a:hover{color:var(--accent)}
.gb-nav li{position:relative}
.gb-nav ul{
  display:none;position:absolute;top:100%;left:0;
  background:#fff;border:1px solid var(--border);
  border-radius:8px;padding:8px 0;min-width:180px;
  box-shadow:0 8px 24px rgba(0,0,0,.1);z-index:200;
}
.gb-nav li:hover>ul{display:block}
.gb-nav ul a{display:block;padding:8px 16px;font-size:13.5px}

/* ── Language switcher ─────────────────────────── */
.gb-lang-sw{position:relative;flex-shrink:0}
.gb-lang-btn{
  display:flex;align-items:center;gap:5px;
  padding:5px 10px;border-radius:6px;
  border:1px solid var(--border);background:transparent;
  font-size:13px;font-weight:600;color:var(--text);
  cursor:pointer;font-family:var(--font);
}
.gb-lang-btn:hover{background:var(--bg)}
.gb-lang-drop{
  position:absolute;top:calc(100% + 6px);right:0;
  background:#fff;border:1px solid var(--border);
  border-radius:10px;box-shadow:0 8px 24px rgba(0,0,0,.1);
  min-width:150px;z-index:200;overflow:hidden;
  opacity:0;transform:translateY(-4px);
  pointer-events:none;transition:opacity .15s,transform .15s;
}
.gb-lang-drop.open{opacity:1;transform:translateY(0);pointer-events:auto}
.gb-lang-opt{
  display:flex;align-items:center;gap:8px;
  padding:9px 14px;font-size:13px;font-weight:500;
  color:var(--text);transition:background .12s;
}
.gb-lang-opt:hover{background:var(--bg)}
.gb-lang-opt.active{background:#f0fdf4;color:var(--accent);font-weight:700}

/* ── Builder content ───────────────────────────── */
.gb-page-wrap{flex:1;width:100%}
.gb-section{width:100%;position:relative}
.gb-section-inner{display:flex;flex-wrap:wrap;max-width:var(--max);margin:0 auto;padding:0 20px}
/* Full-width section: inner content spans 100%, no side padding */
.gb-full-width>.gb-section-inner{max-width:100%;padding:0}
.gb-column{padding:0 12px;box-sizing:border-box;min-width:0}
/* Full-width columns remove side padding too */
.gb-full-width .gb-column{padding:0}
.gb-heading{margin-bottom:.5em;line-height:1.25}
.gb-text{line-height:1.75}
.gb-button,.gb-image,.gb-spacer,.gb-divider,
.gb-icon-box,.gb-counter,.gb-alert,.gb-gallery,
.gb-posts-grid,.gb-video,.gb-html{margin:8px 0}
.gb-counter-num{display:inline-block}

/* ── Footer ────────────────────────────────────── */
.gb-site-footer{
  background:#0f172a;color:#94a3b8;
  padding:40px 24px 24px;font-size:13px;
  margin-top:0;flex-shrink:0;
}
.gb-footer-inner{max-width:var(--max);margin:0 auto}
.gb-footer-widgets{
  display:flex;gap:48px;margin-bottom:32px;
  flex-wrap:wrap;
}
.gb-footer-col{flex:1;min-width:180px}
.gb-footer-col .widget-title{
  font-size:12px;font-weight:700;text-transform:uppercase;
  letter-spacing:.7px;color:#e2e8f0;margin-bottom:12px;
  padding-bottom:8px;border-bottom:2px solid var(--accent);
}
.gb-footer-col .widget-html,.gb-footer-col .widget-text{color:#94a3b8;line-height:1.7}
.gb-footer-bottom{
  display:flex;align-items:center;justify-content:space-between;
  flex-wrap:wrap;gap:12px;
  border-top:1px solid rgba(255,255,255,.08);
  padding-top:20px;
}
.gb-footer-nav{display:flex;list-style:none;gap:16px;margin:0;padding:0}
.gb-footer-nav a{color:#64748b;font-size:12px}
.gb-footer-nav a:hover{color:var(--accent)}

/* ── Responsive ────────────────────────────────── */
@media(max-width:768px){
  .gb-column{flex:0 0 100%!important;max-width:100%!important}
  .gb-header-inner{padding:0 16px}
  .gb-section-inner{padding:0 16px}
  .gb-footer-widgets{flex-direction:column;gap:24px}
  .gb-footer-bottom{flex-direction:column;align-items:flex-start}
}
</style>
</head>
<body>

<!-- Header -->
<header class="gb-site-header">
  <div class="gb-header-inner">

    <a href="<?= e($base) ?>/" class="gb-logo-link" aria-label="Home">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100" width="100" height="50">
        <rect x="15" y="26" width="48" height="48" rx="10" fill="none" stroke="#0F172A" stroke-width="5"/>
        <rect x="27" y="38" width="24" height="24" rx="6" fill="#10B27C"/>
        <text x="80" y="46" font-family="system-ui" font-size="28" font-weight="900" fill="#0F172A">Goni</text>
        <text x="80" y="74" font-family="system-ui" font-size="28" font-weight="300" fill="#10B27C">Core</text>
      </svg>
    </a>

    <?php
    $navHtml = $_menuSvc ? $_menuSvc->render('primary', 'gb-nav') : null;
    if ($navHtml): echo $navHtml;
    else: ?>
    <ul class="gb-nav">
      <li><a href="<?= e($base) ?>/">Home</a></li>
      <?php foreach ($categories ?? [] as $cat): ?>
      <li><a href="<?= e($base) ?>/category/<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></a></li>
      <?php endforeach ?>
    </ul>
    <?php endif ?>

    <?php if (count($_langs) > 1): ?>
    <div class="gb-lang-sw">
      <button class="gb-lang-btn" id="gbLangBtn" onclick="document.getElementById('gbLangDrop').classList.toggle('open')">
        <?php foreach ($_langs as $lx) { if ($lx['code'] === $_curLang) { echo e($lx['flag'] ?? '🌐') . ' ' . e(strtoupper($lx['code'])); break; } } ?>
        <span style="font-size:10px;opacity:.6">▾</span>
      </button>
      <div class="gb-lang-drop" id="gbLangDrop">
        <?php foreach ($_langs as $lx): ?>
        <a href="<?= e($base) ?>/lang/switch/<?= e($lx['code']) ?>"
           class="gb-lang-opt<?= $lx['code'] === $_curLang ? ' active' : '' ?>">
          <?= e($lx['flag'] ?? '🌐') ?> <?= e($lx['native'] ?? $lx['name']) ?>
        </a>
        <?php endforeach ?>
      </div>
    </div>
    <?php endif ?>

  </div>
</header>

<!-- Builder content -->
<div class="gb-page-wrap">
  <?= $builderHtml ?>
</div>

<!-- Footer -->
<footer class="gb-site-footer">
  <div class="gb-footer-inner">

    <?php
    $fc1 = $_widgetSvc ? $_widgetSvc->renderArea('footer-col-1') : '';
    $fc2 = $_widgetSvc ? $_widgetSvc->renderArea('footer-col-2') : '';
    $fc3 = $_widgetSvc ? $_widgetSvc->renderArea('footer-col-3') : '';
    if ($fc1 || $fc2 || $fc3):
    ?>
    <div class="gb-footer-widgets">
      <?php if ($fc1): ?><div class="gb-footer-col"><?= $fc1 ?></div><?php endif ?>
      <?php if ($fc2): ?><div class="gb-footer-col"><?= $fc2 ?></div><?php endif ?>
      <?php if ($fc3): ?><div class="gb-footer-col"><?= $fc3 ?></div><?php endif ?>
    </div>
    <?php endif ?>

    <div class="gb-footer-bottom">
      <span>&copy; <?= date('Y') ?> <?= e((string)($siteName ?? 'GoniCore')) ?>. All rights reserved.</span>
      <?php
      $footerNav = $_menuSvc ? $_menuSvc->render('footer', 'gb-footer-nav') : null;
      if ($footerNav): echo $footerNav; endif ?>
    </div>

  </div>
</footer>

<!-- Counter animation -->
<script>
(function(){
  var counters = document.querySelectorAll('.gb-counter-num');
  if (!counters.length) return;
  var observer = new IntersectionObserver(function(entries){
    entries.forEach(function(entry){
      if (!entry.isIntersecting) return;
      var el = entry.target;
      var target = parseInt(el.dataset.target || el.textContent, 10);
      var suffix = el.textContent.replace(/[0-9]/g,'');
      var duration = 1500, startTime = null;
      function step(ts){
        if (!startTime) startTime = ts;
        var p = Math.min((ts - startTime) / duration, 1);
        el.textContent = Math.floor(p * target) + suffix;
        if (p < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
      observer.unobserve(el);
    });
  }, {threshold:0.3});
  counters.forEach(function(c){ observer.observe(c); });
})();

// Close lang dropdown on outside click
document.addEventListener('click', function(e){
  var btn = document.getElementById('gbLangBtn');
  var drop = document.getElementById('gbLangDrop');
  if (btn && drop && !btn.contains(e.target) && !drop.contains(e.target)){
    drop.classList.remove('open');
  }
});
</script>
</body>
</html>
