<?php
$pageTitle     = '2FA Security';
$activeNav     = '2fa';
$topbarActions = '';
?>

<?php if (!empty($success)): ?>
<div id="gc-flash" data-msg="<?= e($success) ?>" data-icon="success" style="display:none"></div>
<?php endif ?>
<?php if (!empty($error)): ?>
<div id="gc-flash" data-msg="<?= e($error) ?>" data-icon="error" style="display:none"></div>
<?php endif ?>

<div style="max-width:600px">

    <div class="card">
        <div class="card-header">
            <h3><?= $enabled ? '🔐' : '🔓' ?> Two-Factor Authentication
                <span class="badge <?= $enabled ? 'published' : 'draft' ?>" style="margin-left:8px;vertical-align:middle;font-size:11px">
                    <?= $enabled ? 'Enabled' : 'Disabled' ?>
                </span>
            </h3>
        </div>
        <div class="card-body">
            <p style="font-size:14px;color:var(--muted);margin-bottom:20px">
                <?= $enabled
                    ? 'Your account is protected with 2FA. A 6-digit code will be required on every login.'
                    : 'Enable 2FA to add an extra layer of security. You\'ll need an authenticator app (Google Authenticator, Authy, etc.).' ?>
            </p>

            <?php if ($enabled): ?>
            <p style="font-size:13.5px;color:var(--muted);margin-bottom:16px">
                Enter your current authenticator code to confirm disabling 2FA.
            </p>
            <form method="POST" action="<?= e($base) ?>/manage/2fa/disable" autocomplete="off">
                <div class="form-group">
                    <label class="form-label">Authenticator Code</label>
                    <input
                        type="text"
                        name="code"
                        class="form-input"
                        inputmode="numeric"
                        pattern="[0-9 ]*"
                        maxlength="7"
                        placeholder="000 000"
                        autofocus
                        autocomplete="one-time-code"
                        required
                        style="max-width:180px;font-size:20px;font-weight:700;letter-spacing:8px;text-align:center;font-family:'Courier New',monospace"
                    >
                </div>
                <button
                    type="button"
                    class="btn btn-danger"
                    onclick="gcConfirm(this,'Disable 2FA','This will remove 2FA protection from your account.','Disable','#ef4444')"
                >Disable 2FA</button>
            </form>
            <?php else: ?>
            <a href="<?= e($base) ?>/manage/2fa/setup" class="btn btn-primary">Set Up 2FA</a>
            <?php endif ?>
        </div>
    </div>

</div>
