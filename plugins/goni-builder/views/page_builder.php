<?php
/**
 * GoniBuilder — Frontend template.
 *
 * Uses the active theme's partials so header/footer stay consistent.
 * Variables: $post, $builderHtml, $base, $siteName, $siteTagline,
 *            $categories, $lang, $languages, $langService
 * Globals:   $menuServiceInstance, $widgetServiceInstance
 */
global $menuServiceInstance, $widgetServiceInstance;
$menuService   = $menuServiceInstance   ?? null;
$widgetService = $widgetServiceInstance ?? null;
$pageTitle     = (string)($post['title'] ?? '');

$_partialsDir = dirname(__DIR__, 3) . '/themes/default/views/partials';
include $_partialsDir . '/header.php';
?>

<style>
/* ── Body overrides for builder pages ────────────── */
body{margin:0;padding:0;overflow-x:hidden;align-items:stretch;gap:0}

/* ── Builder page wrapper ────────────────────────── */
.gb-page{flex:1;width:100%;padding-top:72px;box-sizing:border-box;margin:0}

/* ── Sections ────────────────────────────────────── */
.gb-section{width:100%;position:relative;margin:0}
.gb-section-inner{display:flex;flex-wrap:wrap;max-width:1200px;margin:0 auto;padding:0 20px;box-sizing:border-box;width:100%}

/* Full-width: all constraints removed (also set via inline style in PHP) */
.gb-full-width{width:100%;overflow:visible}
.gb-full-width>.gb-section-inner{max-width:100%!important;padding:0!important;width:100%!important;margin:0!important}
.gb-full-width .gb-column{padding:0!important;margin:0!important;box-sizing:border-box}

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

<?= $builderHtml ?>

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
      var startTime = null, duration = 1500;
      function step(ts) {
        if (!startTime) startTime = ts;
        var p = Math.min((ts - startTime) / duration, 1);
        el.textContent = Math.floor(p * target) + suffix;
        if (p < 1) requestAnimationFrame(step);
      }
      requestAnimationFrame(step);
      observer.unobserve(el);
    });
  }, { threshold: 0.3 });
  counters.forEach(function(c){ observer.observe(c); });
})();
</script>

<?php include $_partialsDir . '/footer.php'; ?>
