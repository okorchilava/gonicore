<?php
/** @var array<string,mixed> $post */
$renderedContent = isset($shortcodeManager)
    ? $shortcodeManager->process((string) $post['content'])
    : (string) $post['content'];
?>
<div class="lp-content" style="max-width:960px;margin:0 auto;padding:48px 24px">
  <?= $renderedContent ?>
</div>
