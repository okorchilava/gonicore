<?php
/**
 * GoniCore Default Theme — Header Partial
 *
 * Variables in scope (set by ThemeController::view()):
 *   $base, $siteName, $categories, $langService, $menuService, $pageTitle
 */
?>
<!DOCTYPE html>
<html lang="<?= e($langService ? $langService->currentCode() : 'en') ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e((string)(isset($pageTitle) ? "{$pageTitle} — {$siteName}" : ($siteName ?? 'GoniCore'))) ?></title>
<?php $__fav = function_exists('gc_setting') ? (string) gc_setting('site_favicon', '') : ''; if ($__fav !== ''): ?>
<link rel="icon" href="<?= e($base ?? '') ?>/storage/media/<?= e($__fav) ?>">
<?php endif ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200&display=swap">
<style>
/* ── Material Symbols icons ─────────────────────────── */
.material-symbols-outlined {
    font-family: 'Material Symbols Outlined';
    font-weight: normal; font-style: normal; line-height: 1;
    letter-spacing: normal; text-transform: none; white-space: nowrap;
    direction: ltr; display: inline-flex; align-items: center; justify-content: center;
    vertical-align: middle; -webkit-font-feature-settings: 'liga';
    -webkit-font-smoothing: antialiased; user-select: none;
    font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24;
}
/* ── Reset & base ─────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
    --bg:       #ffffff;
    --surface:  #f8fafc;
    --border:   #e2e8f0;
    --text:     #0f172a;
    --muted:    #64748b;
    --accent:   #4f46e5;
    --accent-d: #4338ca;
    --header:   #0f172a;
    --radius:   10px;
    --shadow:   0 1px 3px rgba(0,0,0,.07), 0 4px 16px rgba(0,0,0,.05);
    --shadow-h: 0 8px 24px rgba(0,0,0,.12);
    --max:      1200px;
    --font:     system-ui, -apple-system, 'Segoe UI', sans-serif;
}

body {
    font-family: var(--font);
    background: var(--bg);
    color: var(--text);
    line-height: 1.65;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

a { color: var(--accent); text-decoration: none; }
a:hover { text-decoration: underline; }

/* ── Topbar ─────────────────────────────────────── */
.gc-header {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 72px;
    background: rgba(255,255,255,.92);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border-bottom: 1px solid var(--border);
    z-index: 1000;
    display: flex;
    align-items: center;
}
.gc-header-inner {
    max-width: var(--max);
    margin: 0 auto;
    padding: 0 24px;
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.gc-logo { display: flex; align-items: center; gap: 12px; flex-shrink: 0; }
.gc-logo:hover { text-decoration: none; opacity: .85; }
.gc-logo-img { height: 44px; width: auto; display: block; }
.gc-brand { display: flex; flex-direction: column; line-height: 1.15; min-width: 0; }
.gc-brand-name { font-size: 18px; font-weight: 800; color: var(--text); letter-spacing: -.3px; white-space: nowrap; }
.gc-brand-tagline { font-size: 12px; color: var(--muted); font-weight: 500; margin-top: 2px; white-space: nowrap; }

/* Right cluster: menu → cart → language switcher, pushed to the right edge */
.gc-header-right {
    display: flex; align-items: center; gap: 1.5rem;
    min-width: 0; margin-left: auto;
}
.gc-nav {
    display: flex; align-items: center; gap: 2rem;
    list-style: none; overflow-x: auto; scrollbar-width: none;
    min-width: 0;
}
.gc-nav::-webkit-scrollbar { display: none; }
.gc-nav a {
    color: var(--muted); font-size: 14px; font-weight: 500;
    white-space: nowrap; transition: color .15s; text-decoration: none;
}
.gc-nav a:hover { color: var(--text); text-decoration: none; }

/* ── Language switcher ─────────────────────────── */
.lang-switcher { position: relative; display: flex; align-items: center; }
.lang-btn {
    display: flex; align-items: center; gap: 6px;
    padding: 5px 10px; border-radius: 6px;
    border: 1px solid var(--border); background: transparent;
    font-size: 13px; font-weight: 600; color: var(--text);
    cursor: pointer; font-family: var(--font); transition: background .15s;
}
.lang-btn:hover { background: var(--surface); }
.lang-dropdown {
    position: absolute; top: calc(100% + 6px); right: 0;
    background: #fff; border: 1px solid var(--border);
    border-radius: 10px; box-shadow: 0 8px 24px rgba(0,0,0,.1);
    min-width: 150px; z-index: 200; overflow: hidden;
    opacity: 0; transform: translateY(-4px);
    pointer-events: none; transition: opacity .15s, transform .15s;
}
.lang-dropdown.show { opacity: 1; transform: translateY(0); pointer-events: auto; }
.lang-option {
    display: flex; align-items: center; gap: 8px;
    padding: 9px 14px; font-size: 13px; font-weight: 500;
    color: var(--text); text-decoration: none; transition: background .12s;
}
.lang-option:hover { background: var(--surface); text-decoration: none; }
.lang-option.active { background: #f0fdf4; color: var(--accent); font-weight: 700; }

/* ── Main content area ─────────────────────────── */
.gc-main { flex: 1; width: 100%; max-width: 100%; padding-top: 72px; }

/* ── Footer ─────────────────────────────────────── */
.gc-footer {
    background: #0f172a;
    color: #94a3b8;
    padding: 56px 0 24px;
    margin-top: auto;
    font-size: 14px;
}
.gc-footer-content {
    max-width: var(--max); margin: 0 auto; padding: 0 24px;
    display: grid; grid-template-columns: repeat(3, 1fr);
    gap: 40px; margin-bottom: 40px; align-items: flex-start;
}
.gc-footer-col h4 {
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .8px; color: #f1f5f9; margin-bottom: 16px;
}
.gc-footer-col ul { list-style: none; }
.gc-footer-col ul li { margin-bottom: 10px; }
.gc-footer-col ul a { color: #94a3b8; text-decoration: none; font-size: 13px; transition: color .15s; }
.gc-footer-col ul a:hover { color: #10B27C; }
.gc-footer-col p { color: #94a3b8; margin-top: 14px; font-size: 13px; line-height: 1.7; }
.gc-footer-bottom {
    max-width: var(--max); margin: 0 auto; padding: 20px 24px 0;
    border-top: 1px solid rgba(255,255,255,.08);
    display: flex; justify-content: space-between;
    color: #475569; font-size: 12px;
}

/* ── Wrap & section ────────────────────────────── */
.wrap   { max-width: var(--max); margin: 0 auto; padding: 0 24px; }
.section { padding: 56px 0; }

/* ── Hero banner ───────────────────────────────── */
.hero {
    position: relative;
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #312e81 100%);
    color: #fff;
    min-height: calc(100vh - 72px);
    display: flex; flex-direction: column;
    justify-content: center; align-items: center;
    padding: 60px 24px; text-align: center; overflow: hidden;
}
.hero-bg-lines { position: absolute; top: 0; left: 0; width: 100%; height: 100%; z-index: 0; pointer-events: none; }
.hero > *:not(.hero-bg-lines) { position: relative; z-index: 1; }
.hero-title { font-size: clamp(30px, 5vw, 54px); font-weight: 900; letter-spacing: -1.5px; line-height: 1.15; margin-bottom: 16px; }
.hero-title span { color: #a5b4fc; }
.hero-sub { color: #94a3b8; font-size: 17px; max-width: 480px; margin: 0 auto; }

/* ── Section heading ───────────────────────────── */
.section-heading {
    font-size: 13px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1px; color: var(--muted); margin-bottom: 28px;
    display: flex; align-items: center; gap: 12px;
}
.section-heading::after { content: ''; flex: 1; height: 1px; background: var(--border); }

/* ── Posts grid ────────────────────────────────── */
.posts-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 22px; }
.post-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); display: flex; flex-direction: column;
    transition: box-shadow .2s, transform .2s, border-color .2s; overflow: hidden;
}
.post-card:hover { box-shadow: var(--shadow-h); transform: translateY(-3px); border-color: #c7d2fe; }

/* ── Post card featured image ───────────────────── */
.post-card-img { width: 100%; aspect-ratio: 16/9; overflow: hidden; border-radius: var(--radius) var(--radius) 0 0; background: var(--surface); }
.post-card-img img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform .35s ease; }
.post-card:hover .post-card-img img { transform: scale(1.05); }

.post-card-body { padding: 22px 24px 20px; flex: 1; display: flex; flex-direction: column; }
.post-card-cat { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: var(--accent); margin-bottom: 10px; }
.post-card-cat a { color: inherit; }
.post-card-cat a:hover { text-decoration: none; opacity: .8; }
.post-card-title { font-size: 17px; font-weight: 700; line-height: 1.4; color: var(--text); margin-bottom: 10px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.post-card-title a { color: inherit; }
.post-card-title a:hover { color: var(--accent); text-decoration: none; }
.post-card-excerpt { font-size: 13.5px; color: var(--muted); line-height: 1.65; flex: 1; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 18px; }
.post-card-footer { display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: var(--muted); border-top: 1px solid var(--border); padding-top: 14px; margin-top: auto; }
.read-more { font-size: 13px; font-weight: 600; color: var(--accent); display: inline-flex; align-items: center; gap: 4px; }
.read-more:hover { color: var(--accent-d); text-decoration: none; }

/* ── Empty state ───────────────────────────────── */
.empty { text-align: center; padding: 80px 24px; color: var(--muted); }
.empty-icon { font-size: 48px; margin-bottom: 20px; line-height: 1; }
.empty h3   { font-size: 20px; color: var(--text); margin-bottom: 8px; }
.empty p    { font-size: 14px; }

/* ── Pagination ────────────────────────────────── */
.pagination { display: flex; justify-content: center; gap: 6px; padding-top: 48px; }
.pagination a, .pagination span {
    width: 38px; height: 38px; display: inline-flex; align-items: center; justify-content: center;
    border-radius: 8px; font-size: 14px; font-weight: 500;
    border: 1px solid var(--border); color: var(--text); transition: all .15s;
}
.pagination a:hover { background: var(--surface); text-decoration: none; border-color: #c7d2fe; color: var(--accent); }
.pagination .current { background: var(--accent); color: #fff; border-color: var(--accent); }
.pagination .disabled { opacity: .35; pointer-events: none; }

/* ── Post hero ─────────────────────────────────── */
.post-hero {
    position: relative;
    background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 60%, #312e81 100%);
    color: #fff; padding: 56px 24px 48px; overflow: hidden;
}
.post-hero > *:not(.hero-bg-lines) { position: relative; z-index: 1; }
.post-hero-inner { max-width: 780px; margin: 0 auto; }
.post-cat-pill {
    display: inline-flex; align-items: center; gap: 6px;
    background: rgba(165,180,252,.15); border: 1px solid rgba(165,180,252,.3);
    color: #a5b4fc; border-radius: 20px; padding: 4px 14px;
    font-size: 11.5px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .7px; margin-bottom: 18px;
}
.post-cat-pill a { color: inherit; }
.post-cat-pill a:hover { text-decoration: none; opacity: .85; }
.post-hero-title { font-size: clamp(24px, 4vw, 42px); font-weight: 900; letter-spacing: -1px; line-height: 1.2; margin-bottom: 20px; }
.post-byline { font-size: 13.5px; color: #94a3b8; }
.post-byline strong { color: #cbd5e1; }

/* ── Post / category hero overlay (when cover image set) ── */
.post-hero-overlay { position: absolute; inset: 0; background: rgba(0,0,0,.52); z-index: 0; }
.post-hero-cover { background: #0f172a; }
.post-hero-cover .post-hero-inner { position: relative; z-index: 1; }
.cat-hero-cover { position: relative; }
.cat-hero-cover .post-hero-overlay { border-radius: 0; }

/* ── Page cover image ────────────────────────────── */
.page-cover-wrap { width: 100%; max-height: 420px; overflow: hidden; }
.page-cover-img  { width: 100%; max-height: 420px; object-fit: cover; display: block; }

/* ── Post body ─────────────────────────────────── */
.post-content { max-width: 780px; margin: 0 auto; padding: 48px 24px 64px; font-size: 17px; line-height: 1.85; color: #1e293b; }
.post-featured-wrap + .post-content { padding-top: 40px; }
.post-content h2 { font-size: 26px; font-weight: 800; margin: 2em 0 .6em; color: var(--text); letter-spacing: -.4px; }
.post-content h3 { font-size: 21px; font-weight: 700; margin: 1.8em 0 .5em; color: var(--text); }
.post-content p  { margin-bottom: 1.5em; }
.post-content a  { color: var(--accent); border-bottom: 1px solid rgba(79,70,229,.25); }
.post-content a:hover { border-color: var(--accent); text-decoration: none; }
.post-content ul, .post-content ol { padding-left: 1.6em; margin-bottom: 1.5em; }
.post-content li { margin-bottom: .4em; }
.post-content blockquote { border-left: 4px solid var(--accent); padding: 4px 0 4px 20px; margin: 1.5em 0; color: var(--muted); font-style: italic; font-size: 18px; }
.post-content pre { background: #0f172a; color: #e2e8f0; border-radius: 10px; padding: 20px 24px; overflow-x: auto; font-size: 14px; line-height: 1.65; margin-bottom: 1.5em; }
.post-content code { font-family: 'Fira Code', 'JetBrains Mono', 'Courier New', monospace; font-size: .88em; }
.post-content :not(pre) > code { background: #f1f5f9; padding: 2px 7px; border-radius: 4px; color: #be185d; }
.post-content img { max-width: 100%; border-radius: var(--radius); margin: 1em 0; }
.post-content hr  { border: none; border-top: 1px solid var(--border); margin: 2em 0; }
.post-content table { width: 100%; border-collapse: collapse; margin-bottom: 1.5em; font-size: 15px; }
.post-content th, .post-content td { border: 1px solid var(--border); padding: 10px 14px; }
.post-content th { background: var(--surface); font-weight: 700; }

/* ── Widget areas ──────────────────────────────── */
.widget { margin-bottom: 24px; }
.widget-title { font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .7px; color: var(--text); margin-bottom: 12px; padding-bottom: 8px; border-bottom: 2px solid var(--accent); }
.widget-html, .widget-text { font-size: 13.5px; color: var(--muted); line-height: 1.7; }
.gc-footer .widget-title { color: #f1f5f9; border-bottom-color: #10B27C; }
.gc-footer .widget-html, .gc-footer .widget-text { color: #94a3b8; }
.gc-footer a { color: #94a3b8; }
.gc-footer a:hover { color: #10B27C; text-decoration: none; }

/* ── Back link ─────────────────────────────────── */
.back-link { display: inline-flex; align-items: center; gap: 6px; font-size: 13.5px; font-weight: 600; color: var(--accent); margin-bottom: 40px; padding: 8px 0; }
.back-link:hover { text-decoration: none; color: var(--accent-d); }

/* ── Category header ───────────────────────────── */
.cat-hero { background: var(--header); color: #fff; padding: 52px 24px; text-align: center; }
.cat-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: #818cf8; margin-bottom: 10px; }
.cat-hero-name { font-size: 36px; font-weight: 900; letter-spacing: -1px; }
.cat-hero-count { color: #64748b; font-size: 14px; margin-top: 8px; }

/* ── 404 ───────────────────────────────────────── */
.not-found { text-align: center; padding: 100px 24px; }
.not-found-code { font-size: 120px; font-weight: 900; line-height: 1; background: linear-gradient(135deg, #c7d2fe, var(--accent)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; margin-bottom: 20px; }
.not-found h2 { font-size: 28px; margin-bottom: 12px; }
.not-found p  { color: var(--muted); margin-bottom: 32px; font-size: 15px; }

/* ── Button ────────────────────────────────────── */
.btn { display: inline-flex; align-items: center; gap: 8px; background: var(--accent); color: #fff; padding: 11px 24px; border-radius: 8px; font-weight: 600; font-size: 14px; transition: background .15s; }
.btn:hover { background: var(--accent-d); text-decoration: none; color: #fff; }

/* ── Misc ──────────────────────────────────────── */
.tag-pill { display: inline-block; padding: 3px 10px; background: var(--surface); border: 1px solid var(--border); border-radius: 20px; font-size: 12px; color: var(--muted); text-decoration: none; transition: border-color .15s; }
.tag-pill:hover { border-color: var(--accent); color: var(--accent); text-decoration: none; }

/* ── Parallax slider: always fill parent width ─── */
.ps-slider-wrap { display:block; width:100%!important; min-width:0!important; max-width:100%!important; box-sizing:border-box; }
.gb-html .ps-slider-wrap, .gb-column .ps-slider-wrap { width:100%!important; }
.gb-full-width .ps-slider-wrap { width:100%!important; }

/* ── Responsive ────────────────────────────────── */
@media (max-width: 700px) {
    .posts-grid { grid-template-columns: 1fr; }
    .gc-header-inner { padding: 0 16px; }
    .hero, .post-hero, .cat-hero { padding-left: 16px; padding-right: 16px; }
    .post-content { padding-left: 16px; padding-right: 16px; font-size: 16px; }
    .wrap { padding: 0 16px; }
    .gc-nav { gap: 1rem; }
    .gc-footer-content { grid-template-columns: 1fr; }
}
</style>
<?php if (function_exists('gc_emit')) gc_emit('theme.head'); ?>
</head>
<body>

<!-- ── Site Header ──────────────────────────────────────────── -->
<header class="gc-header">
  <div class="gc-header-inner">

    <?php
        // Header branding. Four states:
        //   logo only · text only · logo + text · nothing → default GoniCore mark.
        $__logo    = function_exists('gc_setting') ? trim((string) gc_setting('site_logo', ''))    : '';
        $__name    = function_exists('gc_setting') ? trim((string) gc_setting('site_name', ''))     : '';
        $__tagline = function_exists('gc_setting') ? trim((string) gc_setting('site_tagline', ''))  : '';
        $__hasText = ($__name !== '' || $__tagline !== '');
        $__hasBrand = ($__logo !== '' || $__hasText);
    ?>
    <a href="<?= e($base ?? '') ?>/" class="gc-logo" aria-label="<?= e($__name !== '' ? $__name : (string)($siteName ?? 'GoniCore')) ?>">
      <?php if (!$__hasBrand): ?>
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100" width="110" height="55" aria-hidden="true">
        <rect x="15" y="26" width="48" height="48" rx="10" fill="none" stroke="#0F172A" stroke-width="5"/>
        <rect x="27" y="38" width="24" height="24" rx="6" fill="#10B27C"/>
        <text x="80" y="46" font-family="system-ui" font-size="28" font-weight="900" fill="#0F172A">Goni</text>
        <text x="80" y="74" font-family="system-ui" font-size="28" font-weight="300" fill="#10B27C">Core</text>
      </svg>
      <?php else: ?>
        <?php if ($__logo !== ''): ?>
        <img class="gc-logo-img" src="<?= e($base ?? '') ?>/storage/media/<?= e($__logo) ?>" alt="<?= e($__name !== '' ? $__name : 'Logo') ?>">
        <?php endif ?>
        <?php if ($__hasText): ?>
        <span class="gc-brand">
          <?php if ($__name !== ''): ?><span class="gc-brand-name"><?= e($__name) ?></span><?php endif ?>
          <?php if ($__tagline !== ''): ?><span class="gc-brand-tagline"><?= e($__tagline) ?></span><?php endif ?>
        </span>
        <?php endif ?>
      <?php endif ?>
    </a>

    <!-- Right cluster: menu (right-aligned) → cart/store icons → language switcher -->
    <div class="gc-header-right">

      <?php
      $renderedNav = isset($menuService) ? $menuService->render('primary', 'gc-nav') : null;
      if ($renderedNav): echo $renderedNav;
      else: ?>
      <ul class="gc-nav">
        <li><a href="<?= e($base ?? '') ?>/">Home</a></li>
        <?php foreach ($categories ?? [] as $cat): ?>
        <li><a href="<?= e($base ?? '') ?>/category/<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></a></li>
        <?php endforeach ?>
      </ul>
      <?php endif ?>

      <?php /* Plugin icons (e.g. the store cart) sit LEFT of the language switcher. */ ?>
      <?php if (function_exists('gc_emit')) gc_emit('theme.nav.extra', $base ?? ''); ?>

      <?php
      $activeLangs = isset($langService) ? $langService->activeLanguages() : [];
      $currentLang = isset($langService) ? $langService->currentCode() : 'en';
      if (count($activeLangs) > 1): ?>
      <div class="lang-switcher">
        <button class="lang-btn" id="langBtn" aria-haspopup="true">
          <?php foreach ($activeLangs as $lx) {
              if ($lx['code'] === $currentLang) {
                  echo e($lx['flag'] ?? '🌐') . ' ' . e(strtoupper($lx['code']));
                  break;
              }
          } ?> <span class="material-symbols-outlined" style="font-size:16px;opacity:.6">expand_more</span>
        </button>
        <div class="lang-dropdown" id="langDrop" role="menu">
          <?php foreach ($activeLangs as $lx): ?>
          <a href="<?= e($base ?? '') ?>/lang/switch/<?= e($lx['code']) ?>"
             class="lang-option<?= $lx['code'] === $currentLang ? ' active' : '' ?>"
             role="menuitem">
            <?= e($lx['flag'] ?? '🌐') ?> <?= e($lx['native'] ?? $lx['name']) ?>
          </a>
          <?php endforeach ?>
        </div>
      </div>
      <?php endif ?>

    </div>

  </div>
</header>
