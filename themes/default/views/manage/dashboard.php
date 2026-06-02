<?php
$pageTitle = 'Dashboard';
$activeNav = 'dashboard';
$userId    = (int)($user['id'] ?? 0);

ob_start(); ?>
<a href="<?= e($base) ?>/manage/posts/new" class="topbar-btn">+ New Post</a>
<?php $topbarActions = ob_get_clean(); ?>

<!-- Stat cards -->
<div class="stat-grid">
    <div class="stat-card c-green">
        <div class="stat-card-label">Published</div>
        <div class="stat-card-value"><?= (int)($stats['posts_published'] ?? 0) ?></div>
        <div class="stat-card-sub">posts live</div>
        <div class="stat-card-icon">✦</div>
    </div>
    <div class="stat-card c-amber">
        <div class="stat-card-label">Drafts</div>
        <div class="stat-card-value"><?= (int)($stats['posts_draft'] ?? 0) ?></div>
        <div class="stat-card-sub">not published</div>
        <div class="stat-card-icon">✎</div>
    </div>
    <div class="stat-card c-slate">
        <div class="stat-card-label">Total Posts</div>
        <div class="stat-card-value"><?= (int)($stats['posts_total'] ?? 0) ?></div>
        <div class="stat-card-sub">all statuses</div>
        <div class="stat-card-icon">◫</div>
    </div>
    <div class="stat-card c-blue">
        <div class="stat-card-label">Categories</div>
        <div class="stat-card-value"><?= (int)($stats['categories'] ?? 0) ?></div>
        <div class="stat-card-sub">content groups</div>
        <div class="stat-card-icon">◈</div>
    </div>
    <div class="stat-card c-violet">
        <div class="stat-card-label">Users</div>
        <div class="stat-card-value"><?= (int)($stats['users'] ?? 0) ?></div>
        <div class="stat-card-sub">registered</div>
        <div class="stat-card-icon">◉</div>
    </div>
</div>

<!-- Sortable widget grid -->
<div class="widget-grid" id="widgetGrid">

    <!-- Recent Posts -->
    <div class="card widget" data-widget="recent-posts" draggable="true">
        <div class="card-header">
            <div class="widget-handle">
                <span class="drag-icon">⠿</span>
                <h3>Recent Posts</h3>
            </div>
            <a href="<?= e($base) ?>/manage/posts" class="btn btn-ghost" style="font-size:12px">View all</a>
        </div>
        <div class="card-body" style="padding:0 20px">
            <?php if (!empty($recentPosts)): ?>
            <?php foreach ($recentPosts as $rp): ?>
            <div class="recent-post">
                <span class="badge <?= e($rp['status']) ?>"><?= e($rp['status']) ?></span>
                <span class="recent-post-title"><?= e($rp['title']) ?></span>
                <span style="font-size:11px;color:var(--muted);white-space:nowrap"><?= e(fmt_date((string)$rp['created_at'])) ?></span>
                <a href="<?= e($base) ?>/manage/posts/<?= (int)$rp['id'] ?>" class="btn btn-ghost" style="padding:3px 8px;font-size:11px">Edit</a>
            </div>
            <?php endforeach ?>
            <?php else: ?>
            <div class="empty" style="padding:28px"><div class="empty-icon">✦</div><h3>No posts yet</h3></div>
            <?php endif ?>
        </div>
    </div>

    <!-- To-Do -->
    <div class="card widget" data-widget="todo" draggable="true">
        <div class="card-header">
            <div class="widget-handle"><span class="drag-icon">⠿</span><h3>To-Do</h3></div>
        </div>
        <div class="card-body">
            <form method="POST" action="<?= e($base) ?>/manage/todos" class="todo-form">
                <input type="text" name="title" placeholder="Add a task…" required>
                <button type="submit" class="btn btn-primary">Add</button>
            </form>
            <?php if (!empty($todoList)): ?>
            <ul class="todo-list">
                <?php foreach ($todoList as $t): ?>
                <li class="todo-item <?= $t['completed'] ? 'done' : '' ?>">
                    <form method="POST" action="<?= e($base) ?>/manage/todos/<?= (int)$t['id'] ?>/toggle" style="display:contents">
                        <button type="submit" style="background:none;border:none;cursor:pointer;padding:0;display:flex">
                            <span style="display:flex;width:16px;height:16px;border-radius:4px;border:2px solid <?= $t['completed'] ? 'var(--accent)' : 'var(--border)' ?>;background:<?= $t['completed'] ? 'var(--accent)' : 'transparent' ?>;align-items:center;justify-content:center;flex-shrink:0">
                                <?= $t['completed'] ? '<svg width="10" height="10" viewBox="0 0 10 10"><polyline points="1.5,5 4,7.5 8.5,2.5" stroke="#fff" stroke-width="1.5" fill="none"/></svg>' : '' ?>
                            </span>
                        </button>
                    </form>
                    <span><?= e($t['title']) ?></span>
                    <form method="POST" action="<?= e($base) ?>/manage/todos/<?= (int)$t['id'] ?>/delete" style="margin-left:auto">
                        <button type="button" class="todo-del"
                            onclick="gcConfirm(this,'Remove task?','','Remove','#64748b')">✕</button>
                    </form>
                </li>
                <?php endforeach ?>
            </ul>
            <?php else: ?>
            <p style="font-size:13px;color:var(--muted)">No tasks yet.</p>
            <?php endif ?>
        </div>
    </div>

    <!-- Activity Log -->
    <div class="card widget" data-widget="activity" draggable="true">
        <div class="card-header">
            <div class="widget-handle"><span class="drag-icon">⠿</span><h3>Activity Log</h3></div>
        </div>
        <div class="card-body" style="padding:0 20px">
            <?php if (!empty($activity)): ?>
            <ul class="activity-list">
                <?php foreach ($activity as $a):
                    $dotClass = str_contains((string)$a['action'], 'delete') ? 'del' : (str_contains((string)$a['action'], 'update') ? 'upd' : '');
                    $meta  = $a['meta'] ? json_decode((string)$a['meta'], true) : [];
                    $label = str_replace('.', ' ', (string)$a['action']);
                    $label = !empty($meta['title']) ? '"' . e((string)$meta['title']) . '" ' . $label : $label;
                ?>
                <li class="activity-item">
                    <div class="activity-dot <?= $dotClass ?>"></div>
                    <div style="flex:1;min-width:0">
                        <div style="font-weight:500"><?= $label ?></div>
                        <?php if ($a['user_name']): ?><div style="font-size:11px;color:var(--muted)">by <?= e((string)$a['user_name']) ?></div><?php endif ?>
                    </div>
                    <div class="activity-time"><?= e(fmt_date((string)$a['created_at'])) ?></div>
                </li>
                <?php endforeach ?>
            </ul>
            <?php else: ?>
            <div class="empty" style="padding:24px"><div class="empty-icon">◈</div><h3>No activity yet</h3></div>
            <?php endif ?>
        </div>
    </div>

    <!-- Server Stats -->
    <div class="card widget" data-widget="server" draggable="true">
        <div class="card-header">
            <div class="widget-handle"><span class="drag-icon">⠿</span><h3>System &amp; Server</h3></div>
        </div>
        <div class="card-body">
            <?php $s = $server ?? [] ?>
            <div style="margin-bottom:14px">
                <div style="font-size:11px;color:var(--muted);margin-bottom:4px">Disk usage — <?= e((string)($s['disk_pct'] ?? 0)) ?>%</div>
                <div class="disk-bar"><div class="disk-bar-fill <?= ($s['disk_pct'] ?? 0) > 90 ? 'danger' : (($s['disk_pct'] ?? 0) > 70 ? 'warn' : '') ?>" style="width:<?= (int)($s['disk_pct'] ?? 0) ?>%"></div></div>
                <div style="font-size:11px;color:var(--muted);margin-top:4px"><?= e((string)($s['disk_used'] ?? '')) ?> / <?= e((string)($s['disk_total'] ?? '')) ?></div>
            </div>
            <div class="server-grid">
                <?php $rows = [
                    'PHP'        => $s['php_version'] ?? '',
                    'SAPI'       => $s['php_sapi'] ?? '',
                    'OS'         => $s['os'] ?? '',
                    'Server'     => $s['server_sw'] ?? '',
                    'Mem limit'  => $s['mem_limit'] ?? '',
                    'Mem usage'  => $s['mem_usage'] ?? '',
                    'Mem peak'   => $s['mem_peak'] ?? '',
                    'OPcache'    => !empty($s['opcache']) ? '✓ on' : '✗ off',
                    'Extensions' => ($s['extensions'] ?? 0) . ' loaded',
                ]; ?>
                <?php foreach ($rows as $k => $v): ?>
                <div class="server-row">
                    <span class="server-key"><?= e($k) ?></span>
                    <span class="server-val"><?= e((string)$v) ?></span>
                </div>
                <?php endforeach ?>
            </div>
        </div>
    </div>

</div>

<script>
(function () {
    var STORAGE_KEY = 'gc_dashboard_<?= $userId ?>';
    var grid = document.getElementById('widgetGrid');
    if (!grid) return;

    /* ── Restore saved order ── */
    var saved = [];
    try { saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); } catch (e) {}

    if (saved.length) {
        var widgets = {};
        grid.querySelectorAll('[data-widget]').forEach(function (el) {
            widgets[el.dataset.widget] = el;
        });
        saved.forEach(function (id) {
            if (widgets[id]) grid.appendChild(widgets[id]);
        });
    }

    /* ── Drag & Drop ── */
    var dragged = null;

    grid.addEventListener('dragstart', function (e) {
        dragged = e.target.closest('[data-widget]');
        if (!dragged) return;
        setTimeout(function () { dragged.classList.add('dragging'); }, 0);
        e.dataTransfer.effectAllowed = 'move';
    });

    grid.addEventListener('dragend', function () {
        if (dragged) dragged.classList.remove('dragging');
        grid.querySelectorAll('[data-widget]').forEach(function (el) {
            el.classList.remove('drag-over');
        });
        saveOrder();
        dragged = null;
    });

    grid.addEventListener('dragover', function (e) {
        e.preventDefault();
        var target = e.target.closest('[data-widget]');
        if (!target || target === dragged) return;
        grid.querySelectorAll('[data-widget]').forEach(function (el) {
            el.classList.remove('drag-over');
        });
        target.classList.add('drag-over');

        /* Insert before or after target based on pointer position */
        var rect = target.getBoundingClientRect();
        var midX = rect.left + rect.width / 2;
        if (e.clientX < midX) {
            grid.insertBefore(dragged, target);
        } else {
            grid.insertBefore(dragged, target.nextSibling);
        }
    });

    grid.addEventListener('dragleave', function (e) {
        var target = e.target.closest('[data-widget]');
        if (target) target.classList.remove('drag-over');
    });

    grid.addEventListener('drop', function (e) { e.preventDefault(); });

    function saveOrder() {
        var order = [];
        grid.querySelectorAll('[data-widget]').forEach(function (el) {
            order.push(el.dataset.widget);
        });
        try { localStorage.setItem(STORAGE_KEY, JSON.stringify(order)); } catch (e) {}
    }
})();
</script>
