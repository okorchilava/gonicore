<?php
$pageTitle = 'My Profile';
$activeNav = 'profile';
$me        = $profileUser ?? $user ?? [];
$gravatarHash = md5(strtolower(trim((string)($me['email'] ?? ''))));
$gravatarUrl  = 'https://www.gravatar.com/avatar/' . $gravatarHash . '?s=128&d=404';
$initial = strtoupper(substr((string)($me['name'] ?? 'U'), 0, 1));
?>

<?php if (!empty($success ?? null)): ?>
<div id="gc-flash" data-msg="<?= e($success) ?>" data-icon="success" style="display:none"></div>
<?php endif ?>
<?php if (!empty($error ?? null)): ?>
<div id="gc-flash" data-msg="<?= e($error) ?>" data-icon="error" style="display:none"></div>
<?php endif ?>

<div style="max-width:680px;display:flex;flex-direction:column;gap:16px">

    <!-- Avatar + name header -->
    <div class="card">
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
            <div class="card-header"><h3>Profile Information</h3></div>
            <div class="card-body" style="padding:20px;display:flex;flex-direction:column;gap:14px">

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="name" class="form-input"
                               value="<?= e((string)($me['name'] ?? '')) ?>" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-input" style="font-family:monospace"
                               value="<?= e((string)($me['username'] ?? '')) ?>" placeholder="Optional">
                    </div>
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" class="form-input"
                               value="<?= e((string)($me['email'] ?? '')) ?>" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" class="form-input"
                               value="<?= e((string)($me['phone'] ?? '')) ?>" placeholder="Optional">
                    </div>
                </div>

                <div>
                    <button type="submit" class="btn btn-primary" style="padding:9px 24px">Save Profile</button>
                </div>
            </div>
        </div>
    </form>

    <!-- Password form -->
    <form method="POST" action="<?= e($base) ?>/manage/profile">
        <input type="hidden" name="_section" value="password">
        <div class="card">
            <div class="card-header"><h3>Change Password</h3></div>
            <div class="card-body" style="padding:20px;display:flex;flex-direction:column;gap:14px">
                <div class="form-group" style="margin:0">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-input"
                           autocomplete="current-password" required>
                </div>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
                    <div class="form-group" style="margin:0">
                        <label class="form-label">New Password</label>
                        <input type="password" name="password" class="form-input"
                               autocomplete="new-password" placeholder="Min 8 characters" required>
                    </div>
                    <div class="form-group" style="margin:0">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" name="password_confirm" class="form-input"
                               autocomplete="new-password" required>
                    </div>
                </div>
                <div>
                    <button type="submit" class="btn btn-primary" style="padding:9px 24px">Update Password</button>
                </div>
            </div>
        </div>
    </form>

    <!-- Danger zone -->
    <div class="card" style="border-color:#fecaca">
        <div class="card-header" style="background:#fef2f2"><h3 style="color:var(--danger)">Danger Zone</h3></div>
        <div class="card-body" style="padding:20px">
            <p style="font-size:13px;color:var(--muted);margin-bottom:14px">Sign out of all sessions on this device.</p>
            <a href="<?= e($base) ?>/logout" class="btn btn-danger" style="font-size:13px">Sign Out</a>
        </div>
    </div>

</div>
