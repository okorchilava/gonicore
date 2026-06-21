<?php
/**
 * Broadcast notification composer.
 * Vars: $user, $recent (list of broadcast notifications), $base
 */
$pageTitle = t('nav.broadcast');
$activeNav = 'broadcast';
?>
<div style="max-width:760px">
    <div class="card">
        <div class="card-header"><h3><span class="material-symbols-outlined mi-sm" style="vertical-align:-3px">campaign</span> Send broadcast notification</h3></div>
        <div class="card-body">
            <p style="font-size:13px;color:var(--muted);margin-bottom:16px">
                Sends an in-app notification to <strong>all admins</strong> — it appears in the bell 🔔 for everyone.
            </p>
            <form method="POST" action="<?= e($base) ?>/manage/broadcast">
                <div class="form-group">
                    <label class="form-label">Title <span style="color:var(--danger)">*</span></label>
                    <input type="text" name="title" class="form-input" required maxlength="255"
                           placeholder="e.g. Scheduled maintenance tonight">
                </div>
                <div class="form-group">
                    <label class="form-label">Message</label>
                    <textarea name="message" class="form-textarea" style="min-height:120px"
                              placeholder="Optional details…"></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="padding:10px 26px">
                    <span class="material-symbols-outlined mi-sm">send</span> Send to all admins
                </button>
            </form>
        </div>
    </div>

    <?php if (!empty($recent)): ?>
    <div class="card" style="margin-top:20px;overflow:hidden">
        <div class="card-header"><h3>Recent broadcasts</h3></div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Title</th><th>Message</th><th style="width:170px">Sent</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($recent as $n): ?>
                    <tr>
                        <td style="font-weight:600"><?= e((string) ($n['title'] ?? '')) ?></td>
                        <td style="color:var(--muted)"><?= e((string) ($n['message'] ?? '')) ?></td>
                        <td style="white-space:nowrap;color:var(--muted);font-size:12.5px"><?= e(fmt_date((string) ($n['created_at'] ?? ''))) ?></td>
                    </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif ?>
</div>
