<?php
/**
 * GoniCore Default Theme — Base Layout
 *
 * Composes: partials/header.php + $content + partials/footer.php
 * All variables set by ThemeController::view() are in scope here.
 */
$_partialsDir = __DIR__ . '/partials';
include $_partialsDir . '/header.php';
?>

<main class="gc-main" id="main-content">
  <?= $content ?? '' ?>
</main>

<?php include $_partialsDir . '/footer.php'; ?>
