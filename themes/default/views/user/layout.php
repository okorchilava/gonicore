<?php
/**
 * Front-end user panel layout — self-contained, independent of the admin panel.
 *
 * Expected vars: $content, $base, $siteName, $user, $activeNav, $navItems,
 *                $unread, $flashMsg, $flashIcon, $csrfToken, $hooks
 *
 * Extensible: add sections by appending to $navItems in the controller, or let
 * a plugin add items by hooking 'user.panel.nav' (receives $base, $activeNav).
 */
$navItems  = $navItems ?? [];
$activeNav = $activeNav ?? 'profile';
$uName     = (string)($user['name'] ?? $user['username'] ?? 'User');
$uEmail    = (string)($user['email'] ?? '');
$initial   = strtoupper(mb_substr($uName, 0, 1));
$pageTitle = $pageTitle ?? ucfirst($activeNav);
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e((string)$siteName) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.min.css">
<style>
  :root{
    --bg:#f1f5f9; --card:#fff; --ink:#0f172a; --muted:#64748b; --border:#e2e8f0;
    --accent:#4f46e5; --accent-soft:rgba(79,70,229,.10); --danger:#dc2626; --ok:#16a34a;
    --radius:14px;
  }
  *{box-sizing:border-box}
  body{margin:0;font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif;
       background:var(--bg);color:var(--ink);line-height:1.5}
  a{color:inherit;text-decoration:none}
  .up-wrap{max-width:1080px;margin:0 auto;padding:28px 20px;display:grid;
           grid-template-columns:260px 1fr;gap:24px;align-items:start}
  /* Sidebar */
  .up-side{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
           padding:20px;position:sticky;top:28px}
  .up-user{display:flex;align-items:center;gap:12px;padding-bottom:16px;border-bottom:1px solid var(--border);margin-bottom:14px}
  .up-avatar{width:46px;height:46px;border-radius:50%;background:var(--accent);color:#fff;
             display:flex;align-items:center;justify-content:center;font-weight:700;font-size:19px;flex-shrink:0}
  .up-uname{font-weight:600;font-size:14px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .up-uemail{font-size:12px;color:var(--muted);white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
  .up-nav{list-style:none;margin:0;padding:0;display:flex;flex-direction:column;gap:4px}
  .up-nav a{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:9px;
            font-size:14px;font-weight:500;color:var(--muted)}
  .up-nav a:hover{background:var(--bg);color:var(--ink)}
  .up-nav a.active{background:var(--accent-soft);color:var(--accent)}
  .up-nav .ico{width:20px;text-align:center}
  .up-badge{margin-left:auto;background:var(--danger);color:#fff;border-radius:999px;
            font-size:11px;font-weight:700;min-width:18px;height:18px;display:inline-flex;
            align-items:center;justify-content:center;padding:0 5px}
  .up-logout{margin-top:14px;padding-top:14px;border-top:1px solid var(--border)}
  .up-logout a{color:var(--danger)}
  /* Main */
  .up-main{min-width:0}
  .up-top{display:flex;align-items:center;justify-content:space-between;margin-bottom:18px}
  .up-top h1{font-size:22px;margin:0}
  .up-top .back{font-size:13px;color:var(--muted)}
  .up-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);
           padding:24px;margin-bottom:20px}
  .up-card h2{font-size:16px;margin:0 0 4px}
  .up-card .sub{font-size:13px;color:var(--muted);margin-bottom:18px}
  .up-field{margin-bottom:16px}
  .up-field label{display:block;font-size:13px;font-weight:600;margin-bottom:6px}
  .up-field input{width:100%;padding:11px 13px;border:1.5px solid var(--border);border-radius:10px;
                  font-size:14px;outline:none;transition:border .15s}
  .up-field input:focus{border-color:var(--accent)}
  .up-btn{background:var(--accent);color:#fff;border:none;padding:11px 22px;border-radius:10px;
          font-size:14px;font-weight:600;cursor:pointer}
  .up-btn:hover{filter:brightness(1.05)}
  .up-grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  @media (max-width:820px){.up-wrap{grid-template-columns:1fr}.up-side{position:static}.up-grid2{grid-template-columns:1fr}}
</style>
</head>
<body>
<div class="up-wrap">
  <aside class="up-side">
    <div class="up-user">
      <div class="up-avatar"><?= e($initial) ?></div>
      <div style="min-width:0">
        <div class="up-uname"><?= e($uName) ?></div>
        <div class="up-uemail"><?= e($uEmail) ?></div>
      </div>
    </div>
    <ul class="up-nav">
      <?php foreach ($navItems as $it): ?>
      <li><a href="<?= e((string)$it['href']) ?>" class="<?= $activeNav === $it['key'] ? 'active' : '' ?>">
        <span class="ico"><?= e((string)($it['icon'] ?? '•')) ?></span>
        <span><?= e((string)$it['label']) ?></span>
        <?php if (($it['key'] ?? '') === 'notifications' && ($unread ?? 0) > 0): ?>
          <span class="up-badge"><?= (int)$unread > 99 ? '99+' : (int)$unread ?></span>
        <?php endif ?>
      </a></li>
      <?php endforeach ?>
      <?php
        // Plugin-added sections (mirrors the admin sidebar hook).
        if (isset($hooks) && $hooks instanceof \GoniCore\Core\Hooks\HookManager) {
            $hooks->emit('user.panel.nav', $base, $activeNav);
        }
      ?>
    </ul>
    <div class="up-logout">
      <ul class="up-nav"><li><a href="<?= e($base) ?>/logout"><span class="ico">↩</span> Sign out</a></li></ul>
    </div>
  </aside>

  <main class="up-main">
    <div class="up-top">
      <h1><?= e($pageTitle) ?></h1>
      <a class="back" href="<?= e($base) ?>/">← Back to site</a>
    </div>
    <?= $content ?>
  </main>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.10.8/sweetalert2.all.min.js"></script>
<?php if (!empty($flashMsg)): ?>
<script>
  Swal.fire({toast:true,position:'top-end',timer:3200,showConfirmButton:false,
    icon:<?= json_encode($flashIcon ?? 'success') ?>,title:<?= json_encode($flashMsg, JSON_UNESCAPED_UNICODE) ?>});
</script>
<?php endif ?>
<script>
  // Inject CSRF token into every POST form in the panel.
  (function(){
    var t = <?= json_encode((string)($csrfToken ?? '')) ?>;
    document.querySelectorAll('form[method="post"], form[method="POST"]').forEach(function(f){
      if (f.querySelector('input[name="_csrf"]')) return;
      var i=document.createElement('input'); i.type='hidden'; i.name='_csrf'; i.value=t; f.appendChild(i);
    });
  })();
</script>
</body>
</html>
