<?php
/** @var array<string,mixed> $post */
/** @var string $template */

$renderedContent = $shortcodeManager
    ? $shortcodeManager->process((string) $post['content'])
    : (string) $post['content'];

$isFullWidth = ($template === 'full-width');
$maxWidth    = $isFullWidth ? '100%' : '820px';
$hasCover    = !empty($post['featured_image']);
?>

<?php if ($hasCover): ?>
<div class="page-cover-wrap">
  <img src="<?= e((string)$post['featured_image']) ?>"
       alt="<?= e($post['title']) ?>"
       class="page-cover-img">
</div>
<?php endif ?>

<article class="gc-page" style="max-width:<?= $maxWidth ?>;margin:0 auto;padding:<?= $hasCover ? '32px' : '48px' ?> 24px 64px">
    <header style="margin-bottom:36px;padding-bottom:28px;border-bottom:1px solid var(--border)">
        <h1 style="font-size:clamp(28px,4vw,44px);font-weight:900;letter-spacing:-1px;line-height:1.15;color:var(--text)">
            <?= e($post['title']) ?>
        </h1>
        <?php if (!empty($post['excerpt'])): ?>
        <p style="margin-top:14px;font-size:17px;color:var(--muted);line-height:1.6;max-width:640px">
            <?= e($post['excerpt']) ?>
        </p>
        <?php endif ?>
    </header>

    <div class="post-content">
        <?= $renderedContent ?>
    </div>
</article>
