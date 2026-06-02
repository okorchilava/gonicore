<?php
/** @var string $base */
/** @var array<string,mixed> $catRow */
/** @var list<array<string,mixed>> $posts */
?>

<!-- Category hero -->
<div class="cat-hero">
  <div class="cat-label">Category</div>
  <h1 class="cat-hero-name"><?= e($catRow['name']) ?></h1>
  <p class="cat-hero-count">
    <?= count($posts) ?> published post<?= count($posts) !== 1 ? 's' : '' ?>
  </p>
</div>

<div class="wrap">
  <div class="section">

    <?php if (!empty($posts)): ?>

    <p class="section-heading"><?= e($catRow['name']) ?> Articles</p>

    <div class="posts-grid">
      <?php foreach ($posts as $p): ?>
      <article class="post-card">
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
      <h3>No posts in this category yet</h3>
      <p>Posts tagged <strong><?= e($catRow['name']) ?></strong> will appear here once published.</p>
    </div>

    <?php endif ?>

    <div style="margin-top:48px">
      <a href="<?= e($base) ?>/" class="back-link">← Back to all posts</a>
    </div>

  </div>
</div>
