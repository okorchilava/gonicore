<?php
/** @var array<string,mixed> $post */
global $shortcodeManager, $siteName;
$renderedContent = $shortcodeManager
    ? $shortcodeManager->process((string) $post['content'])
    : (string) $post['content'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($post['title']) ?> — <?= e($siteName ?? 'GoniCore') ?></title>
<style>
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:system-ui,-apple-system,'Segoe UI',sans-serif;color:#0f172a;line-height:1.65;background:#fff}
.lp-content{max-width:960px;margin:0 auto;padding:48px 24px}
h1,h2,h3{letter-spacing:-.4px;line-height:1.2}
a{color:#10B27C}
img{max-width:100%;border-radius:10px}
</style>
</head>
<body>
<main class="lp-content">
    <?= $renderedContent ?>
</main>
</body>
</html>
