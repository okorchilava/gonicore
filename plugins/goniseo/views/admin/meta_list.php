<?php
$pageTitle     = 'GoniSEO — Meta Tags';
$activeNav     = 'goniseo-meta';
$topbarActions =
    '<a href="' . e($base) . '/manage/goniseo/meta/form" class="btn btn-primary" style="font-size:13px">+ ახალი Meta</a>';

$pages    = (int) ceil($total / $per);
$hasPrev  = $page > 1;
$hasNext  = $page < $pages;
?>
<style>
.gseo-robots-badge{font-size:11px;font-weight:700;border-radius:20px;padding:2px 8px;display:inline-block}
.gseo-robots-index{background:#d1fae5;color:#065f46}
.gseo-robots-noindex{background:#fef2f2;color:#991b1b}
</style>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:14px">✓ Meta შენახულია.</div>
<?php endif ?>
<?php if ($deleted): ?>
<div class="alert alert-success" style="margin-bottom:14px">✓ Meta წაიშალა.</div>
<?php endif ?>

<!-- Search + stats bar -->
<div style="display:flex;align-items:center;gap:12px;margin-bottom:16px;flex-wrap:wrap">
    <form method="GET" action="<?= e($base) ?>/manage/goniseo/meta"
          style="display:flex;gap:8px;flex:1;min-width:200px;max-width:400px">
        <input type="text" name="q" class="form-input" value="<?= e($search) ?>"
               placeholder="URL Path-ის ძიება..." style="flex:1">
        <button type="submit" class="btn btn-ghost">🔍</button>
        <?php if ($search !== ''): ?>
        <a href="<?= e($base) ?>/manage/goniseo/meta" class="btn btn-ghost">✕</a>
        <?php endif ?>
    </form>
    <div style="font-size:13px;color:var(--muted)">
        სულ <strong><?= number_format($total) ?></strong> შეყვანა
        <?= $search !== '' ? '(ძიება: <strong>' . e($search) . '</strong>)' : '' ?>
    </div>
</div>

<div class="card">
    <?php if (empty($rows)): ?>
    <div class="empty">
        <div class="empty-icon">🏷</div>
        <h3><?= $search !== '' ? 'ვერ მოიძებნა' : 'Meta შეყვანა არ არის' ?></h3>
        <p><?= $search !== '' ? '"' . e($search) . '" არარის Meta-ში.' : 'დაამატე URL-ების SEO meta.' ?></p>
        <?php if ($search === ''): ?>
        <a href="<?= e($base) ?>/manage/goniseo/meta/form" class="btn btn-primary">+ პირველი Meta</a>
        <?php endif ?>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
        <thead><tr>
            <th>URL Path</th>
            <th>SEO Title</th>
            <th>Description</th>
            <th style="text-align:center">Robots</th>
            <th style="text-align:center">OG</th>
            <th style="text-align:right">Actions</th>
        </tr></thead>
        <tbody>
        <?php foreach ($rows as $m):
            $hasTtl = trim((string)$m['title']) !== '';
            $hasDesc = trim((string)$m['description']) !== '';
            $hasOg   = trim((string)$m['og_image']) !== '' || trim((string)$m['og_title']) !== '';
            $robots  = trim((string)$m['robots']);
            $isNoindex = str_contains($robots, 'noindex');
        ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:8px">
                    <code style="font-size:12px;background:var(--border);padding:2px 8px;border-radius:5px;max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                        <?= e((string)$m['url_path']) ?>
                    </code>
                    <a href="<?= e((string)$m['url_path']) ?>" target="_blank"
                       style="color:var(--muted);font-size:13px;text-decoration:none" title="გვერდის გახსნა">↗</a>
                </div>
            </td>
            <td style="max-width:180px">
                <?php if ($hasTtl): ?>
                <span style="font-size:13px;font-weight:600"><?= e(mb_strimwidth((string)$m['title'], 0, 50, '…')) ?></span>
                <?php else: ?>
                <span style="color:var(--muted);font-size:12.5px;font-style:italic">default</span>
                <?php endif ?>
            </td>
            <td style="font-size:12.5px;color:var(--muted);max-width:200px">
                <?php if ($hasDesc): ?>
                <?= e(mb_strimwidth((string)$m['description'], 0, 80, '…')) ?>
                <?php else: ?>
                <span style="font-style:italic">—</span>
                <?php endif ?>
            </td>
            <td style="text-align:center">
                <?php if ($robots !== ''): ?>
                <span class="gseo-robots-badge <?= $isNoindex ? 'gseo-robots-noindex' : 'gseo-robots-index' ?>">
                    <?= e($robots) ?>
                </span>
                <?php else: ?>
                <span style="font-size:12px;color:var(--muted)">default</span>
                <?php endif ?>
            </td>
            <td style="text-align:center">
                <?= $hasOg ? '<span title="OG meta დაყენებული" style="font-size:16px">✓</span>' : '<span style="color:var(--muted);font-size:12px">—</span>' ?>
            </td>
            <td style="text-align:right">
                <div style="display:flex;gap:6px;justify-content:flex-end">
                    <a href="<?= e($base) ?>/manage/goniseo/meta/form?id=<?= (int)$m['id'] ?>"
                       class="btn btn-ghost" style="font-size:12px;padding:4px 10px">✏</a>
                    <form method="POST" action="<?= e($base) ?>/manage/goniseo/meta/delete"
                          onsubmit="return confirm('Meta წაიშლება?')">
                        <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                        <button type="submit" class="btn btn-ghost"
                                style="font-size:12px;padding:4px 10px;color:#ef4444">🗑</button>
                    </form>
                </div>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    </div>

    <?php if ($pages > 1): ?>
    <!-- Pagination -->
    <div style="display:flex;align-items:center;justify-content:center;gap:8px;padding:16px 20px;border-top:1px solid var(--border)">
        <?php if ($hasPrev): ?>
        <a href="?q=<?= urlencode($search) ?>&page=<?= $page - 1 ?>" class="btn btn-ghost" style="font-size:13px">← წინა</a>
        <?php endif ?>
        <span style="font-size:13px;color:var(--muted)">
            გვ. <?= $page ?> / <?= $pages ?>
        </span>
        <?php if ($hasNext): ?>
        <a href="?q=<?= urlencode($search) ?>&page=<?= $page + 1 ?>" class="btn btn-ghost" style="font-size:13px">შემდეგი →</a>
        <?php endif ?>
    </div>
    <?php endif ?>
    <?php endif ?>
</div>
