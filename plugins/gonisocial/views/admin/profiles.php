<?php
$pageTitle     = 'GoniSocial — სოციალური პროფილები';
$activeNav     = 'gonisocial-profiles';
$topbarActions = '<a href="' . e($base) . '/manage/gonisocial/profiles/form" class="btn btn-primary" style="font-size:13px">+ პროფილის დამატება</a>';

$allNetworks = \GoniSocial\GoniSocialService::PROFILE_NETWORKS;
$netColors   = array_combine(
    array_keys($allNetworks),
    array_column(array_values($allNetworks), 'color')
);
?>

<?php if ($saved): ?>
<div class="alert alert-success" style="margin-bottom:14px">✓ პროფილი შენახულია.</div>
<?php endif ?>
<?php if ($deleted): ?>
<div class="alert alert-success" style="margin-bottom:14px">🗑 პროფილი წაიშალა.</div>
<?php endif ?>

<?php if (empty($profiles)): ?>
<div class="card">
    <div class="card-body" style="text-align:center;padding:40px 20px;color:var(--muted)">
        <div style="font-size:36px;margin-bottom:10px">👤</div>
        <div style="font-size:15px;margin-bottom:4px">სოციალური პროფილები ჯერ არ არის.</div>
        <div style="font-size:13px;margin-bottom:18px">დაამატე Facebook, Instagram, YouTube... ბმულები.</div>
        <a href="<?= e($base) ?>/manage/gonisocial/profiles/form" class="btn btn-primary">+ პირველი პროფილის დამატება</a>
    </div>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:14px">
    <?php foreach ($profiles as $p):
        $net     = (string)$p['network'];
        $color   = $netColors[$net] ?? '#475569';
        $netName = $allNetworks[$net]['name'] ?? ucfirst($net);
        $dispName = trim((string)$p['display_name']) ?: $netName;
        $handle  = (string)$p['handle'];
        $url     = (string)$p['url'];
        $active  = (int)$p['active'] === 1;
    ?>
    <div class="card" style="border-top:3px solid <?= e($color) ?>">
        <div class="card-body" style="display:flex;flex-direction:column;gap:10px;padding:16px 18px">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:8px">
                <div style="display:flex;align-items:center;gap:10px">
                    <div style="width:36px;height:36px;background:<?= e($color) ?>;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800;font-size:16px;flex-shrink:0">
                        <?= strtoupper(substr($net, 0, 1)) ?>
                    </div>
                    <div>
                        <div style="font-weight:700;font-size:14px"><?= e($dispName) ?></div>
                        <div style="font-size:11.5px;color:var(--muted)"><?= e($netName) ?><?= $handle ? ' · @' . e($handle) : '' ?></div>
                    </div>
                </div>
                <span style="padding:3px 10px;border-radius:20px;font-size:11px;font-weight:700;
                             background:<?= $active ? '#dcfce7' : '#f1f5f9' ?>;
                             color:<?= $active ? '#15803d' : '#94a3b8' ?>">
                    <?= $active ? 'აქტ.' : 'გათ.' ?>
                </span>
            </div>

            <?php if ($url): ?>
            <a href="<?= e($url) ?>" target="_blank" rel="noopener"
               style="font-size:12px;color:#7c3aed;word-break:break-all;text-decoration:none">
                ↗ <?= e(strlen($url) > 45 ? substr($url, 0, 45) . '…' : $url) ?>
            </a>
            <?php endif ?>

            <div style="display:flex;gap:8px;margin-top:4px">
                <a href="<?= e($base) ?>/manage/gonisocial/profiles/form?id=<?= (int)$p['id'] ?>"
                   class="btn btn-ghost" style="font-size:12.5px;padding:5px 12px">✏ რედ.</a>

                <form method="POST" action="<?= e($base) ?>/manage/gonisocial/profiles/toggle" style="margin:0">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-ghost"
                            style="font-size:12.5px;padding:5px 12px;color:<?= $active ? '#ef4444' : '#10b981' ?>">
                        <?= $active ? '⏸ გათ.' : '▶ ჩართ.' ?>
                    </button>
                </form>

                <form method="POST" action="<?= e($base) ?>/manage/gonisocial/profiles/delete"
                      style="margin:0;margin-left:auto"
                      onsubmit="return confirm('<?= e($dispName) ?>-ის პროფილი წაიშლება?')">
                    <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                    <button type="submit" class="btn btn-ghost" style="font-size:12.5px;padding:5px 10px;color:#ef4444">🗑</button>
                </form>
            </div>
        </div>
    </div>
    <?php endforeach ?>
</div>

<div style="margin-top:14px;font-size:12.5px;color:var(--muted)">
    სულ: <?= count($profiles) ?> პროფილი · <?= count(array_filter($profiles, fn($p) => (int)$p['active'] === 1)) ?> აქტიური.
    <br>თემაში გამოყენება: <code>gonisocial_follow()</code> ან <code>gonisocial_follow('icon-only')</code>
</div>
<?php endif ?>
