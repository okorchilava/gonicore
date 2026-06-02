<?php
/** @var string $base */
/** @var array<string,mixed> $catRow */
/** @var list<array<string,mixed>> $posts */
?>

<!-- Category hero -->
<?php
$catCover = null;
foreach ($posts as $_p) {
    if (!empty($_p['featured_image'])) { $catCover = $_p['featured_image']; break; }
}
?>
<div class="cat-hero<?= $catCover ? ' cat-hero-cover' : '' ?>"
  <?php if ($catCover): ?>style="background-image:url('<?= e($catCover) ?>');background-size:cover;background-position:center;"<?php endif ?>>
  <?php if ($catCover): ?><div class="post-hero-overlay"></div><?php endif ?>
  <div style="position:relative;z-index:1">
    <div class="cat-label"><?= t('category.label') ?></div>
    <h1 class="cat-hero-name"><?= e($catRow['name']) ?></h1>
    <p class="cat-hero-count"><?= count($posts) ?> <?= t('category.articles') ?></p>
  </div>
</div>

<div class="wrap">
  <div class="section">

    <?php if (!empty($posts)): ?>

    <p class="section-heading"><?= e($catRow['name']) ?> <?= t('category.articles') ?></p>

    <div class="posts-grid">
      <?php foreach ($posts as $p): ?>
      <article class="post-card">
        <?php if (!empty($p['featured_image'])): ?>
        <div class="post-card-img">
          <a href="<?= e($base) ?>/post/<?= e($p['slug']) ?>" tabindex="-1" aria-hidden="true">
            <img src="<?= e((string)$p['featured_image']) ?>" alt="<?= e($p['title']) ?>">
          </a>
        </div>
        <?php endif ?>
        <div class="post-card-body">
          <h2 class="post-card-title">
            <a href="<?= e($base) ?>/post/<?= e($p['slug']) ?>">
              <?= e($p['title']) ?>
            </a>
          </h2>

          <p class="post-card-excerpt">
            <?= e(excerpt((string)$p['content'])) ?>
          </p>

          <div class="post-card-footer">
            <time datetime="<?= e($p['created_at']) ?>">
              <?= e(fmt_date((string)$p['created_at'])) ?>
            </time>
            <a href="<?= e($base) ?>/post/<?= e($p['slug']) ?>" class="read-more">
              Read more →
            </a>
          </div>
        </div>
      </article>
      <?php endforeach ?>
    </div>

    <?php else: ?>

    <div class="empty">
      <div class="empty-icon">📂</div>
      <h3><?= t('category.empty') ?></h3>
      <p><?= e($catRow['name']) ?></p>
    </div>

    <?php endif ?>

    <div style="margin-top:48px">
      <a href="<?= e($base) ?>/" class="back-link"><?= t('category.back') ?></a>
    </div>

  </div>
</div>
