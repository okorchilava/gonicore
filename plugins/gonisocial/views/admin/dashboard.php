<?php
$pageTitle     = 'GoniSocial — Dashboard';
$activeNav     = 'gonisocial-dashboard';
$topbarActions = '<a href="' . e($base) . '/manage/gonisocial/profiles/form" class="btn btn-primary" style="font-size:13px">+ პროფილის დამატება</a>';

$isEnabled    = ($settings['enabled']        ?? '1') === '1';
$ogEnabled    = ($settings['og_enabled']     ?? '1') === '1';
$shareEnabled = ($settings['share_enabled']  ?? '1') === '1';
$profileCount = (int)($stats['profile_count']        ?? 0);
$activeCount  = (int)($stats['active_profile_count'] ?? 0);
?>
<style>
.gsc-stat{background:var(--card-bg,#fff);border:1px solid var(--border);border-radius:12px;padding:18px 22px;display:flex;flex-direction:column;gap:4px}
.gsc-stat-val{font-size:28px;font-weight:800}
.gsc-stat-lbl{font-size:12.5px;color:var(--muted)}
.gsc-qcard{background:var(--card-bg,#fff);border:1px solid var(--border);border-radius:12px;padding:18px 22px;display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;transition:border-color .15s,box-shadow .15s}
.gsc-qcard:hover{border-color:#7c3aed;box-shadow:0 2px 12px rgba(124,58,237,.12)}
.gsc-qcard-icon{font-size:28px;flex-shrink:0}
</style>

<?php if (!$isEnabled): ?>
<div class="alert" style="background:#fef3c7;border:1px solid #fcd34d;color:#92400e;border-radius:10px;padding:12px 18px;margin-bottom:20px;font-size:13.5px">
    ⚠ GoniSocial პლაგინი გათიშულია.
    <a href="<?= e($base) ?>/manage/gonisocial/settings" style="color:#92400e;font-weight:700">პარამეტრებში ჩართვა →</a>
</div>
<?php endif ?>

<!-- Stats -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:14px;margin-bottom:24px">
    <div class="gsc-stat">
        <span class="gsc-stat-val" style="color:#7c3aed"><?= $profileCount ?></span>
        <span class="gsc-stat-lbl">👤 პროფილები სულ</span>
    </div>
    <div class="gsc-stat">
        <span class="gsc-stat-val" style="color:#10b981"><?= $activeCount ?></span>
        <span class="gsc-stat-lbl">✅ აქტიური პროფ.</span>
    </div>
    <div class="gsc-stat">
        <span class="gsc-stat-val" style="color:<?= $ogEnabled ? '#10b981' : '#ef4444' ?>"><?= $ogEnabled ? 'ON' : 'OFF' ?></span>
        <span class="gsc-stat-lbl">🌐 OG / Twitter</span>
    </div>
    <div class="gsc-stat">
        <span class="gsc-stat-val" style="color:<?= $shareEnabled ? '#10b981' : '#94a3b8' ?>"><?= $shareEnabled ? 'ON' : 'OFF' ?></span>
        <span class="gsc-stat-lbl">🔗 Share ღილაკები</span>
    </div>
</div>

<!-- Quick links -->
<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;margin-bottom:28px">
    <a href="<?= e($base) ?>/manage/gonisocial/share" class="gsc-qcard">
        <span class="gsc-qcard-icon">🔗</span>
        <div>
            <div style="font-weight:700;font-size:14px">გაზიარების ღილაკები</div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px">პოზიცია, ქსელები, სტილი</div>
        </div>
    </a>
    <a href="<?= e($base) ?>/manage/gonisocial/profiles" class="gsc-qcard">
        <span class="gsc-qcard-icon">👤</span>
        <div>
            <div style="font-weight:700;font-size:14px">სოციალური პროფილები</div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px"><?= $profileCount ?> პროფილი (<?= $activeCount ?> აქტიური)</div>
        </div>
    </a>
    <a href="<?= e($base) ?>/manage/gonisocial/settings" class="gsc-qcard">
        <span class="gsc-qcard-icon">⚙</span>
        <div>
            <div style="font-weight:700;font-size:14px">OG / Twitter Card</div>
            <div style="font-size:12px;color:var(--muted);margin-top:2px">Meta tag-ების პარამეტრები</div>
        </div>
    </a>
</div>

<!-- Active profiles preview -->
<?php if (!empty($profiles)): ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-header" style="justify-content:space-between">
        <h3>👤 აქტიური სოც. პროფილები</h3>
        <a href="<?= e($base) ?>/manage/gonisocial/profiles" style="font-size:12.5px;color:#7c3aed">ყველა →</a>
    </div>
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:10px">
        <?php
        $colors = \GoniSocial\GoniSocialService::PROFILE_NETWORKS;
        foreach ($profiles as $p):
            $net   = (string)$p['network'];
            $color = $colors[$net]['color'] ?? '#475569';
            $name  = trim((string)$p['display_name']) ?: ($colors[$net]['name'] ?? ucfirst($net));
        ?>
        <a href="<?= e((string)$p['url']) ?>" target="_blank" rel="noopener"
           style="display:inline-flex;align-items:center;gap:8px;background:<?= e($color) ?>;color:#fff;
                  border-radius:8px;padding:8px 14px;font-size:13px;font-weight:600;text-decoration:none">
            <span><?= e($name) ?></span>
        </a>
        <?php endforeach ?>
    </div>
</div>
<?php else: ?>
<div class="card" style="margin-bottom:20px">
    <div class="card-body" style="text-align:center;padding:30px 20px;color:var(--muted)">
        <div style="font-size:32px;margin-bottom:8px">👤</div>
        <div style="font-size:14px">სოციალური პროფილები არ არის დამატებული.</div>
        <a href="<?= e($base) ?>/manage/gonisocial/profiles/form" class="btn btn-primary" style="margin-top:14px;font-size:13px">+ პირველი პროფილის დამატება</a>
    </div>
</div>
<?php endif ?>

<!-- Info box -->
<div style="background:#f8fafc;border:1px solid var(--border);border-radius:10px;padding:16px 20px;font-size:12.5px;color:var(--muted);line-height:1.8">
    <strong style="color:#1e293b">GoniSocial — ფუნქციები:</strong><br>
    🌐 <strong>OG / Twitter Card</strong> — ავტომატური meta tag-ები სოციალური ქსელებისთვის.<br>
    🔗 <strong>Share ღილაკები</strong> — Floating ან Bottom Bar გაზიარების ვიჯეტი.<br>
    👤 <strong>Follow ღილაკები</strong> — თემაში გამოიყენე: <code>gonisocial_follow()</code><br>
    <?php if (function_exists('goniseo_head')): ?>
    ✅ <strong>GoniSEO გამოვლენილია</strong> — OG tags-ს GoniSEO მართავს; GoniSocial მხოლოდ share ღილაკებს ამუშავებს.
    <?php else: ?>
    ℹ GoniSEO არ არის — GoniSocial OG tags-ს თვითონ ამუშავებს.
    <?php endif ?>
</div>
