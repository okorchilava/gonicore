<?php
$pageTitle     = 'GCsmsSender — Logs';
$activeNav     = 'gcsmssender-logs';
$topbarActions = '';

// HTTP code badge
$httpBadge = static function (int $code): string {
    if ($code === 200) return '<span style="background:#d1fae5;color:#065f46;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px">200 ✓</span>';
    $c = match($code) { 401 => 'Unauth', 402 => 'Balance', 403 => 'Forbidden', 503 => 'Unavail', 0 => 'cURL', default => (string)$code };
    return '<span style="background:#fef2f2;color:#991b1b;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px">' . $code . ' ' . $c . '</span>';
};

// Delivery status badge
$deliveryBadge = static function (mixed $s): string {
    if ($s === null || $s === '') {
        return '<span style="font-size:12px;color:var(--muted)">—</span>';
    }
    return match((int)$s) {
        0 => '<span style="background:#fef9c3;color:#854d0e;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px">⏳ Pending</span>',
        1 => '<span style="background:#d1fae5;color:#065f46;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px">✓ Delivered</span>',
        2 => '<span style="background:#fef2f2;color:#991b1b;font-size:10.5px;font-weight:700;padding:2px 8px;border-radius:20px">✕ Undelivered</span>',
        default => '<span style="font-size:12px;color:var(--muted)">' . (int)$s . '</span>',
    };
};
?>
<style>
.gss-dest{font-family:monospace;font-size:13px;font-weight:700}
.gss-msg{max-width:240px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font-size:12.5px}
.gss-mid{font-family:monospace;font-size:11.5px;color:var(--muted)}
</style>

<div class="card">
    <div class="card-header" style="justify-content:space-between;flex-wrap:wrap;gap:10px">
        <h3>გაგზავნების ლოგი
            <span style="font-size:13px;color:var(--muted);font-weight:400">(<?= number_format($total) ?>)</span>
        </h3>
        <?php if ($total > 0): ?>
        <form method="POST" action="<?= e($base) ?>/manage/gcsmssender/logs/clear"
              onsubmit="return confirm('ყველა ჩანაწერი წაიშლება. გაგრძელება?')">
            <button type="submit" class="btn btn-ghost" style="color:#ef4444;font-size:13px">
                🗑 ლოგის გასუფთავება
            </button>
        </form>
        <?php endif ?>
    </div>

    <?php if (isset($_GET['cleared'])): ?>
    <div class="alert alert-success" style="margin:0 20px 16px">✓ ლოგი გასუფთავდა.</div>
    <?php endif ?>
    <?php if (isset($_GET['checked'])): ?>
    <div class="alert alert-success" style="margin:0 20px 16px">✓ სტატუსი განახლდა.</div>
    <?php endif ?>

    <?php if (empty($items)): ?>
    <div class="empty">
        <div class="empty-icon">📋</div>
        <h3>ლოგი ცარიელია</h3>
        <p>SMS-ის გაგზავნის შემდეგ ჩანაწერები გამოჩნდება აქ.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto">
    <table class="data-table">
        <thead><tr>
            <th>ტიპი</th>
            <th>ნომერი</th>
            <th>შეტყობინება</th>
            <th>Seg</th>
            <th>HTTP</th>
            <th>Delivery</th>
            <th>Message ID</th>
            <th>თარიღი</th>
            <th></th>
        </tr></thead>
        <tbody>
        <?php foreach ($items as $log): ?>
        <tr>
            <td style="font-size:16px;text-align:center" title="smsno=<?= (int)$log['sms_no'] ?>">
                <?= (int)$log['sms_no'] === 1 ? '🏷' : '📨' ?>
            </td>
            <td>
                <span class="gss-dest"><?= e((string)$log['destination']) ?></span>
            </td>
            <td>
                <div class="gss-msg" title="<?= e((string)$log['content']) ?>">
                    <?= e((string)$log['content']) ?>
                </div>
            </td>
            <td style="text-align:center;font-size:13px;font-weight:700">
                <?= $log['qnt'] !== null ? (int)$log['qnt'] : '—' ?>
            </td>
            <td><?= $httpBadge((int)$log['http_code']) ?></td>
            <td><?= $deliveryBadge($log['delivery_status']) ?></td>
            <td class="gss-mid">
                <?= $log['message_id'] ? e((string)$log['message_id']) : '—' ?>
            </td>
            <td style="font-size:12px;color:var(--muted);white-space:nowrap">
                <?= date('d M Y H:i', strtotime((string)$log['created_at'])) ?>
            </td>
            <td>
                <?php if ($log['message_id']): ?>
                <a href="<?= e($base) ?>/manage/gcsmssender/logs/check?id=<?= urlencode((string)$log['message_id']) ?>&amp;page=<?= $page ?>"
                   class="btn btn-ghost" style="font-size:12px;padding:4px 10px"
                   title="Delivery სტატუსის განახლება">
                    🔄
                </a>
                <?php endif ?>
            </td>
        </tr>
        <?php endforeach ?>
        </tbody>
    </table>
    </div>

    <?php if ($pages > 1): ?>
    <div style="padding:16px;display:flex;gap:6px;justify-content:center;flex-wrap:wrap">
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
