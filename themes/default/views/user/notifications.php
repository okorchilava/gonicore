<?php
/** @var array $notifList */
$pageTitle = 'Notifications';
$items = $notifList ?? [];
?>

<div class="up-card" style="padding:0;overflow:hidden">
  <div style="display:flex;align-items:center;justify-content:space-between;padding:18px 24px;border-bottom:1px solid var(--border)">
    <div>
      <h2 style="margin:0">Notifications</h2>
      <p class="sub" style="margin:2px 0 0"><?= count($items) ?> recent</p>
    </div>
    <?php if (!empty($items)): ?>
    <form method="post" action="<?= e($base) ?>/users/notifications/read-all" style="margin:0">
      <button type="submit" class="up-btn" style="background:var(--bg);color:var(--ink);padding:8px 14px;font-size:13px">Mark all read</button>
    </form>
    <?php endif ?>
  </div>

  <?php if (empty($items)): ?>
    <div style="padding:48px 24px;text-align:center;color:var(--muted)">
      <div style="font-size:34px">🔕</div>
      <p>You have no notifications yet.</p>
    </div>
  <?php else: ?>
    <ul style="list-style:none;margin:0;padding:0">
      <?php foreach ($items as $n):
        $isUnread = empty($n['read_at']);
        $own      = !empty($n['user_id']); // broadcasts can only be cleared via "mark all read"
      ?>
      <li style="display:flex;gap:14px;padding:16px 24px;border-bottom:1px solid var(--border);<?= $isUnread ? 'background:var(--accent-soft)' : '' ?>">
        <div style="font-size:20px;line-height:1.2"><?= e((string)($n['icon'] ?? '🔔')) ?></div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:600;font-size:14px"><?= e((string)($n['title'] ?? '')) ?></div>
          <?php if (!empty($n['message'])): ?>
          <div style="font-size:13px;color:var(--muted);margin-top:2px"><?= e((string)$n['message']) ?></div>
          <?php endif ?>
          <div style="font-size:11.5px;color:var(--muted);margin-top:5px"><?= e((string)($n['created_at'] ?? '')) ?></div>
        </div>
        <?php if ($isUnread && $own): ?>
        <form method="post" action="<?= e($base) ?>/users/notifications/<?= (int)$n['id'] ?>/read" style="margin:0">
          <button type="submit" title="Mark as read"
            style="background:none;border:1px solid var(--border);border-radius:8px;padding:5px 10px;font-size:12px;color:var(--muted);cursor:pointer">✓</button>
        </form>
        <?php elseif ($isUnread): ?>
        <span style="align-self:center;width:9px;height:9px;border-radius:50%;background:var(--accent)"></span>
        <?php endif ?>
      </li>
      <?php endforeach ?>
    </ul>
  <?php endif ?>
</div>

