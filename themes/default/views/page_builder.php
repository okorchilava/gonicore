<?php
/**
 * GoniBuilder — Frontend template.
 *
 * Rendered directly by ThemeController (not via view()), so we include
 * the theme partials here to keep header/footer consistent site-wide.
 *
 * Variables: $post, $builderHtml, $base, $siteName, $siteTagline,
 *            $categories, $lang, $languages, $langService
 * Globals:   $menuServiceInstance, $widgetServiceInstance
 */

// Bridge globals to the local names partials expect
global $menuServiceInstance, $widgetServiceInstance;
$menuService   = $menuServiceInstance   ?? null;
$widgetService = $widgetServiceInstance ?? null;

// Title for <head>
$pageTitle = (string)($post['title'] ?? '');

$_partialsDir = __DIR__ . '/partials';
include $_partialsDir . '/header.php';
?>

<style>
/* ── Builder page wrapper ────────────────────────── */
.gb-page{width:100%;padding-top:72px}

/* ── Sections ────────────────────────────────────── */
.gb-section{width:100%;position:relative}
.gb-section-inner{display:flex;flex-wrap:wrap;max-width:1200px;margin:0 auto;padding:0 20px;box-sizing:border-box}

/* Full-width: break out of any container constraints */
.gb-full-width{width:100%}
.gb-full-width>.gb-section-inner{max-width:100%!important;padding:0!important;width:100%}
.gb-full-width .gb-column{padding:0!important;box-sizing:border-box}

.gb-column{padding:0 12px;box-sizing:border-box;min-width:0}
.gb-heading{margin-bottom:.5em;line-height:1.25}
.gb-text{line-height:1.75}
.gb-button,.gb-image,.gb-spacer,.gb-divider,.gb-icon-box,
.gb-counter,.gb-alert,.gb-gallery,.gb-posts-grid,.gb-video,.gb-html{margin:8px 0}
.gb-counter-num{display:inline-block}
@media(max-width:768px){
  .gb-column{flex:0 0 100%!important;max-width:100%!important}
  .gb-section-inner{padding:0 16px}
}
</style>

<?php if (!empty($post['featured_image'])): ?>
<div class="post-featured-wrap">
  <img src="<?= e((string)$post['featured_image']) ?>"
       alt="<?= e((string)($post['title'] ?? '')) ?>"
       class="post-featured-img">
</div>
<?php endif ?>

<!-- Builder content -->
<?= $builderHtml ?>

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

<?php include $_partialsDir . '/footer.php'; ?>
