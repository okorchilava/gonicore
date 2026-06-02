<?php
/** @var string $base */
/** @var list<array<string,mixed>> $posts */
/** @var int $page */
/** @var int $pages */
/** @var int $total */
/** @var string $siteName */
?>

<!-- Hero -->
<section class="hero">
  <div class="hero-bg-lines" aria-hidden="true"></div>
  <h1 class="hero-title">
    Welcome to <span><?= e($siteName) ?></span>
  </h1>
  <p class="hero-sub">A modern, API-first Headless CMS. Browse the latest articles below.</p>
</section>

<!-- Posts -->
<div class="wrap">
  <div class="section">

    <?php if (!empty($posts)): ?>

    <p class="section-heading">
      Latest Articles
      <span style="margin-left:auto;font-size:12px;letter-spacing:0;text-transform:none;color:var(--muted);font-weight:400">
        <?= $total ?> post<?= $total !== 1 ? 's' : '' ?>
      </span>
    </p>

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
          <?php if (!empty($p['category_id'])): ?>
          <?php
            // Find category name from the loaded categories array
            $catName = '';
            $catSlug = '';
            foreach ($categories as $c) {
                if ((int)$c['id'] === (int)$p['category_id']) {
                    $catName = $c['name'];
                    $catSlug = $c['slug'];
                    break;
                }
            }
          ?>
          <?php if ($catName): ?>
          <div class="post-card-cat">
            <a href="<?= e($base) ?>/category/<?= e($catSlug) ?>">
              <?= e($catName) ?>
            </a>
          </div>
          <?php endif ?>
          <?php endif ?>

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

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Pagination">
      <?php if ($page > 1): ?>
        <a href="<?= e($base) ?>/?page=<?= $page - 1 ?>" aria-label="Previous">‹</a>
      <?php else: ?>
        <span class="disabled" aria-hidden="true">‹</span>
      <?php endif ?>

      <?php for ($i = max(1, $page - 2); $i <= min($pages, $page + 2); $i++): ?>
        <?php if ($i === $page): ?>
          <span class="current"><?= $i ?></span>
        <?php else: ?>
          <a href="<?= e($base) ?>/?page=<?= $i ?>"><?= $i ?></a>
        <?php endif ?>
      <?php endfor ?>

      <?php if ($page < $pages): ?>
        <a href="<?= e($base) ?>/?page=<?= $page + 1 ?>" aria-label="Next">›</a>
      <?php else: ?>
        <span class="disabled" aria-hidden="true">›</span>
      <?php endif ?>
    </nav>
    <?php endif ?>

    <?php else: ?>

    <div class="empty">
      <div class="empty-icon">✍️</div>
      <h3>No posts yet</h3>
      <p>Publish your first post via the <a href="<?= e($base) ?>/api/v1/">API</a> to see it here.</p>
    </div>

    <?php endif ?>

  </div>
</div>
