<?php
/** @var string $base */
/** @var array<string,mixed> $post */
/** @var array<string,mixed>|null $category */
/** @var string $siteName */
/** @var string $lang */

// Overlay translation if available for current language
if (!empty($lang) && $lang !== 'en' && isset($langService)) {
    $tr = $langService->getRepo()->getTranslation((int)$post['id'], $lang);
    if ($tr && $tr['status'] === 'published') {
        $post['title']   = $tr['title'];
        $post['content'] = $tr['content'];
    }
}

// Process shortcodes
$renderedContent = isset($shortcodeManager)
    ? $shortcodeManager->process((string) $post['content'])
    : (string) $post['content'];

$hasCover = !empty($post['featured_image']);
?>

<!-- Post hero -->
<div class="post-hero<?= $hasCover ? ' post-hero-cover' : '' ?>"
  <?php if ($hasCover): ?>
  style="background-image:url('<?= e((string)$post['featured_image']) ?>');background-size:cover;background-position:center;background-repeat:no-repeat;"
  <?php endif ?>>
  <?php if ($hasCover): ?>
  <div class="post-hero-overlay"></div>
  <?php else: ?>
  <div class="hero-bg-lines" aria-hidden="true"></div>
  <?php endif ?>
  <div class="post-hero-inner">
    <?php if (!($isPage ?? false) && $category): ?>
    <div class="post-cat-pill">
      <a href="<?= e($base) ?>/category/<?= e($category['slug']) ?>">
        📁 <?= e($category['name']) ?>
      </a>
    </div>
    <?php endif ?>

    <h1 class="post-hero-title"><?= e($post['title']) ?></h1>

    <?php if (!($isPage ?? false)): ?>
    <p class="post-byline">
      Published on <strong><?= e(fmt_date((string)$post['created_at'])) ?></strong>
      <?php if ($post['updated_at'] !== $post['created_at']): ?>
        &nbsp;&middot;&nbsp; Updated <?= e(fmt_date((string)$post['updated_at'])) ?>
      <?php endif ?>
    </p>
    <?php endif ?>
  </div>
</div>

<!-- Post content -->
<div class="post-content">
  <?php if (!($isPage ?? false)): ?>
  <a href="<?= e($base) ?>/" class="back-link">← Back to posts</a>
  <?php endif ?>

  <?= $renderedContent ?>

</div>
