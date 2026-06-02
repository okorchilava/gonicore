<?php
$pageTitle = 'Users';
$activeNav = 'users';
$userList  = $users ?? [];
$currentId = (int)($currentUser['id'] ?? 0);

ob_start(); ?>
<a href="<?= e($base) ?>/manage/users/new" class="topbar-btn">+ New User</a>
<?php $topbarActions = ob_get_clean(); ?>

<?php if (!empty($success ?? null)): ?>
<div id="gc-flash" data-msg="<?= e($success) ?>" data-icon="success" style="display:none"></div>
<?php endif ?>
<?php if (!empty($error ?? null)): ?>
<div id="gc-flash" data-msg="<?= e($error) ?>" data-icon="error" style="display:none"></div>
<?php endif ?>

<div class="card">
    <div class="card-header">
        <h3><?= count($userList) ?> Users</h3>
        <input type="text" id="userSearch" placeholder="Search…"
               style="padding:6px 12px;border:1.5px solid var(--border);border-radius:7px;font-size:13px;outline:none;width:200px"
               oninput="filterUsers()">
    </div>
    <div class="table-wrap">
        <?php if (!empty($userList)): ?>
        <table id="usersTable">
            <thead>
                <tr>
                    <th>User</th>
                    <th>Email</th>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Joined</th>
                    <th style="text-align:right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($userList as $u):
                    $initial = strtoupper(substr((string)($u['name'] ?? 'U'), 0, 1));
                    $gravatarHash = md5(strtolower(trim((string)($u['email'] ?? ''))));
                    $gravatarUrl  = 'https://www.gravatar.com/avatar/' . $gravatarHash . '?s=64&d=404';
                    $isMe = (int)$u['id'] === $currentId;
                ?>
                <tr data-search="<?= e(strtolower($u['name'].' '.$u['email'].' '.($u['username']??''))) ?>">
                    <td>
                        <div style="display:flex;align-items:center;gap:10px">
                            <div style="position:relative;width:34px;height:34px;flex-shrink:0">
                                <img src="<?= $gravatarUrl ?>"
                                     alt="<?= e((string)($u['name'] ?? '')) ?>"
                                     style="width:34px;height:34px;border-radius:50%;object-fit:cover;border:2px solid var(--border)"
                                     onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                                <div style="display:none;width:34px;height:34px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--info));align-items:center;justify-content:center;font-size:13px;font-weight:700;color:#fff;position:absolute;inset:0">
                                    <?= $initial ?>
                                </div>
                            </div>
                            <div>
                                <div style="font-weight:600;font-size:13.5px"><?= e((string)($u['name'] ?? '')) ?></div>
                                <?php if ($isMe): ?>
                                <div style="font-size:10px;color:var(--accent);font-weight:700">You</div>
                                <?php endif ?>
                            </div>
                        </div>
                    </td>
                    <td style="color:var(--muted);font-size:13px"><?= e((string)($u['email'] ?? '')) ?></td>
                    <td style="color:var(--muted);font-size:13px;font-family:monospace"><?= $u['username'] ? e((string)$u['username']) : '—' ?></td>
                    <td><span class="badge <?= e((string)($u['role'] ?? '')) ?>"><?= e((string)($u['role'] ?? '')) ?></span></td>
                    <td style="color:var(--muted);font-size:12px;white-space:nowrap"><?= e(fmt_date((string)($u['created_at'] ?? ''))) ?></td>
                    <td style="text-align:right;white-space:nowrap">
                        <a href="<?= e($base) ?>/manage/users/<?= (int)$u['id'] ?>/edit"
                           class="btn btn-ghost" style="font-size:11px;padding:3px 10px;margin-right:4px">Edit</a>
                        <?php if (!$isMe): ?>
                        <form method="POST" action="<?= e($base) ?>/manage/users/<?= (int)$u['id'] ?>/delete" style="display:inline">
                            <button type="button" class="btn btn-danger" style="font-size:11px;padding:3px 10px"
                                onclick="gcConfirm(this,'Delete user?','<?= e(addslashes($u['name'])) ?> will be permanently removed.','Delete')">Delete</button>
                        </form>
                        <?php else: ?>
                        <a href="<?= e($base) ?>/manage/profile" class="btn btn-ghost" style="font-size:11px;padding:3px 10px">Profile</a>
                        <?php endif ?>
                    </td>
                </tr>
                <?php endforeach ?>
            </tbody>
        </table>
        <?php else: ?>
        <div class="empty"><div class="empty-icon">◉</div><h3>No users found</h3></div>
        <?php endif ?>
    </div>
</div>

<script>
function filterUsers() {
    var q = document.getElementById('userSearch').value.toLowerCase();
    document.querySelectorAll('#usersTable tbody tr').forEach(function(row) {
        row.style.display = (!q || (row.dataset.search || '').includes(q)) ? '' : 'none';
    });
}
</script>
