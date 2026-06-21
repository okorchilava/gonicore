<?php

declare(strict_types=1);

/**
 * Plugin Name: GC Lazy Loader
 * Description: Lazy-loads frontend images & iframes with a smooth fade-in.
 * Version:     1.1.0
 * Author:      GoniCore
 *
 * How it works
 * ────────────
 * 1. A server-side filter adds loading="lazy" + decoding="async" to every
 *    <img> in post/page content (when "Lazy-load images" is on).
 * 2. A tiny frontend script does the same for images/iframes injected by the
 *    theme or other plugins, and fades each one in once it loads. Browsers
 *    without native lazy loading fall back gracefully.
 *
 * Settings live in the core `settings` table (no extra table). Manage them at
 * Manage → GC Lazy Loader.
 *
 * Scope from PluginLoader: $router, $container, $hooks, $pluginDir
 */

use GCLazyLoader\LazyLoaderAdmin;
use GoniCore\Core\Database\QueryBuilder;
use GoniCore\Core\Hooks\HookManager;
use GoniCore\Modules\Login\LoginService;
use GoniCore\Modules\Login\SessionManager;
use GoniCore\Modules\Settings\SettingsService;

// ── Autoloader ─────────────────────────────────────────────────────────────────
spl_autoload_register(static function (string $class) use ($pluginDir): void {
    if (!str_starts_with($class, 'GCLazyLoader\\')) return;
    $rel  = substr($class, strlen('GCLazyLoader\\'));
    $file = $pluginDir . '/src/' . str_replace('\\', '/', $rel) . '.php';
    if (is_file($file)) require_once $file;
});

// ── Settings helpers (default ON when unset) ───────────────────────────────────
$ll_on = static fn(string $key): bool => gc_setting($key, '1') === '1';

// ── 1. Server-side: add loading="lazy" to content images ───────────────────────
gc_filter('the_content', static function (string $html) use ($ll_on): string {
    if (!$ll_on('lazyload_images')) return $html;
    if ($html === '' || stripos($html, '<img') === false) return $html;

    return (string) preg_replace_callback(
        '/<img\b(?![^>]*\bloading=)[^>]*>/i',
        static function (array $m): string {
            return preg_replace('/<img\b/i', '<img loading="lazy" decoding="async"', $m[0], 1) ?? $m[0];
        },
        $html
    );
}, 20);

// ── 2. Frontend CSS — blur-up fade-in (visible on first paint / page load) ──────
gc_on('theme.head', static function () use ($ll_on): void {
    if (!$ll_on('lazyload_fade')) return;
    echo <<<'CSS'
<style id="gc-lazyloader-css">
img.gc-lazy    { opacity: 0; filter: blur(12px); transform: scale(1.02);
                 transition: opacity .55s ease, filter .55s ease, transform .55s ease; }
iframe.gc-lazy { opacity: 0; transition: opacity .55s ease; }
img.gc-lazy.gc-loaded, iframe.gc-lazy.gc-loaded { opacity: 1; filter: none; transform: none; }
/* Failsafe: if JS never marks them loaded (error/blocked), reveal after 4s
   so content is never permanently hidden. */
img.gc-lazy, iframe.gc-lazy { animation: gcLazySafety 0s linear 4s forwards; }
img.gc-lazy.gc-loaded, iframe.gc-lazy.gc-loaded { animation: none; }
@keyframes gcLazySafety { to { opacity: 1; filter: none; transform: none; } }
</style>
CSS;
}, 10);

// ── 3. Frontend JS — apply lazy loading + fade-in ──────────────────────────────
gc_on('theme.footer', static function () use ($ll_on): void {
    $images  = $ll_on('lazyload_images')  ? '1' : '0';
    $iframes = $ll_on('lazyload_iframes') ? '1' : '0';
    $fade    = $ll_on('lazyload_fade')    ? '1' : '0';
    if ($images === '0' && $iframes === '0' && $fade === '0') return;

    echo "<script id=\"gc-lazyloader-js\">\n";
    echo "window.GC_LAZY = {images:{$images},iframes:{$iframes},fade:{$fade}};\n";
    echo <<<'JS'
(function () {
  var cfg = window.GC_LAZY || {images:1,iframes:1,fade:1};

  function prep(el) {
    if (el.dataset.gcLazyDone) return;
    var isImg = el.tagName === 'IMG';
    if (isImg && !cfg.images) return;
    if (!isImg && !cfg.iframes) return;
    el.dataset.gcLazyDone = '1';

    function reveal() { if (cfg.fade) el.classList.add('gc-loaded'); }
    if (cfg.fade) el.classList.add('gc-lazy');

    if (isImg) {
      if (!el.hasAttribute('loading'))  el.setAttribute('loading', 'lazy');
      if (!el.hasAttribute('decoding')) el.setAttribute('decoding', 'async');
      if (el.complete && el.naturalWidth > 0) reveal();
      else { el.addEventListener('load', reveal); el.addEventListener('error', reveal); }
    } else {
      if (!el.hasAttribute('loading')) el.setAttribute('loading', 'lazy');
      el.addEventListener('load', reveal);
      setTimeout(reveal, 1200);
    }
  }

  function scan(root) {
    var sel = [];
    if (cfg.images)  sel.push('img:not([data-gc-lazy-done])');
    if (cfg.iframes) sel.push('iframe:not([data-gc-lazy-done])');
    if (!sel.length) return;
    (root || document).querySelectorAll(sel.join(',')).forEach(prep);
  }

  // Reveal everything still pending — used on full window load as a safety net.
  function revealAll() {
    document.querySelectorAll('.gc-lazy:not(.gc-loaded)').forEach(function (el) {
      if (el.tagName !== 'IMG' || (el.complete && el.naturalWidth > 0)) el.classList.add('gc-loaded');
    });
  }

  // Run as early as possible AND again on window load (covers above-the-fold
  // images on initial page load, and images that finished before JS ran).
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function () { scan(document); });
  } else { scan(document); }
  window.addEventListener('load', function () { scan(document); revealAll(); });

  if ('MutationObserver' in window) {
    var mo = new MutationObserver(function (muts) {
      for (var i = 0; i < muts.length; i++) {
        if (muts[i].addedNodes && muts[i].addedNodes.length) { scan(document); break; }
      }
    });
    mo.observe(document.documentElement, { childList: true, subtree: true });
  }
})();
JS;
    echo "\n</script>";
}, 10);

// ── 4. Page-transition loader — top progress bar + spinner ─────────────────────
// Shows a loading animation on the FIRST paint and, crucially, the instant a
// same-site link is clicked, so navigating page→page gives immediate feedback
// during the server round-trip (classic multi-page sites otherwise just "hang").
gc_on('theme.head', static function () use ($ll_on): void {
    if (!$ll_on('lazyload_pageloader')) return;

    $style = gc_setting('lazyload_loader_style', 'bar') === 'overlay' ? 'overlay' : 'bar';
    $color = (string) gc_setting('lazyload_color', '#10B27C');
    if (!preg_match('/^#[0-9a-fA-F]{3,8}$/', $color)) $color = '#10B27C';

    echo "<style id=\"gc-pagebar-css\">\n";
    echo \GCLazyLoader\LazyLoaderAdmin::spinnerCss() . "\n";
    // Apply the admin-chosen accent color to whichever spinner is in use.
    echo "#gc-pagespin .gcsp,#gc-ploverlay .gcsp{--c:{$color}}\n";

    if ($style === 'overlay') {
        echo "#gc-ploverlay{position:fixed;inset:0;z-index:99999;display:flex;align-items:center;justify-content:center;"
           . "background:rgba(255,255,255,.72);backdrop-filter:blur(2px);-webkit-backdrop-filter:blur(2px);"
           . "opacity:0;visibility:hidden;transition:opacity .3s ease,visibility .3s}";
        echo "#gc-ploverlay.gc-on{opacity:1;visibility:visible}";
        echo "#gc-ploverlay .gcsp{--s:56px}";
    } else {
        echo "#gc-pagebar{position:fixed;top:0;left:0;height:3px;width:0;z-index:99999;background:{$color};"
           . "box-shadow:0 0 10px {$color};opacity:0;transition:width .25s ease,opacity .4s ease;pointer-events:none}";
        echo "#gc-pagebar.gc-on{opacity:1}";
        echo "#gc-pagespin{position:fixed;top:14px;right:16px;z-index:99999;opacity:0;transition:opacity .3s ease;pointer-events:none}";
        echo "#gc-pagespin.gc-on{opacity:1}";
    }
    echo "\n</style>";
}, 11);

gc_on('theme.footer', static function () use ($ll_on): void {
    if (!$ll_on('lazyload_pageloader')) return;

    $style    = gc_setting('lazyload_loader_style', 'bar') === 'overlay' ? 'overlay' : 'bar';
    $spinner  = (string) gc_setting('lazyload_spinner', 'ring');
    $spinHtml = \GCLazyLoader\LazyLoaderAdmin::spinnerHtml($spinner);

    echo "<script id=\"gc-pagebar-js\">\n";
    echo "window.GC_PL=" . json_encode(['style' => $style, 'spin' => $spinHtml], JSON_UNESCAPED_SLASHES) . ";\n";
    echo <<<'JS'
(function () {
  var cfg = window.GC_PL || { style: 'bar', spin: '' };
  var bar, spin, overlay, trickle, val = 0, finishing = false;

  function ensure() {
    if (bar || overlay) return;
    var host = document.body || document.documentElement;
    if (cfg.style === 'overlay') {
      overlay = document.createElement('div'); overlay.id = 'gc-ploverlay';
      overlay.innerHTML = cfg.spin;
      host.appendChild(overlay);
    } else {
      bar = document.createElement('div');  bar.id = 'gc-pagebar';
      spin = document.createElement('div'); spin.id = 'gc-pagespin';
      spin.innerHTML = cfg.spin;
      host.appendChild(bar); host.appendChild(spin);
    }
  }
  function setW(n) { val = n; if (bar) bar.style.width = Math.min(100, n) + '%'; }

  function start() {
    ensure();
    if (cfg.style === 'overlay') { overlay.classList.add('gc-on'); return; }
    if (val > 0 && val < 100 && !finishing) return; // already running
    finishing = false;
    bar.classList.add('gc-on'); spin.classList.add('gc-on');
    setW(8);
    clearInterval(trickle);
    trickle = setInterval(function () { if (val < 90) setW(val + Math.random() * 8); }, 300);
  }
  function done() {
    ensure();
    if (cfg.style === 'overlay') { overlay.classList.remove('gc-on'); return; }
    finishing = true;
    clearInterval(trickle);
    setW(100);
    setTimeout(function () {
      bar.classList.remove('gc-on'); spin.classList.remove('gc-on');
      setTimeout(function () { setW(0); finishing = false; }, 400);
    }, 250);
  }

  // Current page finished loading.
  if (document.readyState === 'complete') { done(); }
  else { start(); window.addEventListener('load', done); }

  // Back/forward cache restore — make sure the loader isn't stuck.
  window.addEventListener('pageshow', function (e) { if (e.persisted) done(); });
  window.addEventListener('pagehide', function () { clearInterval(trickle); });

  // Show the loader the moment a same-site navigation begins.
  document.addEventListener('click', function (e) {
    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
    var a = e.target.closest ? e.target.closest('a[href]') : null;
    if (!a) return;
    if (a.target && a.target !== '_self') return;
    if (a.hasAttribute('download')) return;
    var href = a.getAttribute('href') || '';
    if (href === '' || href.charAt(0) === '#' || /^(mailto:|tel:|javascript:)/i.test(href)) return;
    var url; try { url = new URL(a.href, location.href); } catch (_) { return; }
    if (url.origin !== location.origin) return;
    if (url.pathname === location.pathname && url.search === location.search && url.hash) return;
    start();
  }, true);

  // GET form submits (search, filters) also navigate.
  document.addEventListener('submit', function (e) {
    var f = e.target;
    if (f && (f.method || 'get').toLowerCase() === 'get' && (!f.target || f.target === '_self')) start();
  }, true);
})();
JS;
    echo "\n</script>";
}, 11);

// ── 5. Admin: settings page + DI binding ───────────────────────────────────────
$container->bind(LazyLoaderAdmin::class, static fn($c) => new LazyLoaderAdmin(
    $c->get(LoginService::class),
    $c->get(SessionManager::class),
    $c->get(SettingsService::class),
    $c->get(QueryBuilder::class),
    $c->get(HookManager::class),
    $c->get(\GoniCore\Modules\Language\LanguageService::class),
    $c->get(\GoniCore\Modules\Language\LanguageRepository::class),
    $c->get(\GoniCore\Modules\Notifications\NotificationService::class),
));

$router->group('/manage', static function ($router): void {
    $router->get('/lazyloader',  [LazyLoaderAdmin::class, 'settings']);
    $router->post('/lazyloader', [LazyLoaderAdmin::class, 'save']);
});

// ── 6. Admin sidebar nav entry ─────────────────────────────────────────────────
gc_on('manage.sidebar.nav', static function (string $base, string $activeNav): void {
    $cls = $activeNav === 'lazyloader' ? 'active' : '';
    echo '<li><a href="' . htmlspecialchars($base . '/manage/lazyloader', ENT_QUOTES) . '" class="' . $cls . '">'
       . '<span class="nav-icon material-symbols-outlined">bolt</span> GC Lazy Loader'
       . '</a></li>';
}, 50);
