<?php
$pageTitle     = 'GCSMS — Logs';
$activeNav     = 'gcsms-logs';
$topbarActions = '';

$statusColors = [
    'sent'   => ['#10b981', '#d1fae5'],
    'failed' => ['#ef4444', '#fef2f2'],
];
$typeIcons = [
    'single' => '✉',
    'bulk'   => '📨',
    'otp'    => '🔑',
];
?>
<style>
.gs-badge{display:inline-block;font-size:10.5px;font-weight:700;padding:2px 9px;border-radius:20px;text-transform:uppercase;letter-spacing:.4px}
.gs-phone{font-family:monospace;font-size:12.5px}
.gs-msg{max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:13px}
.gs-mid{font-family:monospace;font-size:11.5px;color:var(--muted)}
</style>

<div class="card">
    <div class="card-header" style="justify-content:space-between;flex-wrap:wrap;gap:10px">
        <h3>გაგზავნების ლოგი
            <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= number_format($total) ?>)</span>
        </h3>
        <?php if ($total > 0): ?>
        <form method="POST" action="<?= e($base) ?>/manage/gcsms/logs/clear"
              onsubmit="return confirm('ყველა ჩანაწერი წაიშლება. გაგრძელება?')">
            <button type="submit" class="btn btn-ghost" style="color:#ef4444;font-size:13px">🗑 ლოგის გასუფთავება</button>
        </form>
        <?php endif ?>
    </div>

    <?php if (isset($_GET['cleared'])): ?>
    <div class="alert alert-success" style="margin:0 20px 16px">✓ ლოგი გასუფთავდა.</div>
    <?php endif ?>

    <?php if (empty($items)): ?>
    <div class="empty">
        <div class="empty-icon">📋</div>
        <h3>ლოგი ცარიელია</h3>
        <p>SMS-ის გაგზავნის შემდეგ ჩანაწერები გამოჩნდება აქ.</p>
    </div>
    <?php else: ?>
    <table class="data-table">
        <thead><tr>
            <th>ტიპი</th>
            <th>ნომერი</th>
            <th>შეტყობინება</th>
            <th>Message ID</th>
            <th>სტატუსი</th>
            <th>თარიღი</th>
        </tr></thead>
        <tbody>
        <?php foreach ($items as $log):
            [$c, $bg] = $statusColors[$log['status']] ?? ['#94a3b8', '#f1f5f9'];
            $icon = $typeIcons[$log['type']] ?? '✉';
        ?>
        <tr>
            <td style="font-size:18px" title="<?= e((string)$log['type']) ?>"><?= $icon ?></td>
            <td>
                <?php
                $phones = explode(',', (string)$log['phone']);
                if (count($phones) === 1): ?>
                <span class="gs-phone"><?= e($phones[0]) ?></span>
                <?php else: ?>
                <span class="gs-phone"><?= e($phones[0]) ?></span>
                <span style="font-size:11px;color:var(--muted)">+<?= count($phones) - 1 ?> სხვა</span>
                <?php endif ?>
            </td>
            <td>
                <div class="gs-msg" title="<?= e((string)$log['message']) ?>">
                    <?= e((string)$log['message']) ?>
                </div>
            </td>
            <td class="gs-mid"><?= $log['message_id'] ? e((string)$log['message_id']) : '—' ?></td>
            <td>
                <span class="gs-badge" style="background:<?= $bg ?>;color:<?= $c ?>">
                    <?= e((string)$log['status']) ?>
                </span>
            </td>
            <td style="font-size:12px;color:var(--muted);white-space:nowrap">
                <?= date('d M Y H:i', strtotime((string)$log['created_at'])) ?>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>

    <?php if ($pages > 1): ?>
    <div style="padding:16px;display:flex;gap:6px;justify-content:center">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="?page=<?= $i ?>"
           style="padding:5px 12px;border-radius:6px;border:1px solid var(--border);font-size:13px;text-decoration:none;<?= $i === $page ? 'background:var(--accent);color:#fff;border-color:var(--accent)' : '' ?>">
            <?= $i ?>
        </a>
        <?php endfor ?>
    </div>
    <?php endif ?>
    <?php endif ?>
</div>
