<?php
$pageTitle = 'Users';
$activeNav = 'tickets-users';
$topbarActions = '';
?>
<div class="card">
    <div class="card-header" style="justify-content:space-between;flex-wrap:wrap;gap:10px">
        <h3>Plugin Users <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= number_format($total) ?>)</span></h3>
        <form method="GET" style="display:flex;gap:8px;align-items:center">
            <input type="text" name="q" value="<?= e($search) ?>"
                   class="form-input" placeholder="Search by email…"
                   style="font-size:13px;padding:6px 10px;width:220px">
            <button type="submit" class="btn btn-ghost" style="font-size:13px">Search</button>
            <?php if ($search): ?>
            <a href="<?= e($base) ?>/manage/tickets/users" class="btn btn-ghost" style="font-size:13px">✕ Clear</a>
            <?php endif ?>
        </form>
    </div>

    <?php if (empty($users)): ?>
    <div class="empty">
        <div class="empty-icon">👥</div>
        <h3><?= $search ? 'No users found for "' . e($search) . '"' : 'No registered users yet' ?></h3>
        <p style="color:var(--muted);font-size:13px">Users who register via the plugin frontend will appear here.</p>
    </div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th style="text-align:center">Bookings</th>
            <th>Registered</th>
        </tr></thead>
        <tbody>
        <?php foreach ($users as $u):
            $bookings = (int)($u['booking_count'] ?? 0);
        ?>
        <tr>
            <td style="color:var(--muted);font-size:12px"><?= (int)$u['id'] ?></td>
            <td>
                <div style="font-weight:600;font-size:13.5px"><?= e($u['name'] ?: '—') ?></div>
            </td>
            <td style="font-size:13px;color:var(--muted)"><?= e($u['email']) ?></td>
            <td style="text-align:center">
                <?php if ($bookings > 0): ?>
                <span style="background:var(--accent)22;color:var(--accent);font-size:11px;font-weight:700;padding:2px 10px;border-radius:20px">
                    <?= $bookings ?>
                </span>
                <?php else: ?>
                <span style="color:var(--muted);font-size:12px">—</span>
                <?php endif ?>
            </td>
            <td style="font-size:12px;color:var(--muted);white-space:nowrap">
                <?= date('d M Y', strtotime((string)$u['created_at'])) ?>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>

    <?php if ($pages > 1): ?>
    <div style="padding:16px;display:flex;gap:6px;justify-content:center">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?page=<?= $i ?><?= $search ? '&q=' . urlencode($search) : '' ?>"
           style="padding:5px 12px;border-radius:6px;border:1px solid var(--border);font-size:13px;text-decoration:none;<?= $i === $page ? 'background:var(--accent);color:#fff;border-color:var(--accent)' : '' ?>">
            <?= $i ?>
        </a>
        <?php endfor ?>
    </div>
    <?php endif ?>
    <?php endif ?>
</div>
