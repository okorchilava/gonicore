<?php
/**
 * GoniCore Default Theme — Footer Partial
 *
 * Variables in scope: $base, $siteName, $widgetService, $menuService
 */
?>

<!-- ── Footer ──────────────────────────────────────────────── -->
<footer class="gc-footer">

  <?php
  $col1 = isset($widgetService) ? $widgetService->renderArea('footer-col-1') : '';
  $col2 = isset($widgetService) ? $widgetService->renderArea('footer-col-2') : '';
  $col3 = isset($widgetService) ? $widgetService->renderArea('footer-col-3') : '';
  $hasWidgets = $col1 || $col2 || $col3;
  ?>

  <?php if ($hasWidgets): ?>
  <div class="gc-footer-content">
    <div class="gc-footer-brand">
      <?php if ($col1): ?><div class="widget-area"><?= $col1 ?></div><?php endif ?>
    </div>
    <div class="gc-footer-links">
      <?php if ($col2): ?><div class="gc-footer-group"><?= $col2 ?></div><?php endif ?>
      <?php if ($col3): ?><div class="gc-footer-group"><?= $col3 ?></div><?php endif ?>
    </div>
  </div>
  <?php endif ?>

  <div class="gc-footer-bottom">
    <span>&copy; <?= date('Y') ?> <?= e((string)($siteName ?? 'GoniCore')) ?>. All rights reserved.</span>
    <?php
    $footerNav = isset($menuService) ? $menuService->render('footer', 'gc-footer-nav') : null;
    if ($footerNav): ?>
    <style>.gc-footer-nav{display:flex;list-style:none;gap:16px;margin:0;padding:0}.gc-footer-nav a{color:#475569;font-size:12px}.gc-footer-nav a:hover{color:#10B27C}</style>
    <?= $footerNav ?>
    <?php endif ?>
    <a href="<?= e($base ?? '') ?>/" aria-label="<?= e((string)($siteName ?? 'GoniCore')) ?> Home">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 100" width="72" height="36" aria-hidden="true">
        <rect x="15" y="26" width="48" height="48" rx="10" fill="none" stroke="#475569" stroke-width="5"/>
        <rect x="27" y="38" width="24" height="24" rx="6" fill="#10B27C"/>
        <text x="80" y="46" font-family="system-ui" font-size="28" font-weight="900" fill="#94a3b8">Goni</text>
        <text x="80" y="74" font-family="system-ui" font-size="28" font-weight="300" fill="#10B27C">Core</text>
      </svg>
    </a>
  </div>

</footer>

<script>
document.addEventListener('click', function(e) {
  var btn  = document.getElementById('langBtn');
  var drop = document.getElementById('langDrop');
  if (btn && drop && !btn.contains(e.target) && !drop.contains(e.target)) {
    drop.classList.remove('show');
  }
});
var lb = document.getElementById('langBtn');
if (lb) lb.addEventListener('click', function(e) {
  e.stopPropagation();
  document.getElementById('langDrop').classList.toggle('show');
});
</script>

</body>
</html>
