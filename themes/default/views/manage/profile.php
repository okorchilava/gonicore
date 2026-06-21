<?php
$pageTitle = t('profile.title');
$activeNav = 'profile';
$me        = $profileUser ?? $user ?? [];
$gravatarHash = md5(strtolower(trim((string)($me['email'] ?? ''))));
$gravatarUrl  = 'https://www.gravatar.com/avatar/' . $gravatarHash . '?s=128&d=404';
$initial = strtoupper(substr((string)($me['name'] ?? 'U'), 0, 1));
?>

<style>
.profile-grid { display:grid; grid-template-columns:1fr 1fr; gap:16px; align-items:start; }
.profile-grid > .profile-full { grid-column:1 / -1; }
.profile-grid > form, .profile-grid > .card { margin:0; }
@media (max-width: 900px) { .profile-grid { grid-template-columns:1fr; } }
</style>
<div class="profile-grid">

    <!-- Avatar + name header -->
    <div class="card profile-full">
        <div class="card-body" style="padding:24px;display:flex;align-items:center;gap:20px">
            <div style="position:relative;flex-shrink:0">
                <img src="<?= $gravatarUrl ?>"
                     id="profileAvatar"
                     alt="<?= e((string)($me['name'] ?? '')) ?>"
                     style="width:80px;height:80px;border-radius:50%;object-fit:cover;border:3px solid var(--border)"
                     onerror="this.style.display='none';document.getElementById('profileInitialBig').style.display='flex'">
                <div id="profileInitialBig"
                     style="display:none;width:80px;height:80px;border-radius:50%;background:linear-gradient(135deg,var(--accent),var(--info));align-items:center;justify-content:center;font-size:28px;font-weight:900;color:#fff">
                    <?= $initial ?>
                </div>
            </div>
            <div>
                <div style="font-size:20px;font-weight:800;color:var(--text)"><?= e((string)($me['name'] ?? '')) ?></div>
                <div style="font-size:13px;color:var(--muted);margin-top:2px"><?= e((string)($me['email'] ?? '')) ?></div>
                <div style="margin-top:6px"><span class="badge <?= e((string)($me['role'] ?? '')) ?>"><?= e((string)($me['role'] ?? '')) ?></span></div>
            </div>
            <div style="margin-left:auto;font-size:12px;color:var(--muted);text-align:right">
                <div>Member since</div>
                <div style="font-weight:600;color:var(--text)"><?= e(fmt_date((string)($me['created_at'] ?? ''))) ?></div>
                <div style="margin-top:4px;font-size:11px">Avatar via <a href="https://gravatar.com" target="_blank" rel="noopener">Gravatar</a></div>
            </div>
        </div>
    </div>

    <!-- Profile info form -->
    <form method="POST" action="<?= e($base) ?>/manage/profile">
        <input type="hidden" name="_section" value="info">
        <div class="card">
            <div class="card-header"><h3><?= e(t('profile.info')) ?></h3></div>
            <div class="card-body" style="padding:20px;display:flex;flex-direction:column;gap:14px">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label"><?= e(t('profile.name')) ?></label>
                        <input type="text" name="name" class="form-input"
                               value="<?= e((string)($me['name'] ?? '')) ?>" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label"><?= e(t('profile.username')) ?></label>
                        <input type="text" name="username" class="form-input" style="font-family:monospace"
                               value="<?= e((string)($me['username'] ?? '')) ?>">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label"><?= e(t('profile.email')) ?></label>
                        <input type="email" name="email" class="form-input"
                               value="<?= e((string)($me['email'] ?? '')) ?>" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label"><?= e(t('profile.phone')) ?></label>
                        <input type="text" name="phone" class="form-input"
                               value="<?= e((string)($me['phone'] ?? '')) ?>">
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary" style="padding:9px 24px"><?= e(t('admin.save')) ?></button>
                </div>
            </div>
        </div>
    </form>

    <!-- Password form -->
    <form method="POST" action="<?= e($base) ?>/manage/profile">
        <input type="hidden" name="_section" value="password">
        <div class="card">
            <div class="card-header"><h3><?= e(t('profile.password')) ?></h3></div>
            <div class="card-body" style="padding:20px;display:flex;flex-direction:column;gap:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label"><?= e(t('profile.current_pass')) ?></label>
                    <input type="password" name="current_password" class="form-input"
                           autocomplete="current-password" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label"><?= e(t('profile.new_pass')) ?></label>
                        <input type="password" name="password" class="form-input"
                               autocomplete="new-password" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label"><?= e(t('profile.confirm_pass')) ?></label>
                        <input type="password" name="password_confirm" class="form-input"
                               autocomplete="new-password" required>
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="padding:9px 24px"><?= e(t('admin.update')) ?></button>
                </div>
            </div>
        </div>
    </form>

    <style>
    .gc-toggle{position:relative;display:inline-flex;align-items:center;cursor:pointer}
    .gc-toggle input[type=checkbox]{position:absolute;opacity:0;width:0;height:0}
    .gc-toggle input[type=hidden]+input[type=checkbox]{position:absolute}
    .gc-toggle-track{width:40px;height:22px;background:#cbd5e1;border-radius:11px;transition:background .2s;position:relative}
    .gc-toggle input[type=checkbox]:checked~.gc-toggle-track{background:var(--accent)}
    .gc-toggle-thumb{position:absolute;top:3px;left:3px;width:16px;height:16px;background:#fff;border-radius:50%;transition:transform .2s;box-shadow:0 1px 3px rgba(0,0,0,.2)}
    .gc-toggle input[type=checkbox]:checked~.gc-toggle-track .gc-toggle-thumb{transform:translateX(18px)}
    </style>

    <!-- Email Notifications -->
    <div class="card">
        <div class="card-header"><h3>🔔 <?= e(t('settings.notifications')) ?></h3></div>
        <div class="card-body">
            <form method="POST" action="<?= e($base) ?>/manage/profile/notifications"
                  style="display:flex;align-items:center;justify-content:space-between;gap:16px;flex-wrap:wrap">
                <div>
                    <div style="font-size:14px;font-weight:600;color:var(--text);margin-bottom:3px">Login security alerts</div>
                    <div style="font-size:13px;color:var(--muted)">
                        Receive an email each time a new sign-in is detected on your account.
                    </div>
                </div>
                <label class="gc-toggle" style="flex-shrink:0"
                       onchange="this.closest('form').submit()">
                    <input type="hidden"   name="email_notifications" value="">
                    <input type="checkbox" name="email_notifications" value="1"
                           <?= ($me['email_notifications'] ?? 1) ? 'checked' : '' ?>>
                    <span class="gc-toggle-track"><span class="gc-toggle-thumb"></span></span>
                </label>
            </form>
        </div>
    </div>

    <?php if (function_exists('gc_emit')) gc_emit('manage.profile.cards', $me, $base); ?>

    <!-- Danger zone -->
    <div class="card" style="border-color:#fecaca">
        <div class="card-header" style="background:#fef2f2"><h3 style="color:var(--danger)">Danger Zone</h3></div>
        <div class="card-body" style="padding:20px">
            <a href="<?= e($base) ?>/logout" class="btn btn-danger" style="font-size:13px"><?= e(t('admin.sign_out')) ?></a>
        </div>
    </div>

</div>
